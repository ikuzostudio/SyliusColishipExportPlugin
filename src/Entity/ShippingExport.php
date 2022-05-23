<?php

/*
 * This file was created by developers working at BitBag
 * Do you need more information about us and what we do? Visit our https://bitbag.io website!
 * We are hiring developers from all over the world. Join us and start your new, exciting adventure and become part of us: https://bitbag.io/career
*/

declare(strict_types=1);

namespace Ikuzo\SyliusColishipPlugin\Entity;

use BitBag\SyliusShippingExportPlugin\Entity\ShippingExport as BaseShippingExport;

class ShippingExport extends BaseShippingExport
{
    /**
     * @var float|null
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