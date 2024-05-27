<?php

namespace FSStats;

class ResultDto
{
    private int $countBelow;
    private int $countMore;
    private int $resultBelow;
    private int $resultMore;

    public function __construct(int $countBelow, int $countMore, int $resultBelow, int $resultMore)
    {
        $this->countBelow = $countBelow;
        $this->countMore = $countMore;
        $this->resultBelow = $resultBelow;
        $this->resultMore = $resultMore;
    }

    public function getCountBelow(): int
    {
        return $this->countBelow;
    }

    public function getCountMore(): int
    {
        return $this->countMore;
    }

    public function getResultBelow(): int
    {
        return $this->resultBelow;
    }

    public function getResultMore(): int
    {
        return $this->resultMore;
    }
}
