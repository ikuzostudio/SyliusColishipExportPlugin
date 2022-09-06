<?php


declare(strict_types=1);

namespace Ikuzo\SyliusColishipPlugin\Controller;

use BitBag\SyliusShippingExportPlugin\Entity\ShippingExport;
use BitBag\SyliusShippingExportPlugin\Event\ExportShipmentEvent;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Component\Shipping\Model\Shipment;
use Sylius\Component\Shipping\Model\ShipmentInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
final class ShippingExportController extends ResourceController
{
    public function setWeight(Request $request, int $id, EntityManagerInterface $em)
    {
        $export = $em->getRepository(ShippingExport::class)->find($id);
        $referer = $request->headers->get('referer');

        $shipment = $export->getShipment();

        if (!$shipment->getWeight()) {
            $shipment->setWeight($shipment->getShippingWeight());
        }

        $form = $this->createFormBuilder($shipment)
            ->setAction($this->generateUrl('ikuzo_admin_coliship_set_weight', ['id' => $id]))
            ->add('weight', NumberType::class, [
                'label' => false,
                'attr' => [
                    'placeholder' => 'ikuzo.ui.shippingWeightUnit'
                ]
            ])
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $export = $form->getData();
            $em->flush();

            $this->addFlash('success', 'ikuzo.ui.coliship_export.weight_changed');

            return new RedirectResponse($referer);
        }

        return $this->render('@IkuzoSyliusColishipPlugin/ShippingExport/Grid/Field/setWeightForm.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function apiExport(Request $request, EntityManagerInterface $em)
    {
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
