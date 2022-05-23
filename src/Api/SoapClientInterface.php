<?php

declare(strict_types=1);

namespace Ikuzo\SyliusColishipPlugin\Api;

interface SoapClientInterface
{
    public function createShipment(array $requestData);
}
