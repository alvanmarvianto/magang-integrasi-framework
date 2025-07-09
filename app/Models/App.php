<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class App extends Model
{
    use HasFactory;

    protected $primaryKey = 'app_id';

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
}