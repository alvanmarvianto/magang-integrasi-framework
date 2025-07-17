<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Database extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'databases';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'database_id';

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
     * Get the technologies that use this database.
     */
    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class, 'technology_databases', 'database_id', 'technology_id');
    }
}
