<?php

namespace FSStats\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $key
 * @property int $size
 */
class Usage extends Model
{
    protected $table = 'usage';

    protected $fillable = [
        'key',
        'size',
    ];

    public $timestamps = false;

    protected function casts()
    {
        return [
            'id' => 'int',
            'key' => 'string',
            'size' => 'int',
        ];
    }
}
