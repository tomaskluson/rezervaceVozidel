<?php

namespace App\Form;

use App\Entity\Car;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File as FileConstraints;

class CarFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('spz', TextType::class, array(
                'attr' => array(
                    'class' => 'form-control', // Bootstrap styl
                    'minlength' => 6, // min. délka
                    'maxlength' => 7, // max. délka
                    'placeholder' => '1T23456'
                ),
                'label' => 'SPZ auta'
            ))
            ->add('note', TextareaType::class, array(
                'attr' => array(
                    'class' => 'form-control',
                    'placeholder' => 'Bílé auto značky BMW...',
                    'novalidate' => 'novalidate'
                ),
                'label' => 'Popis vozidla',
                'help' => 'Popis bude zobrazen při výběru vozidla  při rezervaci'
            ))
            ->add('isDeactivated', ChoiceType::class, array(  // něco jako select v HTML
                'label' => 'Smí být používáno pro rezervace',
                'choices'  => array(
                    'Ano' => false,
                    'Ne' => true,
                ), 'attr' => array(
                    'class' => 'form-control')
            ))
            ->add('imageFile', FileType::class, array(
                'attr' => array(
                    'class' => 'form-control-file',
                ),
                'mapped' => false,
                'constraints' => [ // musíme použít validaci zde, v anotacích ve třídě Entity nefunguje, když je mapped=>false
                    new FileConstraints([
                        'maxSize' => '2048k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/bmp',
                            'image/svg'
                        ],
                        'mimeTypesMessage' => 'Nahrejte prosím platný obrázek.', // typy v {{ types }}
                        'maxSizeMessage' => 'Soubor je příliš velký! Maximální velikost je {{ limit }} {{ suffix }}.',
                    ])
                ],
                'required' => false,
                'label' => 'Vybrat obrázek',
                'help' => 'Nejlépe průhledný a ořezaný. Obrázek bude zobrazen pro lepší představivost uživatele, aby věděl, o jaké vozidlo se jedná. Zobrazí se např. v kalendáři, detailech rezervace...',
            ))
            ->add('colorText', ColorType::class, array(
                'label' => 'Barva textu (např. v kalendáři)',
                'attr' => array(
                    'class' => 'form-control col-sm-6 col-md-2')
            ))
            ->add('colorBackground', ColorType::class, array(
                'label' => 'Barva pozadí (např. v kalendáři)',
                'attr' => array(
                    'class' => 'form-control col-sm-6 col-md-2')
            ));

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $car = $event->getData();
            $form = $event->getForm();

            // změnit popis tlačítka "uložení", pokud nebylo vozidlo vytvořeno
            if ($car->getId() === null)
                $label = "Přidat vozidlo";
            else
                $label = "Uložit změny";

            $form->add('save', SubmitType::class, array(
                'attr' => array(
                    'class' => 'btn btn-success float-right mt-3',
                ),
                'label' => $label,
            ));
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Car::class,
        ]);
    }
}
