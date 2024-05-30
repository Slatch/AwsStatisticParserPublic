<?php

namespace FSStats\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $url
 */
class LastUrl extends Model
{
    public const TABLE_NAME = 'last_url_v2';
    protected $table = self::TABLE_NAME;

    protected $fillable = [
        'url',
    ];

    public $timestamps = false;

    protected function casts()
    {
        return [
            'id' => 'int',
            'url' => 'string',
        ];
    }
}
