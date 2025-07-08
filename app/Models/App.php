<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class App extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'apps';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'app_id';

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
        'app_name',
        'stream_id',
        'description',
    ];

    /**
     * Get the stream that the app belongs to.
     */
    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class, 'stream_id', 'stream_id');
    }

    /**
     * The applications that this app integrates with (as a source).
     */
    public function integrations(): BelongsToMany
    {
        return $this->belongsToMany(App::class, 'app_integrations', 'source_app_id', 'target_app_id')
            ->using(AppIntegration::class)
            ->withPivot('connection_type_id');
    }

    /**
     * The applications that integrate with this app (as a target).
     */
    public function integratedBy(): BelongsToMany
    {
        return $this->belongsToMany(App::class, 'app_integrations', 'target_app_id', 'source_app_id')
            ->using(AppIntegration::class)
            ->withPivot('connection_type_id');
    }
}