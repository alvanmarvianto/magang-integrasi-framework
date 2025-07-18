<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Vendor extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'vendors';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'vendor_id';

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
     * Get the technologies that use this vendor.
     */
    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class, 'technology_vendors', 'vendor_id', 'technology_id');
    }
}
