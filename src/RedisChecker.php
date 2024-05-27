<?php

namespace FSStats;

use Redis;

class RedisChecker
{
    public const RESULT_BELOW_128 = 'result_below';
    public const COUNT_BELOW_128 = 'count_below';
    public const RESULT_MORE_128 = 'result_equal_or_more';
    public const COUNT_MORE_128 = 'count_equal_or_more';

    private Redis $redis;

    public function __construct(Redis $client)
    {
        $this->redis = $client;
    }

    public function init(): void
    {
        if (!$this->redis->exists(self::RESULT_BELOW_128)) {
            $this->redis->set(self::RESULT_BELOW_128, 0);
        }
        if (!$this->redis->exists(self::COUNT_BELOW_128)) {
            $this->redis->set(self::COUNT_BELOW_128, 0);
        }
        if (!$this->redis->exists(self::RESULT_MORE_128)) {
            $this->redis->set(self::RESULT_MORE_128, 0);
        }
        if (!$this->redis->exists(self::COUNT_MORE_128)) {
            $this->redis->set(self::COUNT_MORE_128, 0);
        }
    }

    public function hasDate(string $date): bool
    {
        return $this->redis->exists('date_' . $date);
    }

    public function writeDate(string $date): void
    {
        $this->redis->set('date_' . $date, 1);
    }

    public function hasUrl(string $url): bool
    {
        return $this->redis->exists('url_' . $url);
    }

    public function writeUrl(string $url): void
    {
        $this->redis->set('url_' . $url, 1);
    }

    public function getResult(): ResultDto
    {
        return new ResultDto(
            (int)$this->redis->get(self::COUNT_BELOW_128),
            (int)$this->redis->get(self::COUNT_MORE_128),
            (int)$this->redis->get(self::RESULT_BELOW_128),
            (int)$this->redis->get(self::RESULT_MORE_128)
        );
    }
}
