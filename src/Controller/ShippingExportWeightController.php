<?php


declare(strict_types=1);

namespace Ikuzo\SyliusColishipPlugin\Controller;

use BitBag\SyliusShippingExportPlugin\Entity\ShippingExport;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

final class ShippingExportWeightController extends AbstractController
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

        return $this->renderForm('@IkuzoSyliusColishipPlugin/ShippingExport/Grid/Field/setWeightForm.html.twig', [
            'form' => $form,
        ]);
    }
}
