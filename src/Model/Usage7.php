<?php

namespace FSStats\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $key
 * @property int $size
 */
class Usage7 extends Model
{
    protected $table = 'usage7';
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
