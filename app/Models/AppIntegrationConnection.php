<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppIntegrationConnection extends Model
{
    protected $table = 'appintegration_connections';

    protected $fillable = [
        'integration_id',
        'connection_type_id',
        'source_inbound',
        'source_outbound',
        'target_inbound',
        'target_outbound',
    ];

    public $timestamps = false;

    public function integration(): BelongsTo
    {
        return $this->belongsTo(AppIntegration::class, 'integration_id', 'integration_id');
    }

    public function connectionType(): BelongsTo
    {
        return $this->belongsTo(ConnectionType::class, 'connection_type_id', 'connection_type_id');
    }
}
