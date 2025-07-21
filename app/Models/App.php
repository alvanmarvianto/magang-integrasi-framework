<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class App extends Model
{
    use HasFactory;

    protected $table = 'apps';
    protected $primaryKey = 'app_id';
    public $timestamps = false;

    protected $fillable = [
        'app_id',
        'app_name',
        'description',
        'stream_id',
        'app_type',
        'stratification'
    ];

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class, 'stream_id');
    }

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

    public function technology(): HasOne
    {
        return $this->hasOne(Technology::class, 'app_id', 'app_id');
    }
}