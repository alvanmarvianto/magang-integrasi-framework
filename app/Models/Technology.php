<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Technology extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
    ];

    protected $casts = [
        'type' => 'string',
    ];

    public const TYPES = [
        'vendors',
        'operating_systems',
        'databases',
        'programming_languages',
        'third_parties',
        'middlewares',
        'frameworks',
        'platforms'
    ];

    /**
     * Get the app technologies for this technology.
     */
    public function appTechnologies(): HasMany
    {
        return $this->hasMany(AppTechnology::class, 'tech_id');
    }

    /**
     * Get the apps that use this technology.
     */
    public function apps()
    {
        return $this->belongsToMany(App::class, 'app_technologies', 'tech_id', 'app_id')
            ->withPivot('version')
            ->withTimestamps();
    }

    /**
     * Scope to filter by technology type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get technologies by type.
     */
    public static function getByType(string $type)
    {
        return static::where('type', $type)->get();
    }
}
