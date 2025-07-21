<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThirdParty extends Model
{
    protected $table = 'technology_third_parties';
    protected $primaryKey = 'third_party_id';
    public $timestamps = false;

    protected $fillable = [
        'app_id',
        'name',
        'version'
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id', 'app_id');
    }
}
