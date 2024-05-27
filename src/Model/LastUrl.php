<?php

namespace FSStats\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $url
 */
class LastUrl extends Model
{
    protected $table = 'last_url';

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
