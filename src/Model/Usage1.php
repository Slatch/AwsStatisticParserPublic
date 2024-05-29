<?php

namespace FSStats\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $key
 * @property int $size
 */
class Usage1 extends Model
{
    protected $table = 'usage1';
    protected $primaryKey = null;
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'key',
        'size',
    ];

    protected function casts()
    {
        return [
            'key' => 'string',
            'size' => 'int',
        ];
    }
}
