<?php

namespace App\Services;

use App\DTOs\DiagramEdgeDTO;
use App\DTOs\StreamLayoutDTO;
use App\Models\App;
use App\Models\AppIntegration;
use App\Models\Stream;
use App\Models\ConnectionType;
use App\Repositories\Interfaces\StreamLayoutRepositoryInterface;

class StreamLayoutService
{
    public function __construct(
        private readonly StreamLayoutRepositoryInterface $streamLayoutRepository
    ) {
    }

    /**
     * Update stream layouts when integration data changes
     */
    public function updateStreamLayoutsForIntegration(AppIntegration $integration): void
    {
        $integration->load(['sourceApp.stream', 'targetApp.stream', 'connections.connectionType']);

        $layouts = $this->streamLayoutRepository->getAll();

        foreach ($layouts as $layoutDto) {
            $updated = false;
            $edgesLayout = $layoutDto->edgesLayout;

            $stream = Stream::find($layoutDto->streamId);
            $streamName = $stream ? $stream->stream_name : '';

            $sourceAppStream = $integration->sourceApp->stream->stream_name ?? null;
            $targetAppStream = $integration->targetApp->stream->stream_name ?? null;

            $shouldProcessForThisStream = ($sourceAppStream === $streamName || $targetAppStream === $streamName);

            if (!$shouldProcessForThisStream) {
                continue;
            }

            $foundExistingEdge = false;
            foreach ($edgesLayout as &$edge) {
                $edgeIntegrationId = null;

                if (isset($edge['data']) && isset($edge['data']['integration_id'])) {
                    $edgeIntegrationId = $edge['data']['integration_id'];
                }

                $matchesByApps = false;
                if ($edgeIntegrationId === null) {
                    $matchesByApps = isset($edge['source']) && isset($edge['target']) && (
                            // Direction 1: edge source matches integration source, edge target matches integration target
                        ($edge['source'] == $integration->getAttribute('source_app_id') &&
                            $edge['target'] == $integration->getAttribute('target_app_id')) ||
                            // Direction 2: edge source matches integration target, edge target matches integration source  
                        ($edge['source'] == $integration->getAttribute('target_app_id') &&
                            $edge['target'] == $integration->getAttribute('source_app_id'))
                    );
                }

                if ($edgeIntegrationId == $integration->getKey() || $matchesByApps) {
                    $this->updateExistingEdge($edge, $integration);
                    $updated = true;
                    $foundExistingEdge = true;
                    break;
                }
            }

            if (!$foundExistingEdge) {
                $newEdgeDto = $this->createNewEdgeFromIntegration($integration);
                $edge = $newEdgeDto->toArray();
                if (!isset($edge['style']) || !is_array($edge['style'])) {
                    $edge['style'] = [];
                }
                $edge['style']['stroke'] = '#000000';
                $edge['style']['strokeWidth'] = 2;
                unset($edge['markerEnd'], $edge['markerStart']);
                $edgesLayout[] = $edge;
                $updated = true;
            }

            if ($updated) {
                $updatedDto = StreamLayoutDTO::forSave(
                    $layoutDto->streamId,
                    $layoutDto->nodesLayout,
                    $edgesLayout,
                    $layoutDto->streamConfig
                );
                $this->streamLayoutRepository->update($layoutDto->id, $updatedDto);
            }
        }
    }

