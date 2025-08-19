<?php

namespace App\Services;

use App\Models\Stream;
use App\DTOs\StreamDTO;
use App\DTOs\HierarchyNodeDTO;
use App\Services\StreamConfigurationService;
use App\Http\Resources\StreamResource;
use App\Repositories\Interfaces\StreamRepositoryInterface;
use Illuminate\Support\Collection;

class StreamService
{
    protected StreamRepositoryInterface $streamRepository;
    protected StreamConfigurationService $streamConfigService;

    public function __construct(
        StreamRepositoryInterface $streamRepository,
        StreamConfigurationService $streamConfigService
    ) {
        $this->streamRepository = $streamRepository;
        $this->streamConfigService = $streamConfigService;
    }

    /**
     * Get all streams as DTOs
     */
    public function getAllStreams(): Collection
    {
        $streams = $this->streamRepository->getAll();
        return $streams->map(fn($stream) => StreamDTO::fromModel($stream));
    }

    /**
     * Get streams with apps count for admin table
     */
    public function getStreamsWithAppsCount(?string $search = null, string $sortBy = 'stream_name', bool $sortDesc = false, int $perPage = 10)
    {
        $query = Stream::withCount('apps');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('stream_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $direction = $sortDesc ? 'desc' : 'asc';
        $query->orderBy($sortBy, $direction);

        return $query->paginate($perPage)
            ->withQueryString()
            ->through(fn ($stream) => array_merge(
                $stream->toArray(),
                ['apps_count' => $stream->apps_count]
            ));
    }

    /**
     * Get all streams with apps as DTOs
     */
    public function getAllStreamsWithApps(): Collection
    {
        $streams = $this->streamRepository->getAllWithApps();
        return $streams->map(fn($stream) => StreamDTO::fromModel($stream));
    }

    /**
     * Find stream by ID and return as DTO
     */
    public function findStreamById(int $id): ?StreamDTO
    {
        $stream = $this->streamRepository->findById($id);
        return $stream ? StreamDTO::fromModel($stream) : null;
    }

    /**
     * Find stream by name and return as DTO
     */
    public function findStreamByName(string $name): ?StreamDTO
    {
        $stream = $this->streamRepository->findByName($name);
        return $stream ? StreamDTO::fromModel($stream) : null;
    }

    /**
     * Find stream by name with apps and return as DTO
     */
    public function findStreamByNameWithApps(string $name): ?StreamDTO
    {
        $stream = $this->streamRepository->findByNameWithApps($name);
        return $stream ? StreamDTO::fromModel($stream) : null;
    }

    /**
     * Create new stream
     */
    public function createStream(array $data): StreamDTO
    {
        $this->validateStreamData($data);
        
        $stream = $this->streamRepository->create($data);
        
        // Clear stream configuration cache after creation
        $this->streamConfigService->clearCache();
        
        return StreamDTO::fromModel($stream);
    }

    /**
     * Update existing stream
     */
    public function updateStream(Stream $stream, array $data): StreamDTO
    {
        $this->validateStreamData($data);
        
        $this->streamRepository->update($stream, $data);
        
        // Clear stream configuration cache after update
        $this->streamConfigService->clearCache();
        
        // Reload the stream to get updated data
        $updatedStream = $this->streamRepository->findById($stream->stream_id);
        return StreamDTO::fromModel($updatedStream);
    }

    /**
     * Delete stream
     */
    public function deleteStream(Stream $stream): bool
    {
        // Check if stream has apps before deletion
        if ($this->streamHasApps($stream->stream_id)) {
            throw new \InvalidArgumentException('Cannot delete stream that contains applications');
        }
        
        $result = $this->streamRepository->delete($stream);
        
        // Clear stream configuration cache after deletion
        if ($result) {
            $this->streamConfigService->clearCache();
        }
        
        return $result;
    }

    /**
     * Get streams by multiple names
     */
    public function getStreamsByNames(array $names): Collection
    {
        $streams = $this->streamRepository->getStreamsByNames($names);
        return $streams->map(fn($stream) => StreamDTO::fromModel($stream));
    }

    /**
     * Check if stream exists by name
     */
    public function streamExistsByName(string $name): bool
    {
        return $this->streamRepository->existsByName($name);
    }

    /**
     * Get stream statistics
     */
    public function getStreamStatistics(): array
    {
        return $this->streamRepository->getStreamStatistics();
    }

    /**
     * Get allowed streams for diagram operations
     */
    public function getAllowedDiagramStreams(): array
    {
        return $this->streamConfigService->getAllowedDiagramStreams();
    }

    /**
     * Get allowed streams with details for UI components
     */
    public function getAllowedDiagramStreamsWithDetails(): array
    {
        return $this->streamConfigService->getAllowedDiagramStreamsWithDetails()->toArray();
    }

    public function getAllStreamsWithDetails(): array
    {
        return $this->streamConfigService->getAllStreamsWithDetails()->toArray();
    }

    /**
     * Validate if stream is allowed for diagram operations
     */
    public function isStreamAllowedForDiagram(string $streamName): bool
    {
        return $this->streamConfigService->isStreamAllowed($streamName);
    }

    /**
     * Get stream options for forms
     */
    public function getStreamOptionsForForms(): array
    {
        return $this->getAllStreams()
            ->map(fn($streamDTO) => [
                'value' => $streamDTO->streamId,
                'label' => $streamDTO->streamName,
            ])
            ->toArray();
    }

    /**
     * Validate stream data
     */
    private function validateStreamData(array $data): void
    {
        if (empty($data['stream_name'])) {
            throw new \InvalidArgumentException('Stream name is required');
        }

        if (strlen($data['stream_name']) > 255) {
            throw new \InvalidArgumentException('Stream name must not exceed 255 characters');
        }
    }

    /**
     * Check if stream has apps
     */
    private function streamHasApps(int $streamId): bool
    {
        $stream = $this->streamRepository->findById($streamId);
        if (!$stream) {
            return false;
        }

        // Use the relationship to check if apps exist
        return $stream->apps()->exists();
    }

    /**
     * Bulk update sort order for streams
     */
    public function bulkUpdateSort(array $updates): bool
    {
        try {
            foreach ($updates as $update) {
                $stream = $this->streamRepository->findById($update['stream_id']);
                if ($stream) {
                    $this->streamRepository->update($stream, [
                        'stream_name' => $stream->stream_name,
                        'description' => $stream->description,
                        'is_allowed_for_diagram' => $stream->is_allowed_for_diagram,
                        'sort_order' => $update['sort_order'],
                        'color' => $stream->color,
                    ]);
                }
            }

            // Clear stream configuration cache after bulk update
            $this->streamConfigService->clearCache();
            
            return true;
        } catch (\Exception $e) {
            throw new \Exception('Failed to bulk update sort order: ' . $e->getMessage());
        }
    }

    /**
     * Get app hierarchy for index page
     */
    public function getAppHierarchyForIndex(): HierarchyNodeDTO
    {
        $allowedStreams = $this->streamConfigService->getAllowedDiagramStreamsWithDetails();
        $streamNames = $allowedStreams->pluck('stream_name')->toArray();
        $streams = $this->streamRepository->getAllWithAppsLimited($streamNames);
        
        $streamChildren = [];
        foreach ($allowedStreams as $allowedStream) {
            // Find the corresponding stream with apps
            $streamWithApps = $streams->firstWhere('stream_name', $allowedStream->stream_name);
            
            if (!$streamWithApps) {
                continue;
            }
            
            $appChildren = [];
            foreach ($streamWithApps->apps as $app) {
                $appSubNodes = [
                    HierarchyNodeDTO::createUrl(
                        'Integrasi',
                        '/integration/app/' . $app->app_id,
                        $streamWithApps->stream_name
                    ),
                ];
                
                // Only add "Modul" option if the app is marked as a function app
                if ($app->is_module) {
                    $appSubNodes[] = HierarchyNodeDTO::createUrl(
                        'Modul',
                        '/integration/Module/' . $app->app_id,
                        $streamWithApps->stream_name
                    );
                }
                
                $appSubNodes[] = HierarchyNodeDTO::createUrl(
                    'Teknologi', 
                    '/technology/' . $app->app_id,
                    $streamWithApps->stream_name
                );
                
                $appSubNodes[] = HierarchyNodeDTO::createUrl(
                    'Kontrak', 
                    '/contract/' . $app->app_id,
                    $streamWithApps->stream_name
                );
                
                $appNode = HierarchyNodeDTO::createFolder($app->app_name, $appSubNodes);
                $appChildren[] = $appNode;
            }

            $streamNode = HierarchyNodeDTO::createFolder(
                $allowedStream->stream_name,
                $appChildren
            );
            $streamChildren[] = $streamNode;
        }

        return HierarchyNodeDTO::createFolder(
            'Aplikasi BI - DLDS',
            $streamChildren
        );
    }
} 