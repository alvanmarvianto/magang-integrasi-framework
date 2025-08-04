<?php

namespace App\Services;

use App\Constants\StreamConstants;
use App\DTOs\DiagramDataDTO;
use App\DTOs\DiagramEdgeDTO;
use App\DTOs\DiagramNodeDTO;
use App\Models\App;
use App\Models\AppIntegration;
use App\Models\Stream;
use App\Repositories\Interfaces\StreamLayoutRepositoryInterface;
use Illuminate\Support\Collection;

class DiagramService
{
    public function __construct(
        private readonly StreamLayoutRepositoryInterface $streamLayoutRepository
    ) {}

    /**
     * Validate stream name
     */
    public function validateStreamName(string $streamName): bool
    {
        return in_array(strtolower($streamName), array_map('strtolower', StreamConstants::ALLOWED_DIAGRAM_STREAMS));
    }

    /**
     * Get allowed streams
     */
    public function getAllowedStreams(): array
    {
        return StreamConstants::ALLOWED_DIAGRAM_STREAMS;
    }

    /**
     * Get stream model by name
     */
    public function getStream(string $streamName): ?Stream
    {
        return Stream::where('stream_name', $streamName)->first();
    }

    /**
     * Get apps in a specific stream
     */
    public function getStreamApps(Stream $stream): Collection
    {
        return App::with('stream')->where('stream_id', $stream->getAttribute('stream_id'))->get();
    }

    /**
     * Get connected external apps for a stream
     */
    public function getConnectedExternalApps(array $homeAppIds): Collection
    {
        // Get apps that have integrations TO home stream apps
        $sourceAppIds = AppIntegration::whereIn('target_app_id', $homeAppIds)
            ->whereNotIn('source_app_id', $homeAppIds)
            ->pluck('source_app_id')
            ->unique();

        // Get apps that have integrations FROM home stream apps
        $targetAppIds = AppIntegration::whereIn('source_app_id', $homeAppIds)
            ->whereNotIn('target_app_id', $homeAppIds)
            ->pluck('target_app_id')
            ->unique();

        // Combine and remove home stream app IDs to get only external apps
        $externalAppIds = $sourceAppIds->merge($targetAppIds)->unique();

        return App::whereIn('app_id', $externalAppIds)->with('stream')->get();
    }

    /**
     * Get integrations involving specific apps using DTOs
     */
    public function getIntegrations(array $appIds, array $homeAppIds = []): Collection
    {
        $integrations = AppIntegration::whereIn('source_app_id', $appIds)
            ->whereIn('target_app_id', $appIds)
            ->with(['connectionType', 'sourceApp', 'targetApp'])
            ->get();

        // If homeAppIds provided, filter to only include connections involving home stream apps
        if (!empty($homeAppIds)) {
            $integrations = $integrations->filter(function ($integration) use ($homeAppIds) {
                $sourceIsHome = in_array($integration->source_app_id, $homeAppIds);
                $targetIsHome = in_array($integration->target_app_id, $homeAppIds);
                
                return $sourceIsHome || $targetIsHome;
            });
        }

        return $integrations;
    }

    /**
     * Save layout configuration using DTOs
     */
    public function saveLayout(string $streamName, array $nodesLayout, array $streamConfig, array $edgesLayout = []): void
    {
        if (!$this->validateStreamName($streamName)) {
            throw new \InvalidArgumentException('Invalid stream name');
        }

        $this->streamLayoutRepository->saveLayout($streamName, $nodesLayout, $edgesLayout, $streamConfig);
    }

    /**
     * Get Vue Flow data for a specific stream using DTOs
     */
    public function getVueFlowData(string $streamName, bool $isUserView = false): DiagramDataDTO
    {
        // Validate stream
        if (!$this->validateStreamName($streamName)) {
            return DiagramDataDTO::withError('Invalid stream name');
        }

        $stream = $this->getStream($streamName);
        if (!$stream) {
            return DiagramDataDTO::withError('Stream not found');
        }

        try {
            // Get apps in this stream
            $streamApps = $this->getStreamApps($stream);
            $streamAppIds = $streamApps->pluck('app_id')->toArray();

            // Get all integrations involving these stream apps
            $integrations = AppIntegration::with(['sourceApp.stream', 'targetApp.stream', 'connectionType'])
                ->where(function ($query) use ($streamAppIds) {
                    $query->whereIn('source_app_id', $streamAppIds)
                          ->orWhereIn('target_app_id', $streamAppIds);
                })
                ->get();

            // Get connected external apps with stream relationships
            $externalApps = $this->getConnectedExternalApps($streamAppIds);
            $externalApps->load('stream');

            // Combine all apps
            $allApps = $streamApps->merge($externalApps)->keyBy('app_id');

            // Create nodes using transformer
            $nodeTransformer = new \App\Services\NodeTransformer();
            $nodes = [];
            
            // Add stream parent node
            $streamNodeData = $nodeTransformer->createStreamNode($streamName, !$isUserView);
            $nodes[] = $streamNodeData;
            
            // Add stream apps
            $streamNodeApps = $nodeTransformer->transformHomeStreamApps($streamApps, $streamName, !$isUserView);
            $nodes = array_merge($nodes, $streamNodeApps->toArray());
            
            // Add external apps
            $externalNodeApps = $nodeTransformer->transformExternalApps($externalApps, !$isUserView);
            $nodes = array_merge($nodes, $externalNodeApps->toArray());

            // Get saved layout if exists
            $savedLayout = $this->streamLayoutRepository->getLayoutData($streamName);

            // Create edges using transformer with saved layout
            $edgeTransformer = new \App\Services\EdgeTransformer();
            $edges = $isUserView 
                ? $edgeTransformer->transformForUser($integrations, $savedLayout)
                : $edgeTransformer->transformForAdmin($integrations, $savedLayout);

            return DiagramDataDTO::create($nodes, $edges->toArray(), $savedLayout);

        } catch (\Exception $e) {
            return DiagramDataDTO::withError('Failed to generate diagram data: ' . $e->getMessage());
        }
    }
}
