<?php

namespace FSStats;

final class ProceededValuesDto
{
    private int $totalBytes;
    private int $bytesAboveTarget;
    private int $bytesBelowTarget;

    private int $totalFiles;
    private int $filesAboveTarget;
    private int $filesBelowTarget;

    private string $lastFile;

    public function __construct(
        int $totalBytes,
        int $bytesAboveTarget,
        int $bytesBelowTarget,

        int $totalFiles,
        int $filesAboveTarget,
        int $filesBelowTarget,

        string $lastFile
    ) {
        $this->totalBytes = $totalBytes;
        $this->bytesAboveTarget = $bytesAboveTarget;
        $this->bytesBelowTarget = $bytesBelowTarget;

        $this->totalFiles = $totalFiles;
        $this->filesAboveTarget = $filesAboveTarget;
        $this->filesBelowTarget = $filesBelowTarget;

        $this->lastFile = $lastFile;
    }

    public function getTotalBytes(): int
    {
        return $this->totalBytes;
    }

    public function getBytesAboveTarget(): int
    {
        return $this->bytesAboveTarget;
    }

    public function getBytesBelowTarget(): int
    {
        return $this->bytesBelowTarget;
    }

    public function getTotalFiles(): int
    {
        return $this->totalFiles;
    }

    public function getFilesAboveTarget(): int
    {
        return $this->filesAboveTarget;
    }

    public function getFilesBelowTarget(): int
    {
        return $this->filesBelowTarget;
    }

    public function getLastFile(): string
    {
        return $this->lastFile;
    }
}
