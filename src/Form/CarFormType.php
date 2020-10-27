<?php

namespace App\Form;

use App\Entity\Car;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
