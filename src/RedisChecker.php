<?php

namespace FSStats;

use Redis;

class RedisChecker
{
    private const KEY_RESULT_BELOW = 'result_below';
    private const KEY_COUNT_BELOW = 'count_below';
    private const KEY_RESULT_EQUAL_OR_MORE = 'result_equal_or_more';
    private const KEY_COUNT_EQUAL_OR_MORE = 'count_equal_or_more';

    private Redis $redis;

    public function __construct(Redis $client)
    {
        $this->redis = $client;
    }

    public function init(): void
    {
        if (!$this->redis->exists(self::KEY_RESULT_BELOW)) {
            $this->redis->set(self::KEY_RESULT_BELOW, 0);
        }
        if (!$this->redis->exists(self::KEY_COUNT_BELOW)) {
            $this->redis->set(self::KEY_COUNT_BELOW, 0);
        }
        if (!$this->redis->exists(self::KEY_RESULT_EQUAL_OR_MORE)) {
            $this->redis->set(self::KEY_RESULT_EQUAL_OR_MORE, 0);
        }
        if (!$this->redis->exists(self::KEY_COUNT_EQUAL_OR_MORE)) {
            $this->redis->set(self::KEY_COUNT_EQUAL_OR_MORE, 0);
        }
    }

    public function increment(int $size, int $threshold): void
    {
        if ($size >= $threshold) {
            $this->redis->incrBy(self::KEY_RESULT_EQUAL_OR_MORE, $size);
            $this->redis->incr(self::KEY_COUNT_EQUAL_OR_MORE);
            return;
        }

        $this->redis->incrBy(self::KEY_RESULT_BELOW, $size);
        $this->redis->incr(self::KEY_COUNT_BELOW);
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
            (int)$this->redis->get(self::KEY_COUNT_BELOW),
            (int)$this->redis->get(self::KEY_COUNT_EQUAL_OR_MORE),
            (int)$this->redis->get(self::KEY_RESULT_BELOW),
            (int)$this->redis->get(self::KEY_RESULT_EQUAL_OR_MORE)
        );
    }
}