    /**
     * Synchronize stream layout edges with current AppIntegration data
     */
    public function synchronizeStreamLayoutEdges(string $streamName): int
    {
        $layoutDto = $this->streamLayoutRepository->findByStreamName($streamName);
        if (!$layoutDto) {
            return 0;
        }

        $stream = Stream::where('stream_name', $streamName)->first();
        if (!$stream) {
            return 0;
        }

        $homeStreamApps = App::where('stream_id', $stream->getAttribute('stream_id'))->get();
        $homeAppIds = $homeStreamApps->pluck('app_id')->toArray();

        $connectedAppIds = collect();

        $sourceAppIds = AppIntegration::whereIn('target_app_id', $homeAppIds)
            ->pluck('source_app_id')
            ->unique();
        $connectedAppIds = $connectedAppIds->merge($sourceAppIds);

        $targetAppIds = AppIntegration::whereIn('source_app_id', $homeAppIds)
            ->pluck('target_app_id')
            ->unique();
        $connectedAppIds = $connectedAppIds->merge($targetAppIds);

        $allValidAppIds = $connectedAppIds->merge($homeAppIds)->unique()->values()->toArray();

        $integrations = AppIntegration::whereIn('source_app_id', $allValidAppIds)
            ->whereIn('target_app_id', $allValidAppIds)
            ->with(['connections.connectionType', 'sourceApp', 'targetApp'])
            ->get();

        $existingEdgesLayout = $layoutDto->edgesLayout;
        $existingEdgesMap = [];
        foreach ($existingEdgesLayout as $edge) {
            $edgeId = $edge['id'] ?? '';
            $existingEdgesMap[$edgeId] = $edge;
        }

        $newEdgesLayout = [];
        foreach ($integrations as $integration) {
            $sourceIsHome = in_array($integration->getAttribute('source_app_id'), $homeAppIds);
            $targetIsHome = in_array($integration->getAttribute('target_app_id'), $homeAppIds);

            if ($sourceIsHome || $targetIsHome) {
                $edgeId = $integration->getAttribute('source_app_id') . '-' . $integration->getAttribute('target_app_id');
                $existingEdge = $existingEdgesMap[$edgeId] ?? null;

                $newEdgeDto = $this->createNewEdgeFromIntegration($integration);
                $newEdge = $newEdgeDto->toArray();

                if (!isset($newEdge['style']) || !is_array($newEdge['style'])) {
                    $newEdge['style'] = [];
                }
                $newEdge['style']['stroke'] = '#000000';
                $newEdge['style']['strokeWidth'] = 2;
                unset($newEdge['markerEnd'], $newEdge['markerStart']);

                if ($existingEdge) {
                    $newEdge['sourceHandle'] = $existingEdge['sourceHandle'] ?? null;
                    $newEdge['targetHandle'] = $existingEdge['targetHandle'] ?? null;
                }

                $newEdgesLayout[] = $newEdge;
            }
        }

        $streamConfig = $layoutDto->streamConfig;
        $streamConfig['totalEdges'] = count($newEdgesLayout);
        $streamConfig['lastUpdated'] = now()->toISOString();
        $streamConfig['forceEdgeBlackNoArrow'] = true;

        $nodesLayout = $layoutDto->nodesLayout;

        $stream = Stream::find($layoutDto->streamId);
        $streamName = $stream ? $stream->stream_name : '';

        $validNodesCount = count(array_filter($nodesLayout, function ($key) use ($streamName) {
            $cleanStreamName = strtolower(trim($streamName));
            if (str_starts_with($cleanStreamName, 'stream ')) {
                $cleanStreamName = substr($cleanStreamName, 7);
            }
            return $key !== $streamName && $key !== $cleanStreamName;
        }, ARRAY_FILTER_USE_KEY));

        $streamConfig['totalNodes'] = $validNodesCount;

        $updatedDto = StreamLayoutDTO::forSave(
            $layoutDto->streamId,
            $nodesLayout,
            $newEdgesLayout,
            $streamConfig
        );
        $this->streamLayoutRepository->update($layoutDto->id, $updatedDto);

        return count($newEdgesLayout);
    }

    /**
     * Ensure the stream parent node in the saved layout uses DB-cased stream name in its data fields
     */
    public function synchronizeStreamParentNodeLabel(string $streamName): int
    {
        $layoutDto = $this->streamLayoutRepository->findByStreamName($streamName);
        if (!$layoutDto) {
            return 0;
        }

        $stream = Stream::where('stream_name', $streamName)->first();
        if (!$stream) {
            return 0;
        }

        $dbName = $stream->stream_name;
        $nodesLayout = $layoutDto->nodesLayout;
        $updated = 0;

        $cleanName = strtolower(trim($dbName));
        if (str_starts_with($cleanName, 'stream ')) {
            $cleanName = substr($cleanName, 7);
        }

        foreach ($nodesLayout as $key => &$node) {
            $nodeId = (string) ($node['id'] ?? $key);
            $isParent = ($nodeId === $dbName) || ($nodeId === $cleanName);
            if (!$isParent) {
                continue;
            }

            if (!isset($node['data']) || !is_array($node['data'])) {
                $node['data'] = [];
            }

            $before = json_encode($node['data']);
            $node['data']['label'] = $dbName;
            $node['data']['app_name'] = $dbName;
            $node['data']['stream_name'] = $dbName;
            $node['data']['lingkup'] = $dbName;
            $after = json_encode($node['data']);

            if ($before !== $after) {
                $updated++;
            }
        }
        unset($node);

        if ($updated > 0) {
            $updatedDto = StreamLayoutDTO::forSave(
                $layoutDto->streamId,
                $nodesLayout,
                $layoutDto->edgesLayout,
                $layoutDto->streamConfig
            );
            $this->streamLayoutRepository->update($layoutDto->id, $updatedDto);
        }

        return $updated;
    }

