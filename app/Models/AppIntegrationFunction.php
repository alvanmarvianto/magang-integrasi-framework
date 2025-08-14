<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppIntegrationFunction extends Model
{
    protected $table = 'appintegration_functions';

    protected $fillable = [
        'app_id',
        'integration_id',
        'function_name',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id', 'app_id');
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(AppIntegration::class, 'integration_id', 'integration_id');
    }
}
