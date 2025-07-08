<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stream extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'streams';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'stream_id';

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
        'stream_name',
    ];

    /**
     * Get the apps associated with the stream.
     */
    public function apps(): HasMany
    {
        return $this->hasMany(App::class, 'stream_id', 'stream_id');
    }
}