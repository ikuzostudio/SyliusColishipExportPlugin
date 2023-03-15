<?php


declare(strict_types=1);

namespace Ikuzo\SyliusColishipPlugin\Controller;

use BitBag\SyliusShippingExportPlugin\Entity\ShippingExport;
use BitBag\SyliusShippingExportPlugin\Entity\ShippingGateway;
use BitBag\SyliusShippingExportPlugin\Event\ExportShipmentEvent;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Shipping\Model\Shipment;
use Sylius\Component\Shipping\Model\ShipmentInterface;
use Symfony\Component\HttpFoundation\Request;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Webmozart\Assert\Assert;

final class ShippingExportController extends ResourceController
{
    public function exportAllNewShipmentsAction(Request $request): RedirectResponse
    {
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

        Assert::implementsInterface($this->repository, ShippingExportRepositoryInterface::class);
        $shippingExports = $this->repository->findAllWithNewOrPendingState();

        if (0 === count($shippingExports)) {
            $this->addFlash('error', 'bitbag.ui.no_new_shipments_to_export');

            return $this->redirectToReferer($request);
        }

        foreach ($shippingExports as $shippingExport) {
            $this->eventDispatcher->dispatch(
                ExportShipmentEvent::SHORT_NAME,
                $configuration,
                $shippingExport
            );
        }

        return $this->redirectToReferer($request);
    }

    public function exportSingleShipmentAction(Request $request): RedirectResponse
    {
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

        Assert::implementsInterface($this->repository, ShippingExportRepositoryInterface::class);
        /** @var ResourceInterface|null $shippingExport */
        $shippingExport = $this->repository->find($request->get('id'));
        Assert::notNull($shippingExport);

        $this->eventDispatcher->dispatch(
            ExportShipmentEvent::SHORT_NAME,
            $configuration,
            $shippingExport
        );

        return $this->redirectToReferer($request);
    }

    private function redirectToReferer(Request $request): RedirectResponse
    {
        $referer = $request->headers->get('referer');
        if (null !== $referer) {
            return new RedirectResponse($referer);
        }

        return $this->redirectToRoute($request->attributes->get('_route'));
    }

    public function apiExport(Request $request)
    {
        $em = $this->getDoctrine();
        $ids = $request->get('ids');
        $return = [];
        
        if ($ids) {
            foreach ($ids as $key => $id) {
                $order = $em->getRepository(Order::class)->find($id);
                foreach ($order->getShipments() as $shipment) {
                    if ($shipment) {
                        $shippingExport = $em->getRepository(ShippingExport::class)->findOneBy([
                            'shipment' => $shipment
                        ]);
    
                        if (!$shippingExport instanceof ShippingExport) {
                            $gateway = $em->getRepository(ShippingGateway::class)->findOneByShippingMethod($shipment->getMethod());
    
                            if ($gateway) {
                                $shippingExport = $this->get('bitbag.factory.shipping_export')->createNew();
                                $shippingExport->setShippingGateway($gateway);
                                $shippingExport->setShipment($shipment);

                                $em->getManager()->persist($shippingExport);
                                $em->getManager()->flush();
                            }
                            
                        } else {
                            if ($shippingExport->getState() === "new") {
                                try {
                                    $request->request->set('id', $shippingExport->getId());
                                    $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);
                                    
                                    $eventDispatched = $this->eventDispatcher->dispatch(
                                        ExportShipmentEvent::SHORT_NAME,
                                        $configuration,
                                        $shippingExport
                                    );
                                    
                                } catch (\Throwable $th) {
                                    $return[] = [
                                        'shipping_id' => $shippingExport->getShipment()->getId(),
                                        'error_message' => $th->getMessage(),
                                    ];
                                    continue;
                                }
                            }

                            $return[] = [
                                'shipping_id' => $shippingExport->getShipment()->getId(),
                                'tracking_code' => $shippingExport->getShipment()->getTracking(),
                                'label' => base64_encode(file_get_contents($shippingExport->getLabelPath())) 
                            ];
                        }
                        
                    }
                }
            }
        }

        
        
        // foreach ($shippingExports as $shippingExport) {
        //     $return[] = [
        //         'shipping_id' => $shippingExport->getShipment()->getId(),
        //         'tracking_code' => $shippingExport->getShipment()->getTracking(),
        //         'label' => base64_encode(file_get_contents($shippingExport->getLabelPath())) 
        //     ];
        // }

        return $this->json($return);
    }

}