    /**
     * Remove integration edges from all stream layouts
     */
    public function removeIntegrationFromLayouts(AppIntegration $integration): void
    {
        $layouts = $this->streamLayoutRepository->getAll();

        foreach ($layouts as $layoutDto) {
            $edgesLayout = $layoutDto->edgesLayout;
            $originalCount = count($edgesLayout);

            $edgesLayout = array_filter($edgesLayout, function ($edge) use ($integration) {
                $edgeIntegrationId = null;

                if (isset($edge['data']) && isset($edge['data']['integration_id'])) {
                    $edgeIntegrationId = $edge['data']['integration_id'];
                }

                return $edgeIntegrationId != $integration->getKey();
            });

            if (count($edgesLayout) !== $originalCount) {
                $streamConfig = $layoutDto->streamConfig;
                if (isset($streamConfig['totalEdges'])) {
                    $streamConfig['totalEdges'] = count($edgesLayout);
                }

                $updatedDto = StreamLayoutDTO::forSave(
                    $layoutDto->streamId,
                    $layoutDto->nodesLayout,
                    array_values($edgesLayout),
                    $streamConfig
                );
                $this->streamLayoutRepository->update($layoutDto->id, $updatedDto);
            }
        }
    }

    /**
     * Update an existing edge with integration data
     */
    private function updateExistingEdge(array &$edge, AppIntegration $integration): void
    {
        $edge['source'] = (string) $integration->getAttribute('source_app_id');
        $edge['target'] = (string) $integration->getAttribute('target_app_id');
        $edge['id'] = $integration->getAttribute('source_app_id') . '-' . $integration->getAttribute('target_app_id');

        $types = $integration->relationLoaded('connections')
            ? $integration->connections->map(fn($c) => $c->connectionType?->type_name)
                ->filter()
                ->unique()
                ->values()
                ->toArray()
            : [];
        $connectionLabel = empty($types) ? 'direct' : implode(' / ', $types);

        $edge['data'] = [
            'integration_id' => $integration->getKey(),
            'source_app_id' => $integration->getAttribute('source_app_id'),
            'target_app_id' => $integration->getAttribute('target_app_id'),
            'connection_type' => $connectionLabel,
            'connection_types' => array_map(fn($name) => ['name' => $name], $types),
            'source_app_name' => $integration->sourceApp->app_name ?? '',
            'target_app_name' => $integration->targetApp->app_name ?? '',
            'label' => $connectionLabel,
            'sourceApp' => [
                'app_id' => $integration->getAttribute('source_app_id'),
                'app_name' => $integration->sourceApp->app_name ?? ''
            ],
            'targetApp' => [
                'app_id' => $integration->getAttribute('target_app_id'),
                'app_name' => $integration->targetApp->app_name ?? ''
            ],
            'color' => '#000000'
        ];

        $edge['style'] = [
            'stroke' => '#000000',
            'strokeWidth' => 2
        ];
        $edge['color'] = '#000000';
        unset($edge['markerEnd'], $edge['markerStart']);
        $edge['type'] = 'smoothstep';
    }

