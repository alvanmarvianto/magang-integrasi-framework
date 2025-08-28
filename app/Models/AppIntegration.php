<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'int';

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
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [];

    public function connections(): HasMany
    {
        return $this->hasMany(AppIntegrationConnection::class, 'integration_id', 'integration_id');
    }

    public function functions(): HasMany
    {
        return $this->hasMany(AppIntegrationFunction::class, 'integration_id', 'integration_id');
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
        return true;
    }

    /**
     * Check if the target app is the starting point.
     *
     * @return bool
     */
    public function startsFromTarget(): bool
    {
        return false;
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