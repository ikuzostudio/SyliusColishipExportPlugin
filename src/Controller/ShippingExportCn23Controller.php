<?php


declare(strict_types=1);

namespace Ikuzo\SyliusColishipPlugin\Controller;

use BitBag\SyliusShippingExportPlugin\Entity\ShippingExport;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Response;

final class ShippingExportCn23Controller extends AbstractController
{
    public function export(Request $request, int $id, EntityManagerInterface $em)
    {
        $export = $em->getRepository(ShippingExport::class)->find($id);
        $cn23Path = null;

        if ($export->getLabelPath()) {
            $info = pathinfo($export->getLabelPath());
            

            $cn23Path = sprintf('%s/%s_cn23.%s', 
                $info['dirname'], 
                $info['filename'], 
                $info['extension']
            );

            if (!file_exists($cn23Path)) {
                $cn23Path = null;
            }
        }

        $form = $this->createFormBuilder(null)
            ->setAction($this->generateUrl('ikuzo_admin_coliship_export_cn23', ['id' => $id]))
            ->add('weight', SubmitType::class, [
                'label' => 'ikuzo.ui.download_cn23',
                'attr' => [
                    'class' => 'ui labeled icon teal button mini'
                ]
            ])
            ->getForm();

        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            return new Response(
                file_get_contents($cn23Path),
                200,
                array(
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="'.pathinfo($cn23Path)['basename'].'"',
                )
            );
        }

        return $this->renderForm('@IkuzoSyliusColishipPlugin/ShippingExport/Grid/Field/downloadCn23Button.html.twig', [
            'form' => $form,
            'cn23Path' => $cn23Path
        ]);
    }
}
