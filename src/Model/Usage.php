<?php

namespace FSStats\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $k
 * @property int $s
 */
class Usage extends Model
{
    protected $table = 'usage_test';
    protected $primaryKey = null;
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'k',
        's',
    ];

    protected function casts()
    {
        return [
            'k' => 'string',
            's' => 'int',
        ];
    }
}
