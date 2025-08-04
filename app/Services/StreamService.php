<?php

namespace App\Services;

use App\Constants\StreamConstants;
use App\Models\Stream;
use App\DTOs\StreamDTO;
use App\DTOs\HierarchyNodeDTO;
use App\Http\Resources\StreamResource;
use App\Repositories\Interfaces\StreamRepositoryInterface;
use Illuminate\Support\Collection;

class StreamService
{
    protected StreamRepositoryInterface $streamRepository;

    public function __construct(StreamRepositoryInterface $streamRepository)
    {
        $this->streamRepository = $streamRepository;
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
        return StreamDTO::fromModel($stream);
    }

    /**
     * Update existing stream
     */
    public function updateStream(Stream $stream, array $data): StreamDTO
    {
        $this->validateStreamData($data);
        
        $this->streamRepository->update($stream, $data);
        
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
        
        return $this->streamRepository->delete($stream);
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
        return StreamConstants::ALLOWED_DIAGRAM_STREAMS;
    }

    /**
     * Validate if stream is allowed for diagram operations
     */
    public function isStreamAllowedForDiagram(string $streamName): bool
    {
        return in_array(strtolower($streamName), array_map('strtolower', $this->getAllowedDiagramStreams()));
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

        // Check for valid stream name format (only alphanumeric and underscores)
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $data['stream_name'])) {
            throw new \InvalidArgumentException('Stream name can only contain letters, numbers, and underscores');
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
     * Get app hierarchy for index page
     */
    public function getAppHierarchyForIndex(): HierarchyNodeDTO
    {
        $allowedStreamNames = $this->getAllowedDiagramStreams();
        $streams = $this->streamRepository->getAllWithAppsLimited($allowedStreamNames);
        
        $streamChildren = [];
        foreach ($streams as $stream) {
            $appChildren = [];
            foreach ($stream->apps as $app) {
                $appNode = HierarchyNodeDTO::createFolder($app->app_name, [
                    HierarchyNodeDTO::createUrl(
                        'Integrasi',
                        '/integration/app/' . $app->app_id,
                        $stream->stream_name
                    ),
                    HierarchyNodeDTO::createUrl(
                        'Teknologi', 
                        '/technology/' . $app->app_id,
                        $stream->stream_name
                    )
                ]);
                $appChildren[] = $appNode;
            }

            $streamNode = HierarchyNodeDTO::createFolder(
                'Stream - ' . strtoupper($stream->stream_name),
                $appChildren
            );
            $streamChildren[] = $streamNode;
        }

        return HierarchyNodeDTO::createFolder(
            'Bank Indonesia - DLDS',
            $streamChildren
        );
    }
} 