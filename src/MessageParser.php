<?php

namespace FSStats;

final class MessageParser
{
    /**
     * Returns DTO from string.
     * See format in
     * @see \FSStats\MessageFormatter
     */
    public function parse(string $input): ProceededValuesDto
    {
        $output = [];
        foreach (explode(PHP_EOL, $input) as $line) {
            $pieces = explode(MessageFormatter::SEPARATOR, $line, 2);
            $output[$pieces[0]] = $pieces[1];
        }

        return new ProceededValuesDto(
            (int)$output[MessageFormatter::TOTAL_BYTES],
            (int)$output[MessageFormatter::BYTES_ABOVE_TARGET],
            (int)$output[MessageFormatter::BYTES_BELOW_TARGET],
            (int)$output[MessageFormatter::TOTAL_FILES],
            (int)$output[MessageFormatter::FILES_ABOVE_TARGET],
            (int)$output[MessageFormatter::FILES_BELOW_TARGET],
            $output[MessageFormatter::LAST_FILE]
        );
    }
}
