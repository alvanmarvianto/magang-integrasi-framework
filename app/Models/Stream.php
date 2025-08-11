<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stream extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'streams';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'stream_id';

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
        'stream_name',
        'description',
        'is_allowed_for_diagram',
        'sort_order',
        'color',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_allowed_for_diagram' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the apps associated with the stream.
     */
    public function apps(): HasMany
    {
        return $this->hasMany(App::class, 'stream_id', 'stream_id');
    }

    /**
     * Scope to get only streams allowed for diagrams
     */
    public function scopeAllowedForDiagram($query)
    {
        return $query->where('is_allowed_for_diagram', true);
    }

    /**
     * Scope to get streams ordered by priority
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get all allowed streams for diagrams
     */
    public static function getAllowedDiagramStreams(): array
    {
        return static::allowedForDiagram()
            ->orderedByPriority()
            ->pluck('stream_name')
            ->toArray();
    }

    /**
     * Check if stream is allowed for diagrams
     */
    public static function isStreamAllowed(string $streamName): bool
    {
        return static::where('stream_name', $streamName)
            ->allowedForDiagram()
            ->exists();
    }
}