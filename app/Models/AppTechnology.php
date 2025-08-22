<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppTechnology extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_id',
        'tech_id',
        'version',
    ];

    /**
     * Get the app that owns this technology assignment.
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id', 'app_id');
    }

    /**
     * Get the technology for this assignment.
     */
    public function technology(): BelongsTo
    {
        return $this->belongsTo(Technology::class, 'tech_id');
    }
}
