<?php

namespace FSStats\Model;

class Usage
{
    public const TABLE_NAME = 'usage_v2';

    public static function getTableName(): string
    {
        return self::TABLE_NAME . ($_ENV['DB_TABLE_SUFFIX'] ?? '');
    }
}