    /**
     * Create a new edge from integration data
     */
    private function createNewEdgeFromIntegration(AppIntegration $integration): DiagramEdgeDTO
    {
        $types = $integration->relationLoaded('connections')
            ? $integration->connections->map(fn($c) => $c->connectionType?->type_name)
                ->filter()
                ->unique()
                ->values()
                ->toArray()
            : [];
        $connectionType = empty($types) ? 'direct' : implode(' / ', $types);
        $edgeColor = '#000000';

        $edgeData = [
            'id' => $integration->getAttribute('source_app_id') . '-' . $integration->getAttribute('target_app_id'),
            'source' => (string) $integration->getAttribute('source_app_id'),
            'target' => (string) $integration->getAttribute('target_app_id'),
            'type' => 'smoothstep',
            'color' => $edgeColor,
            'style' => [
                'stroke' => $edgeColor,
                'strokeWidth' => 2
            ],
            'data' => [
                'integration_id' => $integration->getKey(),
                'source_app_id' => $integration->getAttribute('source_app_id'),
                'target_app_id' => $integration->getAttribute('target_app_id'),
                'connection_type' => $connectionType,
                'connection_types' => array_map(fn($name) => ['name' => $name], $types),
                'source_app_name' => $integration->sourceApp->app_name ?? '',
                'target_app_name' => $integration->targetApp->app_name ?? '',
                'label' => $connectionType,
                'sourceApp' => [
                    'app_id' => $integration->getAttribute('source_app_id'),
                    'app_name' => $integration->sourceApp->app_name ?? ''
                ],
                'targetApp' => [
                    'app_id' => $integration->getAttribute('target_app_id'),
                    'app_name' => $integration->targetApp->app_name ?? ''
                ],
                'color' => '#000000'
            ],
            'label' => $connectionType,
            'connection_type' => $connectionType,
        ];

        return DiagramEdgeDTO::fromArray($edgeData);
    }

