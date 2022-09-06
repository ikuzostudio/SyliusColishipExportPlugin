<?php


declare(strict_types=1);

namespace Ikuzo\SyliusColishipPlugin\Controller;

use BitBag\SyliusShippingExportPlugin\Entity\ShippingExport;
use BitBag\SyliusShippingExportPlugin\Event\ExportShipmentEvent;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Component\Shipping\Model\Shipment;
use Sylius\Component\Shipping\Model\ShipmentInterface;
use Symfony\Component\HttpFoundation\Request;
use Sylius\Component\Resource\Model\ResourceInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Webmozart\Assert\Assert;

final class ShippingExportController extends ResourceController
{
    /** @var ShippingExportRepositoryInterface */
    protected $repository;

    public function exportAllNewShipmentsAction(Request $request): RedirectResponse
    {
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

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
        $shippingExports = [];
        $ids = $request->get('ids');
        
        if ($ids) {
            foreach ($ids as $key => $id) {
                $shipment = $em->getRepository(Shipment::class)->find($id);
                if ($shipment instanceof ShipmentInterface) {
                    $shippingExport = $em->getRepository(ShippingExport::class)->findOneBy([
                        'shipment' => $shipment
                    ]);
                    
                    
                    try {
                        $request->request->set('id', $id);
                        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);
                        $this->eventDispatcher->dispatch(
                            ExportShipmentEvent::SHORT_NAME,
                            $configuration,
                            $shippingExport
                        );
                        
                        $shippingExports[] = $shippingExport;
                    } catch (\Throwable $th) {
                        unset($ids[$key]);
                    }
                }
            }
        }

        $return = [];
        
        foreach ($shippingExports as $shippingExport) {
            $return[] = [
                'shipping_id' => $shippingExport->getShipment()->getId(),
                'tracking_code' => $shippingExport->getShipment()->getTracking(),
                'label' => base64_encode(file_get_contents($shippingExport->getLabelPath())) 
            ];
        }

        return $this->json($return);
    }

}
