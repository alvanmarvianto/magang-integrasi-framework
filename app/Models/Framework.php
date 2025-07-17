<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Framework extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'frameworks';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'framework_id';

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
     * Get the technologies that use this framework.
     */
    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class, 'technology_frameworks', 'framework_id', 'technology_id');
    }
}
