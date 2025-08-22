<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class App extends Model
{
    protected $table = 'apps';
    protected $primaryKey = 'app_id';
    public $timestamps = false;

    protected $fillable = [
        'app_id',
        'app_name',
        'description',
        'stream_id',
        'app_type',
        'stratification',
        'is_module',
    ];

    protected $casts = [
        'is_module' => 'boolean',
    ];

    /**
     * Boot method to register model events
     */
    protected static function boot()
    {
        parent::boot();
        
        // When deleting an app, also remove it from stream layouts
        static::deleting(function ($app) {
            StreamLayout::removeAppFromLayouts($app->getKey());
        });
    }

    public function integrations(): BelongsToMany
    {
    return $this->belongsToMany(App::class, 'appintegrations', 'source_app_id', 'target_app_id')
            ->using(AppIntegration::class);
    }

    public function integratedBy(): BelongsToMany
    {
    return $this->belongsToMany(App::class, 'appintegrations', 'target_app_id', 'source_app_id')
            ->using(AppIntegration::class);
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class, 'stream_id', 'stream_id');
    }

    public function appIntegrations(): HasMany
    {
        return $this->hasMany(AppIntegration::class, 'app_id', 'app_id');
    }

    public function integrationFunctions(): HasMany
    {
        return $this->hasMany(AppIntegrationFunction::class, 'app_id', 'app_id');
    }

    /**
     * Get all technology assignments for this app.
     */
    public function appTechnologies(): HasMany
    {
        return $this->hasMany(AppTechnology::class, 'app_id', 'app_id');
    }

    /**
     * Get all technologies for this app.
     */
    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class, 'app_technologies', 'app_id', 'tech_id')
            ->withPivot('version')
            ->withTimestamps();
    }

    /**
     * Get technologies of a specific type for this app.
     */
    public function getTechnologiesByType(string $type)
    {
        return $this->technologies()->where('type', $type)->get();
    }

    /**
     * Helper methods for specific technology types
     */
    public function vendors()
    {
        return $this->getTechnologiesByType('vendors');
    }

    public function operatingSystems()
    {
        return $this->getTechnologiesByType('operating_systems');
    }

    public function databases()
    {
        return $this->getTechnologiesByType('databases');
    }

    public function programmingLanguages()
    {
        return $this->getTechnologiesByType('programming_languages');
    }

    public function frameworks()
    {
        return $this->getTechnologiesByType('frameworks');
    }

    public function middlewares()
    {
        return $this->getTechnologiesByType('middlewares');
    }

    public function thirdParties()
    {
        return $this->getTechnologiesByType('third_parties');
    }

    public function platforms()
    {
        return $this->getTechnologiesByType('platforms');
    }

    public function contracts(): BelongsToMany
    {
        return $this->belongsToMany(Contract::class, 'app_contract', 'app_id', 'contract_id');
    }
}

