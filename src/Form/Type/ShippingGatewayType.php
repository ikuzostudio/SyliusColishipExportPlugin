<?php

/*
 * This file was created by the developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * another great project.
 * You can find more information about us on https://bitbag.shop and write us
 * an email on mikolaj.krol@bitbag.pl.
 */

declare(strict_types=1);

namespace Ikuzo\SyliusColishipPlugin\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Sylius\Component\Core\Model\ShippingMethod;

final class ShippingGatewayType extends AbstractType
{
    static array $products = [
        'dom_fr',
        'dos_fr',
        'bpr_fr',
        'a2p_fr',
        'colr_fr',
        'j:1_fr',
        'com_om',
        'cds_om',
        'eco_om',
        'ecos_om',
        'dom_inter',
        'dos_inter',
        'cmt_inter',
        'pcs_inter',
        'bdp_inter',
        'coli_inter'
    ];

    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'ikuzo.ui.coliship.username',
                'required' => true
            ])
            ->add('password', TextType::class, [
                'label' => 'ikuzo.ui.coliship.password',
                'required' => true,
            ])
            ->add('expeditor_company', TextType::class, [
                'label' => 'ikuzo.ui.coliship.expeditor.company',
                'required' => true
            ])
            ->add('expeditor_firstname', TextType::class, [
                'label' => 'ikuzo.ui.coliship.expeditor.firstname',
            ])
            ->add('expeditor_lastname', TextType::class, [
                'label' => 'ikuzo.ui.coliship.expeditor.lastname',
            ])
            ->add('expeditor_address1', TextType::class, [
                'label' => 'ikuzo.ui.coliship.expeditor.address1',
                'required' => true
            ])
            ->add('expeditor_address2', TextType::class, [
                'label' => 'ikuzo.ui.coliship.expeditor.address2',
            ])
            ->add('expeditor_address3', TextType::class, [
                'label' => 'ikuzo.ui.coliship.expeditor.address3',
            ])
            ->add('expeditor_address4', TextType::class, [
                'label' => 'ikuzo.ui.coliship.expeditor.address4',
            ])
            ->add('expeditor_city', TextType::class, [
                'label' => 'ikuzo.ui.coliship.expeditor.city',
                'required' => true
            ])
            ->add('expeditor_zipcode', TextType::class, [
                'label' => 'ikuzo.ui.coliship.expeditor.zipcode',
                'required' => true
            ])
            ->add('expeditor_country', ChoiceType::class, [
                'label' => 'ikuzo.ui.coliship.expeditor.country',
                'required' => true,
                'choices' => [
                    'France' => 'FR',
                    'Monaco' => 'MC',
                    'Guadeloupe' => 'GP',
                    'Martinique' => 'MQ',
                    'Guyane Française' => 'GF',
                    'Réunion, Île de la' => 'RE',
                    'Mayotte' => 'YT',
                    'Saint-Pierre-et-Miquelon' => 'PM',
                    'Saint-Martin' => 'MF',
                    'Saint-Barthélemy' => 'BL',
                ]
            ])
            ->add('label_format', ChoiceType::class, [
                'label' => 'ikuzo.ui.coliship.label_format',
                'required' => true,
                'choices' => [
                    'PDF A4 300dpi' => 'PDF_A4_300dpi',
                    'PDF 10x15 300dpi' => 'PDF_10x15_300dpi',
                    'ZPL 10x15 203dpi' => 'ZPL_10x15_203dpi',
                    'ZPL 10x15 300dpi' => 'ZPL_10x15_300dpi',
                    'DPL 10x15 203dpi' => 'DPL_10x15_203dpi',
                    'DPL 10x15 300dpi' => 'DPL_10x15_300dpi',
                ]
            ])
            ->add('package_default_content', TextType::class, [
                'label' => 'ikuzo.ui.coliship.default_content',
            ])
            ->add('default_hs_code', TextType::class, [
                'label' => 'ikuzo.ui.coliship.default_hs_code',
                'help' => 'ikuzo.ui.coliship.default_hs_code_help'
            ])
            ->add('eori_number', TextType::class, [
                'label' => 'ikuzo.ui.coliship.eori_number',
                'help' => 'ikuzo.ui.coliship.eori_number_help'
            ])
        ;

        foreach (self::$products as $product) {
            foreach ($this->em->getRepository(ShippingMethod::class)->findAll() as $shippingMethod) {
                $choices[$shippingMethod->getCode()] = $shippingMethod->getId();
            }

            $builder->add('product_'.$product, ChoiceType::class, [
                'label' => 'ikuzo.ui.coliship.products.'.$product,
                'choices' => $choices,
                'multiple' => true,
                'required' => false,
            ]);
        }

    }
}
