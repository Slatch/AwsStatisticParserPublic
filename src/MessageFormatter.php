<?php

namespace FSStats;

final class MessageFormatter
{
    public const TOTAL_BYTES = 'TOTAL_BYTES';
    public const BYTES_ABOVE_TARGET = 'BYTES_ABOVE_TARGET';
    public const BYTES_BELOW_TARGET = 'BYTES_BELOW_TARGET';
    public const TOTAL_FILES = 'TOTAL_FILES';
    public const FILES_ABOVE_TARGET = 'FILES_ABOVE_TARGET';
    public const FILES_BELOW_TARGET = 'FILES_BELOW_TARGET';
    public const LAST_FILE = 'LAST_FILE';

    public const SEPARATOR = ': ';

    /**
     * Returns string message like
     * ```
     * TOTAL_BYTES: int
     * BYTES_ABOVE_TARGET: int
     * BYTES_BELOW_TARGET: int
     * TOTAL_FILES: int
     * FILES_ABOVE_TARGET: int
     * FILES_BELOW_TARGET: int
     * LAST_FILE: file_url_path
     * ```
     */
    public function formatMessage(
        int $totalBytes,
        int $bytesAboveTarget,
        int $bytesBelowTarget,
        int $totalFiles,
        int $filesAboveTarget,
        int $filesBelowTarget,
        string $lastFile,
    ): string {
        $output = [
            self::TOTAL_BYTES => $totalBytes,
            self::BYTES_ABOVE_TARGET => $bytesAboveTarget,
            self::BYTES_BELOW_TARGET => $bytesBelowTarget,
            self::TOTAL_FILES => $totalFiles,
            self::FILES_ABOVE_TARGET => $filesAboveTarget,
            self::FILES_BELOW_TARGET => $filesBelowTarget,
            self::LAST_FILE => $lastFile,
        ];

        return implode(PHP_EOL, array_map(
            function ($v, $k) {
                return $k . self::SEPARATOR . $v;
            },
            $output,
            array_keys($output)
        ));
    }
}
