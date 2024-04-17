<?php

namespace FSStats;

final class Output
{
    public function write(string $message): void
    {
        echo $message;
    }

    public function writeln(string $message): void
    {
        $this->write($message . PHP_EOL);
    }
}
