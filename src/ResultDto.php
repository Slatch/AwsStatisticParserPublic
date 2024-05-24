<?php

namespace FSStats;

class ResultDto
{
    private int $countBelow;
    private int $countEqualOrMore;
    private int $resultBelow;
    private int $resultEqualOrMore;

    public function __construct(int $countBelow, int $countEqualOrMore, int $resultBelow, int $resultEqualOrMore)
    {
        $this->countBelow = $countBelow;
        $this->countEqualOrMore = $countEqualOrMore;
        $this->resultBelow = $resultBelow;
        $this->resultEqualOrMore = $resultEqualOrMore;
    }

    public function getCountBelow(): int
    {
        return $this->countBelow;
    }

    public function getCountEqualOrMore(): int
    {
        return $this->countEqualOrMore;
    }

    public function getResultBelow(): int
    {
        return $this->resultBelow;
    }

    public function getResultEqualOrMore(): int
    {
        return $this->resultEqualOrMore;
    }
}