    /**
     * Synchronize connection type colors in stream layout
     * Updates edge colors in saved layouts when connection type colors change
     */
    public function synchronizeConnectionTypeColors(string $streamName): int
    {
        $layoutDTO = $this->streamLayoutRepository->findByStreamName($streamName);

        if (!$layoutDTO || !$layoutDTO->edgesLayout) {
            return 0;
        }

        $edgesLayout = $layoutDTO->edgesLayout;
        $colorsSynced = 0;

        $connectionTypes = ConnectionType::all();
        $connectionTypesById = $connectionTypes->keyBy('connection_type_id');
        $connectionTypesByName = $connectionTypes->mapWithKeys(function ($ct) {
            return [strtolower($ct->type_name) => $ct];
        });

        foreach ($edgesLayout as $index => $edge) {
            $ctId = $edge['data']['connection_type_id'] ?? null;
            $ctName = $edge['data']['connection_type'] ?? ($edge['connection_type'] ?? ($edge['label'] ?? null));

            if (!$ctId && !$ctName) {
                continue;
            }

            $connectionTypeModel = null;
            if ($ctId) {
                $connectionTypeModel = $connectionTypesById->get($ctId);
            }
            if (!$connectionTypeModel && $ctName) {
                $connectionTypeModel = $connectionTypesByName->get(strtolower($ctName));
            }

            if (!$connectionTypeModel) {
                $integration = null;
                $edgeSource = $edge['source'] ?? null;
                $edgeTarget = $edge['target'] ?? null;
                $edgeIntegrationId = $edge['data']['integration_id'] ?? null;

                if ($edgeIntegrationId) {
                    $integration = AppIntegration::with('connections.connectionType')
                        ->find($edgeIntegrationId);
                } elseif ($edgeSource && $edgeTarget) {
                    $integration = AppIntegration::with('connections.connectionType')
                        ->where(function ($q) use ($edgeSource, $edgeTarget) {
                            $q->where(function ($q2) use ($edgeSource, $edgeTarget) {
                                $q2->where('source_app_id', $edgeSource)
                                    ->where('target_app_id', $edgeTarget);
                            })->orWhere(function ($q3) use ($edgeSource, $edgeTarget) {
                                $q3->where('source_app_id', $edgeTarget)
                                    ->where('target_app_id', $edgeSource);
                            });
                        })->orderByDesc('integration_id')->first();
                }

                if ($integration && $integration->relationLoaded('connections') && $integration->connections->isNotEmpty()) {
                    $first = $integration->connections->firstWhere('connectionType', '!=', null) ?? $integration->connections->first();
                    if ($first && $first->connectionType) {
                        $connectionTypeModel = $first->connectionType;
                        $ctId = $first->connection_type_id;
                    }
                } else {
                    continue;
                }
            }

            $currentColor = $connectionTypeModel->color ?? '#000000';

            if (!isset($edgesLayout[$index]['style']) || !is_array($edgesLayout[$index]['style'])) {
                $edgesLayout[$index]['style'] = [];
            }
            if (!isset($edgesLayout[$index]['data']) || !is_array($edgesLayout[$index]['data'])) {
                $edgesLayout[$index]['data'] = [];
            }

            $savedColor = $edgesLayout[$index]['data']['color'] ?? null;
            $styleColor = $edgesLayout[$index]['style']['stroke'] ?? null;
            $rootColor = $edgesLayout[$index]['color'] ?? null;

            $currentName = $connectionTypeModel->type_name;
            $savedNameData = $edgesLayout[$index]['data']['connection_type'] ?? null;
            $savedNameRoot = $edgesLayout[$index]['connection_type'] ?? null;
            $savedLabel = $edgesLayout[$index]['label'] ?? null;

            $needsNameUpdate = ($savedNameData && strtolower($savedNameData) !== strtolower($currentName))
                || ($savedNameRoot && strtolower($savedNameRoot) !== strtolower($currentName))
                || ($savedLabel && strtolower($savedLabel) !== strtolower($currentName));

            if ($savedColor !== $currentColor || $styleColor !== $currentColor || $rootColor !== $currentColor || $needsNameUpdate) {
                $edgesLayout[$index]['data']['color'] = $currentColor;
                $edgesLayout[$index]['style']['stroke'] = $currentColor;
                $edgesLayout[$index]['color'] = $currentColor;
                $edgesLayout[$index]['data']['connection_type'] = $currentName;
                if (isset($ctId) && $ctId) {
                    $edgesLayout[$index]['data']['connection_type_id'] = $ctId;
                } elseif (isset($connectionTypeModel->connection_type_id)) {
                    $edgesLayout[$index]['data']['connection_type_id'] = $connectionTypeModel->connection_type_id;
                }
                $edgesLayout[$index]['connection_type'] = $currentName;
                $edgesLayout[$index]['label'] = $currentName;

                if (isset($edgesLayout[$index]['markerEnd']['color'])) {
                    $edgesLayout[$index]['markerEnd']['color'] = $currentColor;
                }
                if (isset($edgesLayout[$index]['markerStart']['color'])) {
                    $edgesLayout[$index]['markerStart']['color'] = $currentColor;
                }

                $colorsSynced++;
            }
        }

        if ($colorsSynced > 0) {
            $this->streamLayoutRepository->saveLayoutById(
                $layoutDTO->streamId,
                $layoutDTO->nodesLayout,
                $edgesLayout,
                $layoutDTO->streamConfig
            );
        }

        return $colorsSynced;
    }

