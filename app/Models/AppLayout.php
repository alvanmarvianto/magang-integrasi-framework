<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppLayout extends Model
{
    use HasFactory;

    protected $table = 'app_layouts';

    protected $fillable = [
        'app_id',
        'nodes_layout',
        'edges_layout',
        'app_config',
    ];

    protected $casts = [
        'nodes_layout' => 'array',
        'edges_layout' => 'array',
        'app_config' => 'array',
    ];

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'app_id');
    }
}
