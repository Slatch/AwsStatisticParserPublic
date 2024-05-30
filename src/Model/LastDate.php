<?php

namespace FSStats\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $date
 */
class LastDate extends Model
{
    public const TABLE_NAME = 'last_date_v2';
    protected $table = self::TABLE_NAME;

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
