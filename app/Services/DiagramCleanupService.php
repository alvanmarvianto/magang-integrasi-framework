<?php

namespace App\Services;

use App\DTOs\StreamLayoutDTO;
use App\Models\App;
use App\Models\AppIntegration;
use App\Repositories\Interfaces\StreamLayoutRepositoryInterface;
use Illuminate\Support\Facades\Log;

class DiagramCleanupService
{
    public function __construct(
        private readonly StreamLayoutRepositoryInterface $streamLayoutRepository
    ) {}

    /**
     * Remove duplicate integrations from the database
     */
    public function removeDuplicateIntegrations(): int
    {
        try {
            $deletedCount = 0;

            $allIntegrations = AppIntegration::orderBy('integration_id')->get();
            $seenConnections = [];
            $toDelete = [];

            foreach ($allIntegrations as $integration) {
                $sourceId = $integration->getAttribute('source_app_id');
                $targetId = $integration->getAttribute('target_app_id');
                
                $directKey = $sourceId . '-' . $targetId;
                $reverseKey = $targetId . '-' . $sourceId;
                
                if (isset($seenConnections[$directKey]) || isset($seenConnections[$reverseKey])) {
                    $toDelete[] = $integration->getAttribute('integration_id');
                    $deletedCount++;
                } else {
                    $seenConnections[$directKey] = true;
                    $seenConnections[$reverseKey] = true;
                }
            }

            if (!empty($toDelete)) {
                AppIntegration::whereIn('integration_id', $toDelete)->delete();
            }

            return $deletedCount;

        } catch (\Exception $e) {
            Log::error('Error removing duplicate integrations: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Remove integrations that reference non-existent apps
     */
    public function removeInvalidIntegrations(): int
    {
        try {
            $validAppIds = App::pluck('app_id')->toArray();

            $invalidIntegrations = AppIntegration::where(function ($query) use ($validAppIds) {
                $query->whereNotIn('source_app_id', $validAppIds)
                      ->orWhereNotIn('target_app_id', $validAppIds);
            })->get();

            $deletedCount = $invalidIntegrations->count();

            if ($deletedCount > 0) {
                AppIntegration::where(function ($query) use ($validAppIds) {
                    $query->whereNotIn('source_app_id', $validAppIds)
                          ->orWhereNotIn('target_app_id', $validAppIds);
                })->delete();
            }

            return $deletedCount;

        } catch (\Exception $e) {
            Log::error('Error removing invalid integrations: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Clean up invalid layout data for a stream
     */
    public function cleanupStreamLayout(string $streamName): void
    {
        $validAppIds = App::whereHas('stream', function ($query) use ($streamName) {
            $query->where('stream_name', $streamName);
        })->pluck('app_id')->toArray();

        $externalAppIds = App::whereHas('integrations', function ($query) use ($validAppIds) {
            $query->whereIn('target_app_id', $validAppIds);
        })->orWhereHas('integratedBy', function ($query) use ($validAppIds) {
            $query->whereIn('source_app_id', $validAppIds);
        })->pluck('app_id')->toArray();

        $allValidAppIds = array_unique(array_merge($validAppIds, $externalAppIds));

        $layoutDto = $this->streamLayoutRepository->findByStreamName($streamName);
        if ($layoutDto && $layoutDto->nodesLayout) {
            $validNodeIds = collect($allValidAppIds)->map(fn($id) => (string)$id)->toArray();
            $validNodeIds[] = $streamName;
            $cleanStreamName = strtolower(trim($streamName));
            if (str_starts_with($cleanStreamName, 'stream ')) {
                $cleanStreamName = substr($cleanStreamName, 7);
            }
            $validNodeIds[] = $cleanStreamName;

            $cleanedLayout = array_filter(
                $layoutDto->nodesLayout,
                fn($key) => in_array($key, $validNodeIds),
                ARRAY_FILTER_USE_KEY
            );

            if (count($cleanedLayout) !== count($layoutDto->nodesLayout)) {
                $updatedDto = StreamLayoutDTO::forSave(
                    $layoutDto->streamId,
                    $cleanedLayout,
                    $layoutDto->edgesLayout,
                    $layoutDto->streamConfig
                );
                $this->streamLayoutRepository->update($layoutDto->id, $updatedDto);
            }
        }
    }
}
