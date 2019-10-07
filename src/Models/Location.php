<?php

namespace GP\LocationServiceability\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'city',
        'county',
        'zip',
        'street_address',
        'state_id',
        'latitude',
        'longitude'
    ];

    protected $casts = [
        'latitude'  => 'string',
        'longitude' => 'string',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function state()
    {
        return $this->belongsTo(State::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function services()
    {
        return $this->hasMany(
            Service::class,
            'location_id',
            'id'
        );
    }

    /**
     * Scope Geolocation
     *
     * @param Illuminate\Database\Eloquent\Builder $query
     * @param numeric $latitude
     * @param numeric $longitude
     * @param numeric $km
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithinGeolocation($query, $latitude, $longitude, $km)
    {
        $miles = 0.621371 * $km;

        return $query->select('*')
            ->selectRaw(
                '(3959 '
                . '* ACOS('
                    . 'COS(RADIANS(?)) '
                    . '* COS(RADIANS(`latitude`)) '
                    . '* COS('
                        . 'RADIANS(`longitude`) '
                        . '- RADIANS(?)'
                    . ')'
                    . '+ SIN(RADIANS(?)) '
                    . '* SIN(RADIANS(`latitude`))'
                . ')) AS distance',
                [
                    $latitude,
                    $longitude,
                    $latitude
                ]
            )
            ->groupBy(['id', 'latitude', 'longitude'])
            ->having('distance', '<=', $miles);
    }
}
