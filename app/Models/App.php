<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
