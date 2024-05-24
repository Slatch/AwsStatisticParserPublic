<?php

namespace FSStats;

use Redis;

class BloomConfig
{
    public const COMMAND_RESERVE = 'BF.RESERVE';
    public const COMMAND_ADD = 'BF.ADD';
    public const COMMAND_EXISTS = 'BF.EXISTS';

    public const DEFAULT_ERROR_RATE = 0.000000001;
    public const DEFAULT_CAPACITY = 40000000000;
    public const NON_SCALING_FLAG = 'NONSCALING';

    private Redis $redis;
    private string $setName;

    public function __construct(Redis $client, string $setName = 'my_set')
    {
        $this->redis = $client;
        $this->setName = $setName;
    }

    public function reserve(float $errorRate = self::DEFAULT_ERROR_RATE, int $capacity = self::DEFAULT_CAPACITY): void
    {
        $this->redis->rawCommand(self::COMMAND_RESERVE, [
            $this->setName, $errorRate, $capacity, self::NON_SCALING_FLAG,
        ]);
    }

    public function exists(string $key): bool
    {
        return $this->redis->rawCommand(BloomConfig::COMMAND_EXISTS, $this->setName, $key);
    }

    public function add(string $key): void
    {
        $this->redis->rawCommand(self::COMMAND_ADD, $this->setName, $key);
    }
}
