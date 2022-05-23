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

use BitBag\SyliusShippingExportPlugin\Entity\ShippingGatewayInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShipmentInterface;

final class WebClient implements WebClientInterface
{
    public const DATE_FORMAT = 'Y-m-d';

    /** @var ShippingGatewayInterface */
    private $shippingGateway;

    /** @var ShipmentInterface */
    private $shipment;

    public function setShippingGateway(ShippingGatewayInterface $shippingGateway): void
    {
        $this->shippingGateway = $shippingGateway;
    }

    public function setShipment(ShipmentInterface $shipment): void
    {
        $this->shipment = $shipment;
    }

    public function getRequestData(float $weight): array
    {
        return [
            'contractNumber' => $this->shippingGateway->getConfigValue('username'),
            'password' => $this->shippingGateway->getConfigValue('password'),
            'outputFormat' => [
                'x' => 0,
                'y' => 0,
                'outputPrintingType' => $this->shippingGateway->getConfigValue('label_format')
            ],
            'letter' => [
                'service' => $this->getService(),
                'parcel' => [
                    'weight' => round($weight, 1)
                ],
                'sender' => $this->getSender(),
                'addressee' => $this->getAddressee()
            ]
        ];
    }

    private function getOrder(): OrderInterface
    {
        return $this->shipment->getOrder();
    }

    private function getService(): array
    {
        $order = $this->getOrder();
        
        return [
            'productCode' => $this->guessProductType(),
            'depositDate' => date('Y-m-d'),
            'orderNumber' => $order->getNumber(),
            'commercialName' => $this->getOrder()->getChannel()->getName()
        ];
    }

    private function guessProductType(): string
    {
        $method = $this->shipment->getMethod();

        foreach ($this->shippingGateway->getConfig() as $key => $value) {
            if (str_starts_with($key, 'product_')) {
                if (in_array($method->getId(), $value)) {
                    $productArr = explode('_', $key);
                    $productName = strtoupper($productArr[1]);
                    $productName = str_replace(':', '+', $productName);
                    // $product = str_replace('product_', '', $key);
                    return $productName;

                }
            }
        }
        
        throw new \Exception("Cant guess product type for this expedition. Checkout your gateway config", 1);
        
    }

    private function getSender(): array
    {
        $channelCode = $this->getOrder()->getChannel()->getCode();
        return [
            'senderParcelRef' => $channelCode,
            'address' => [
                'companyName' => $this->getShippingGatewayConfig('expeditor_company'),
                'line2' => $this->getShippingGatewayConfig('expeditor_address1'),
                'countryCode' => $this->getShippingGatewayConfig('expeditor_country'),
                'city' => $this->getShippingGatewayConfig('expeditor_city'),
                'zipCode' => $this->getShippingGatewayConfig('expeditor_zipcode')
            ]
        ];
    }

    private function getAddressee(): array
    {
        $shippingAddress = $this->getOrder()->getShippingAddress();

        return [
            'addresseeParcelRef' => 'NUM_'.$shippingAddress->getId(),
            'address' => [
                'lastName' => $shippingAddress->getLastName(),
                'firstName' =>  $shippingAddress->getFirstName(),
                'countryCode' => $shippingAddress->getCountryCode(),
                'zipCode' => str_replace('-', '', $shippingAddress->getPostcode()),
                'city' => $shippingAddress->getCity(),
                'line2' => $shippingAddress->getStreet(),
                'phoneNumber' => $this->getOrder()->getCustomer()->getPhoneNumber(),
                'email' => $this->getOrder()->getCustomer()->getEmail(),
                'companyName' => $shippingAddress->getCompany()
            ],
        ];
    }

    private function getShippingGatewayConfig($config)
    {
        return $this->shippingGateway->getConfigValue($config);
    }
}
