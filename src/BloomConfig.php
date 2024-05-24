<?php

namespace FSStats;

use Redis;

class BloomConfig
{
    public const COMMAND_RESERVE = 'BF.RESERVE';
    public const COMMAND_ADD = 'BF.ADD';
    public const COMMAND_EXISTS = 'BF.EXISTS';

    public const SET_NAME = 'my_set';
    public const DEFAULT_ERROR_RATE = 0.000000001;
    //public const DEFAULT_ERROR_RATE = 0.0001;
    public const DEFAULT_CAPACITY = 40000000000;
    //public const DEFAULT_CAPACITY = 150000;
    public const NON_SCALING_FLAG = 'NONSCALING';

    private Redis $redis;
    private float $errorRate = self::DEFAULT_ERROR_RATE;
    private int $capacity = self::DEFAULT_CAPACITY;

    public function __construct(Redis $client)
    {
        $this->redis = $client;
    }

    public function reserve(): void
    {
        $this->redis->rawCommand(self::COMMAND_RESERVE, [
            self::SET_NAME,
            $this->errorRate,
            $this->capacity,
            self::NON_SCALING_FLAG,
        ]);
    }

    public function exists(string $key): bool
    {
        return $this->redis->rawCommand(BloomConfig::COMMAND_EXISTS, BloomConfig::SET_NAME, $key);
    }

    public function add(string $key): void
    {
        $this->redis->rawCommand(self::COMMAND_ADD, self::SET_NAME, $key);
    }
}
