<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OperatingSystem extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'operating_systems';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'os_id';

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
        'name',
        'version',
    ];

    /**
     * Get the technologies that use this operating system.
     */
    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class, 'technology_operating_systems', 'os_id', 'technology_id');
    }
}
