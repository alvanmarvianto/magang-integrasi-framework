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
    ];

    public function integrations(): BelongsToMany
    {
        return $this->belongsToMany(App::class, 'appintegrations', 'source_app_id', 'target_app_id')
                    ->using(AppIntegration::class)
                    ->withPivot('connection_type_id');
    }

    public function integratedBy(): BelongsToMany
    {
        return $this->belongsToMany(App::class, 'appintegrations', 'target_app_id', 'source_app_id')
                    ->using(AppIntegration::class)
                    ->withPivot('connection_type_id');
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class, 'stream_id', 'stream_id');
    }

    public function appIntegrations(): HasMany
    {
        return $this->hasMany(AppIntegration::class, 'app_id', 'app_id');
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class, 'app_id', 'app_id');
    }

    public function operatingSystems(): HasMany
    {
        return $this->hasMany(OperatingSystem::class, 'app_id', 'app_id');
    }

    public function databases(): HasMany
    {
        return $this->hasMany(Database::class, 'app_id', 'app_id');
    }

    public function programmingLanguages(): HasMany
    {
        return $this->hasMany(ProgrammingLanguage::class, 'app_id', 'app_id');
    }

    public function frameworks(): HasMany
    {
        return $this->hasMany(Framework::class, 'app_id', 'app_id');
    }

    public function middlewares(): HasMany
    {
        return $this->hasMany(Middleware::class, 'app_id', 'app_id');
    }

    public function thirdParties(): HasMany
    {
        return $this->hasMany(ThirdParty::class, 'app_id', 'app_id');
    }

    public function platforms(): HasMany
    {
        return $this->hasMany(Platform::class, 'app_id', 'app_id');
    }
}