    /**
     * Synchronize stream colors for app nodes in stream layout
     * Updates node colors when stream colors change
     */
    public function synchronizeStreamColors(string $streamName): int
    {
        $layoutDTO = $this->streamLayoutRepository->findByStreamName($streamName);

        if (!$layoutDTO || !$layoutDTO->nodesLayout) {
            return 0;
        }

        $nodesLayout = $layoutDTO->nodesLayout;
        $colorsSynced = 0;

        $streams = Stream::all();
        $streamsByExact = $streams->keyBy('stream_name');
        $streamsByNormalized = $streams->mapWithKeys(function ($s) {
            $name = $s->stream_name;
            $norm = strtolower(trim($name));
            if (str_starts_with($norm, 'stream ')) {
                $norm = substr($norm, 7);
            }
            return [$norm => $s];
        });

        $cleanStreamName = strtolower(trim($streamName));
        if (str_starts_with($cleanStreamName, 'stream ')) {
            $cleanStreamName = substr($cleanStreamName, 7);
        }

        foreach ($nodesLayout as $key => $node) {
            $nodeIdStr = (string) ($node['id'] ?? $key);

            $nodeStreamName = null;
            $isParentNode = false;

            if ($nodeIdStr === $streamName || $nodeIdStr === $cleanStreamName) {
                $nodeStreamName = $streamName;
                $isParentNode = true;
            }

            if (!$nodeStreamName && isset($node['data']['stream_name'])) {
                $nodeStreamName = (string) $node['data']['stream_name'];
            }

            if (!$nodeStreamName && is_numeric($nodeIdStr)) {
                $app = App::with('stream')->find((int) $nodeIdStr);
                if ($app && $app->stream) {
                    $nodeStreamName = $app->stream->stream_name;
                }
            }

            if (!$nodeStreamName && isset($node['data']['app_name']) && is_string($node['data']['app_name'])) {
                $byName = App::with('stream')->where('app_name', $node['data']['app_name'])->first();
                if ($byName && $byName->stream) {
                    $nodeStreamName = $byName->stream->stream_name;
                }
            }

            if (!$nodeStreamName && !is_numeric($nodeIdStr)) {
                $byIdName = App::with('stream')->where('app_name', $nodeIdStr)->first();
                if ($byIdName && $byIdName->stream) {
                    $nodeStreamName = $byIdName->stream->stream_name;
                }
            }

            if (!$nodeStreamName) {
                continue;
            }

            $streamModel = $streamsByExact->get($nodeStreamName);
            if (!$streamModel) {
                $norm = strtolower(trim($nodeStreamName));
                if (str_starts_with($norm, 'stream ')) {
                    $norm = substr($norm, 7);
                }
                $streamModel = $streamsByNormalized->get($norm);
            }

            if (!$streamModel || !$streamModel->color) {
                continue;
            }

            $currentColor = $streamModel->color;

            $writeKey = array_key_exists($nodeIdStr, $nodesLayout) ? $nodeIdStr : $key;

            if (!isset($nodesLayout[$writeKey]['style']) || !is_array($nodesLayout[$writeKey]['style'])) {
                $nodesLayout[$writeKey]['style'] = [];
            }

            $borderStr = $nodesLayout[$writeKey]['style']['border'] ?? null;
            $savedBorderColor = $nodesLayout[$writeKey]['style']['borderColor'] ?? null;

            $colorNeedsUpdate = false;

            $desiredBorderStr = "2px solid {$currentColor}";
            if ($borderStr !== $desiredBorderStr) {
                $nodesLayout[$writeKey]['style']['border'] = $desiredBorderStr;
                $colorNeedsUpdate = true;
            }

            if ($savedBorderColor !== $currentColor) {
                $nodesLayout[$writeKey]['style']['borderColor'] = $currentColor;
                $colorNeedsUpdate = true;
            }

            if (!$isParentNode && isset($nodesLayout[$writeKey]['data']) && is_array($nodesLayout[$writeKey]['data'])) {
                $dataColor = $nodesLayout[$writeKey]['data']['color'] ?? null;
                if ($dataColor !== $currentColor) {
                    $nodesLayout[$writeKey]['data']['color'] = $currentColor;
                    $colorNeedsUpdate = true;
                }
            }

            if ($writeKey !== $nodeIdStr && isset($nodesLayout[$nodeIdStr]) && is_array($nodesLayout[$nodeIdStr])) {
                if (!isset($nodesLayout[$nodeIdStr]['style']) || !is_array($nodesLayout[$nodeIdStr]['style'])) {
                    $nodesLayout[$nodeIdStr]['style'] = [];
                }
                $nodesLayout[$nodeIdStr]['style']['border'] = $desiredBorderStr;
                $nodesLayout[$nodeIdStr]['style']['borderColor'] = $currentColor;
                if (!$isParentNode) {
                    if (!isset($nodesLayout[$nodeIdStr]['data']) || !is_array($nodesLayout[$nodeIdStr]['data'])) {
                        $nodesLayout[$nodeIdStr]['data'] = [];
                    }
                    $nodesLayout[$nodeIdStr]['data']['color'] = $currentColor;
                }
                $colorNeedsUpdate = true;
            }

            if ($colorNeedsUpdate) {
                $colorsSynced++;
            }
        }

        if ($colorsSynced > 0) {
            $this->streamLayoutRepository->saveLayoutById(
                $layoutDTO->streamId,
                $nodesLayout,
                $layoutDTO->edgesLayout,
                $layoutDTO->streamConfig
            );
        }

        return $colorsSynced;
    }
}
