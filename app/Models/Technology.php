<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Technology extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'technologies';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'technology_id';

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
        'app_id',
        'vendor',
        'app_type',
        'stratification',
        'os',
        'database',
        'language',
        'drc',
        'failover',
        'third_party',
        'middleware',
        'framework',
        'platform',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'app_type' => 'string',
        'stratification' => 'string',
        'vendor' => 'array',
        'os' => 'array',
        'database' => 'array',
        'language' => 'array',
        'drc' => 'array',
        'failover' => 'array',
        'third_party' => 'array',
        'middleware' => 'array',
        'framework' => 'array',
        'platform' => 'array',
    ];

    /**
     * Mutator for vendor attribute - converts array to semicolon-separated string
     */
    public function setVendorAttribute($value)
    {
        $this->attributes['vendor'] = is_array($value) ? implode(';', array_filter($value)) : $value;
    }

    /**
     * Accessor for vendor attribute - converts semicolon-separated string to array
     */
    public function getVendorAttribute($value)
    {
        return $value ? explode(';', $value) : null;
    }

    /**
     * Mutator for os attribute
     */
    public function setOsAttribute($value)
    {
        $this->attributes['os'] = is_array($value) ? implode(';', array_filter($value)) : $value;
    }

    /**
     * Accessor for os attribute
     */
    public function getOsAttribute($value)
    {
        return $value ? explode(';', $value) : null;
    }

    /**
     * Mutator for database attribute
     */
    public function setDatabaseAttribute($value)
    {
        $this->attributes['database'] = is_array($value) ? implode(';', array_filter($value)) : $value;
    }

    /**
     * Accessor for database attribute
     */
    public function getDatabaseAttribute($value)
    {
        return $value ? explode(';', $value) : null;
    }

    /**
     * Mutator for language attribute
     */
    public function setLanguageAttribute($value)
    {
        $this->attributes['language'] = is_array($value) ? implode(';', array_filter($value)) : $value;
    }

    /**
     * Accessor for language attribute
     */
    public function getLanguageAttribute($value)
    {
        return $value ? explode(';', $value) : null;
    }

    /**
     * Mutator for drc attribute
     */
    public function setDrcAttribute($value)
    {
        $this->attributes['drc'] = is_array($value) ? implode(';', array_filter($value)) : $value;
    }

    /**
     * Accessor for drc attribute
     */
    public function getDrcAttribute($value)
    {
        return $value ? explode(';', $value) : null;
    }

    /**
     * Mutator for failover attribute
     */
    public function setFailoverAttribute($value)
    {
        $this->attributes['failover'] = is_array($value) ? implode(';', array_filter($value)) : $value;
    }

    /**
     * Accessor for failover attribute
     */
    public function getFailoverAttribute($value)
    {
        return $value ? explode(';', $value) : null;
    }

    /**
     * Mutator for third_party attribute
     */
    public function setThirdPartyAttribute($value)
    {
        $this->attributes['third_party'] = is_array($value) ? implode(';', array_filter($value)) : $value;
    }

    /**
     * Accessor for third_party attribute
     */
    public function getThirdPartyAttribute($value)
    {
        return $value ? explode(';', $value) : null;
    }

    /**
     * Mutator for middleware attribute
     */
    public function setMiddlewareAttribute($value)
    {
        $this->attributes['middleware'] = is_array($value) ? implode(';', array_filter($value)) : $value;
    }

    /**
     * Accessor for middleware attribute
     */
    public function getMiddlewareAttribute($value)
    {
        return $value ? explode(';', $value) : null;
    }

    /**
     * Mutator for framework attribute
     */
    public function setFrameworkAttribute($value)
    {
        $this->attributes['framework'] = is_array($value) ? implode(';', array_filter($value)) : $value;
    }

    /**
     * Accessor for framework attribute
     */
    public function getFrameworkAttribute($value)
    {
        return $value ? explode(';', $value) : null;
    }

    /**
     * Mutator for platform attribute
     */
    public function setPlatformAttribute($value)
    {
        $this->attributes['platform'] = is_array($value) ? implode(';', array_filter($value)) : $value;
    }

    /**
     * Accessor for platform attribute
     */
    public function getPlatformAttribute($value)
    {
        return $value ? explode(';', $value) : null;
    }

    /**
     * Mutator for app_type attribute - maps old values to new values
     */
    public function setAppTypeAttribute($value)
    {
        $map = [
            'cots' => 'COTS',
            'inhouse' => 'In-House',
            'outsource' => 'Outsource',
            'COTS' => 'COTS',
            'In-House' => 'In-House',
            'Outsource' => 'Outsource',
        ];
        $this->attributes['app_type'] = $map[$value] ?? $value;
    }

    /**
     * Accessor for app_type attribute - returns mapped value
     */
    public function getAppTypeAttribute($value)
    {
        $map = [
            'cots' => 'COTS',
            'inhouse' => 'In-House',
            'outsource' => 'Outsource',
            'COTS' => 'COTS',
            'In-House' => 'In-House',
            'Outsource' => 'Outsource',
        ];
        return $map[$value] ?? $value;
    }

    /**
     * Mutator for stratification attribute - maps old values to new values
     */
    public function setStratificationAttribute($value)
    {
        $map = [
            'strategis' => 'Strategis',
            'kritikal' => 'Kritikal',
            'umum' => 'Umum',
            'Strategis' => 'Strategis',
            'Kritikal' => 'Kritikal',
            'Umum' => 'Umum',
        ];
        $this->attributes['stratification'] = $map[$value] ?? $value;
    }

    /**
     * Accessor for stratification attribute
     */
    public function getStratificationAttribute($value)
    {
        $map = [
            'strategis' => 'Strategis',
            'kritikal' => 'Kritikal',
            'umum' => 'Umum',
            'Strategis' => 'Strategis',
            'Kritikal' => 'Kritikal',
            'Umum' => 'Umum',
        ];
        return $map[$value] ?? $value;
    }

    /**
     * Get the app that owns the technology.
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id', 'app_id');
    }
}