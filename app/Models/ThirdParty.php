<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ThirdParty extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'third_parties';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'third_party_id';

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
     * Get the technologies that use this third party.
     */
    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class, 'technology_third_parties', 'third_party_id', 'technology_id');
    }
}
