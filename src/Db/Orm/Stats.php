<?php

namespace FSStats\Db\Orm;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'stats')]
class Stats
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\Column(type: 'string')]
    private string $key;

    #[ORM\Column(type: 'boolean')]
    private bool $isLatest;

    #[ORM\Column(type: 'boolean')]
    private bool $isDeleteMarker;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $size;

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setIsLatest(bool $isLatest): void
    {
        $this->isLatest = $isLatest;
    }

    public function isLatest(): bool
    {
        return $this->isLatest;
    }

    public function setIsDeleteMarker(bool $isDeleteMarker): void
    {
        $this->isDeleteMarker = $isDeleteMarker;
    }

    public function isDeleteMarker(): bool
    {
        return $this->isDeleteMarker;
    }

    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    public function getSize(): int
    {
        return $this->size;
    }
}
