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
        'description',
        'connection_endpoint',
        'direction',
        'starting_point',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'direction' => 'string',
        'starting_point' => 'string',
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
        return $this->starting_point === 'source';
    }

    /**
     * Check if the target app is the starting point.
     *
     * @return bool
     */
    public function startsFromTarget(): bool
    {
        return $this->starting_point === 'target';
    }

    /**
     * Get the starting app based on the starting point.
     *
     * @return BelongsTo
     */
    public function getStartingApp(): BelongsTo
    {
        return $this->startsFromSource() ? $this->sourceApp() : $this->targetApp();
    }

    /**
     * Get the receiving app based on the starting point.
     *
     * @return BelongsTo
     */
    public function getReceivingApp(): BelongsTo
    {
        return $this->startsFromSource() ? $this->targetApp() : $this->sourceApp();
    }
}