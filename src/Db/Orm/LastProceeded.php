<?php

namespace FSStats\Db\Orm;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'last_proceeded')]
class LastProceeded
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 255)]
    private string $gzip;

    public function setGzip(string $gzip): void
    {
        $this->gzip = $gzip;
    }

    public function getGzip(): string
    {
        return $this->gzip;
    }
}
