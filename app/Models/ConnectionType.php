<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConnectionType extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'connectiontypes';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'connection_type_id';

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
        'type_name',
        'color',
    ];

    public function appIntegrations(): HasMany
    {
        return $this->hasMany(AppIntegrationConnection::class, 'connection_type_id', 'connection_type_id');
    }

    public function appIntegrationConnections(): HasMany
    {
        return $this->hasMany(AppIntegrationConnection::class, 'connection_type_id', 'connection_type_id');
    }
}