<?php

namespace FSStats;

final class Progress
{
    private Output $output;

    private const MAX_PER_ROW = 10000;
    private const SHOW_EACH_N_ELEMENT = 100;

    // constants for human-readable usage
    private const MILLION = 1000000;
    private const THOUSAND = 1000;

    public function __construct(Output $output)
    {
        $this->output = $output;
    }

    public function write(int $counter): void
    {
        if ($counter % self::MAX_PER_ROW === 0 && $counter !== 0) {
            if ($counter >= self::MILLION) {
                $this->output->writeln(sprintf(' %dM proceeded', floor($counter / self::MILLION)));
            } elseif ($counter >= self::THOUSAND) {
                $this->output->writeln(sprintf(' %dk proceeded', floor($counter / self::THOUSAND)));
            } else {
                $this->output->writeln(sprintf(' %d proceeded', $counter));
            }
        }

        if ($counter % self::SHOW_EACH_N_ELEMENT === 0) {
            $this->output->write('.');
        }
    }

    public function end(int $counter): void
    {
        $this->output->writeln(sprintf(PHP_EOL . '> Proceeded %d elements' . PHP_EOL, $counter));
    }
}
