<?php

/*
 * This file was created by the developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * another great project.
 * You can find more information about us on https://bitbag.shop and write us
 * an email on mikolaj.krol@bitbag.pl.
 */

declare(strict_types=1);

namespace Ikuzo\SyliusColishipPlugin\Api;

use KeepItSimple\Http\Soap\MTOMSoapClient;

final class SoapClient implements SoapClientInterface
{
    const WSDL_URL = "https://ws.colissimo.fr/sls-ws/SlsServiceWS/2.0?wsdl";

    public function __construct()
    {
        $this->client = new MTOMSoapClient(self::WSDL_URL, [
            'wsdl_cache' => 0,
            'trace' => 1,
            'exceptions' => true,
            'soap_version' => SOAP_1_1,
            'encoding' => 'utf-8'
        ]);
    }

    /**
     * @throws SoapFault
     */
    public function createShipment(array $requestData)
    {
        return $this->client->generateLabel(['generateLabelRequest' => $requestData]);
    }
}
