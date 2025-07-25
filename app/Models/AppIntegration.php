<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AppIntegration extends Pivot
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'appintegrations';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'integration_id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'source_app_id',
        'target_app_id',
        'connection_type_id',
        'inbound',
        'outbound',
        'connection_endpoint',
        'direction',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'direction' => 'string',
    ];

    public function connectionType(): BelongsTo
    {
        return $this->belongsTo(ConnectionType::class, 'connection_type_id', 'connection_type_id');
    }

    public function sourceApp(): BelongsTo
    {
        return $this->belongsTo(App::class, 'source_app_id', 'app_id');
    }

    public function targetApp(): BelongsTo
    {
        return $this->belongsTo(App::class, 'target_app_id', 'app_id');
    }

    /**
     * Check if the integration is bidirectional.
     *
     * @return bool
     */
    public function isBidirectional(): bool
    {
        return $this->direction === 'both_ways';
    }

    /**
     * Check if the integration is unidirectional.
     *
     * @return bool
     */
    public function isUnidirectional(): bool
    {
        return $this->direction === 'one_way';
    }

    /**
     * Check if the source app is the starting point.
     *
     * @return bool
     */
    public function startsFromSource(): bool
    {
        return true; // Always starts from source now
    }

    /**
     * Check if the target app is the starting point.
     *
     * @return bool
     */
    public function startsFromTarget(): bool
    {
        return false; // Never starts from target now
    }

    /**
     * Get the starting app (always source).
     *
     * @return BelongsTo
     */
    public function getStartingApp(): BelongsTo
    {
        return $this->sourceApp();
    }

    /**
     * Get the receiving app (always target).
     *
     * @return BelongsTo
     */
    public function getReceivingApp(): BelongsTo
    {
        return $this->targetApp();
    }

    /**
     * Switch source and target apps.
     *
     * @return bool
     */
    public function switchSourceAndTarget(): bool
    {
        $originalSourceId = $this->getAttribute('source_app_id');
        $originalTargetId = $this->getAttribute('target_app_id');
        $originalInbound = $this->getAttribute('inbound');
        $originalOutbound = $this->getAttribute('outbound');

        $result = $this->update([
            'source_app_id' => $originalTargetId,
            'target_app_id' => $originalSourceId,
            'inbound' => $originalOutbound, // Swap inbound and outbound
            'outbound' => $originalInbound,
        ]);

        // Update stream layouts after switching
        if ($result) {
            $this->updateStreamLayoutsAfterSwitch($originalSourceId, $originalTargetId);
        }

        return $result;
    }

    /**
     * Update stream layouts after switching source and target
     *
     * @param int $oldSourceId
     * @param int $oldTargetId
     * @return void
     */
    private function updateStreamLayoutsAfterSwitch(int $oldSourceId, int $oldTargetId): void
    {
        $layouts = \App\Models\StreamLayout::all();
        
        foreach ($layouts as $layout) {
            $updated = false;
            $edgesLayout = $layout->edges_layout ?? [];
            
            // Update edges that involve this integration
            foreach ($edgesLayout as &$edge) {
                // Check if this edge represents the integration by integration_id
                $edgeIntegrationId = null;
                
                if (isset($edge['data']) && isset($edge['data']['integration_id'])) {
                    $edgeIntegrationId = $edge['data']['integration_id'];
                }
                
                if ($edgeIntegrationId == $this->getAttribute('integration_id')) {
                    // Update the edge source and target (swap them)
                    $edge['source'] = (string)$this->getAttribute('source_app_id');
                    $edge['target'] = (string)$this->getAttribute('target_app_id');
                    $edge['id'] = $this->getAttribute('source_app_id') . '-' . $this->getAttribute('target_app_id');
                    
                    // Update the edge data with fresh integration data
                    $this->load(['sourceApp', 'targetApp', 'connectionType']);
                    
                    $edge['data'] = array_merge($edge['data'], [
                        'source_app_id' => $this->getAttribute('source_app_id'),
                        'target_app_id' => $this->getAttribute('target_app_id'),
                        'inbound' => $this->getAttribute('inbound'),
                        'outbound' => $this->getAttribute('outbound'),
                        'source_app_name' => $this->sourceApp->app_name ?? '',
                        'target_app_name' => $this->targetApp->app_name ?? '',
                        'sourceApp' => [
                            'app_id' => $this->getAttribute('source_app_id'),
                            'app_name' => $this->sourceApp->app_name ?? ''
                        ],
                        'targetApp' => [
                            'app_id' => $this->getAttribute('target_app_id'),
                            'app_name' => $this->targetApp->app_name ?? ''
                        ]
                    ]);
                    
                    $updated = true;
                }
            }
            
            // Save the updated layout if changes were made
            if ($updated) {
                $layout->update([
                    'edges_layout' => $edgesLayout,
                ]);
            }
        }
    }

    /**
     * Check if this integration has duplicates in the database.
     *
     * @return bool
     */
    public function hasDuplicates(): bool
    {
        $sourceAppId = $this->getAttribute('source_app_id');
        $targetAppId = $this->getAttribute('target_app_id');
        
        // Check for exact duplicates (same source and target)
        $exactDuplicates = self::where('source_app_id', $sourceAppId)
            ->where('target_app_id', $targetAppId)
            ->where('integration_id', '!=', $this->getAttribute('integration_id'))
            ->exists();

        // Check for reverse duplicates (same apps but reversed)
        $reverseDuplicates = self::where('source_app_id', $targetAppId)
            ->where('target_app_id', $sourceAppId)
            ->where('integration_id', '!=', $this->getAttribute('integration_id'))
            ->exists();

        return $exactDuplicates || $reverseDuplicates;
    }

    /**
     * Get all duplicate integrations for this connection.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDuplicates()
    {
        $sourceAppId = $this->getAttribute('source_app_id');
        $targetAppId = $this->getAttribute('target_app_id');
        
        // Get exact duplicates
        $exactDuplicates = self::where('source_app_id', $sourceAppId)
            ->where('target_app_id', $targetAppId)
            ->where('integration_id', '!=', $this->getAttribute('integration_id'))
            ->get();

        // Get reverse duplicates
        $reverseDuplicates = self::where('source_app_id', $targetAppId)
            ->where('target_app_id', $sourceAppId)
            ->where('integration_id', '!=', $this->getAttribute('integration_id'))
            ->get();

        return $exactDuplicates->merge($reverseDuplicates);
    }

    /**
     * Remove all duplicate integrations for this connection, keeping only this one.
     *
     * @return int Number of duplicates removed
     */
    public function removeDuplicates(): int
    {
        $duplicates = $this->getDuplicates();
        $deletedCount = 0;

        foreach ($duplicates as $duplicate) {
            $duplicate->delete();
            $deletedCount++;
        }

        return $deletedCount;
    }

    /**
     * Get a normalized connection key for this integration (always smaller app_id first).
     *
     * @return string
     */
    public function getConnectionKey(): string
    {
        $sourceAppId = $this->getAttribute('source_app_id');
        $targetAppId = $this->getAttribute('target_app_id');
        $appIds = [$sourceAppId, $targetAppId];
        sort($appIds);
        return implode('-', $appIds);
    }
}