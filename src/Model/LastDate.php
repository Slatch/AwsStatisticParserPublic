<?php

namespace FSStats\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $date
 */
class LastDate extends Model
{
    protected $table = 'last_date';

    protected $fillable = [
        'date',
    ];

    public $timestamps = false;

    protected function casts()
    {
        return [
            'id' => 'int',
            'date' => 'string',
        ];
    }
}
