<?php

declare(strict_types=1);

namespace Ikuzo\SyliusColishipPlugin\Model;

use Doctrine\ORM\Mapping as ORM;

trait ShippingExportTrait {
    
    /**
     * @ORM\Column(name="weight", type="float", nullable=true)
     **/
    protected $weight = null;

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function setWeight(?float $weight = null): void
    {
        $this->weight = $weight;
    }
}