<?php

namespace App\Form;

use App\Entity\Car;
use App\Entity\Reservation;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('car', EntityType::class, array( // název pole + typ pole. Vybíráme z entity Car
                'attr' => array(
                    'class' => 'form-control custom-select', // třídy od bootstrapu pro lepší vzhled
                ),
                'choice_label' => 'note', // podle čeho vybírat vozidlo (buď SPZ, id nebo poznámka), jelikož SPZ si nikdo nepamatuje, je lepší poznámka auta :D
                'label' => 'Vyberte auto',

                'class' => Car::class,  // z jaké entity vlastně budeme vybírat data
                'query_builder' => function(EntityRepository $er) { // vybrat auta jen která nejsou deaktivovaná, ostatní budou "skrytá"
                    return $er->createQueryBuilder('car')
                        ->where('car.isDeactivated = 0');
                },
            ))
            ->add('reservation_date_from',TextType::class, array( // typ Text, protože nebudeme zobrazovat políčka pro vybrání data. Načteme si 3rd party kalendář
                'attr' => array(
                    'class' => 'form-control',
                ),
                'label' => 'Rezervace od',
                'mapped' => false // nebudeme kontrolovat správnost tohoto pole přes databázi, protože jde o "TextType" a ne "DateTimeType"
            ))
            ->add('reservation_date_to', TextType::class, array(
                'attr' => array(
                    'class' => 'form-control',
                ),
                'label' => 'Rezervace do',
                'mapped' => false
            ))
            ->add('note', TextType::class, array(  // typ Text, jde o text
                'attr' => array(
                    'class' => 'form-control',
                    'placeholder' => 'Např.: Školení v Novém Jičíně..' // placeholder v HTML vloží do pole něco jako nápovědu
                ),
                'label' => 'Poznámka k rezervaci'
            ));

        // Zde změníme label podle toho, zda upravujeme rezervaci, a nebo rezervaci vytváříme
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /* @var $booking Reservation */

            $booking = $event->getData();
            $form = $event->getForm();

            // změnit popis "uložení", pokud nebyla rezervace vytvořena
            if ($booking->getId() === null) {
                $label = "Vytvořit rezervaci";
            } else {
                $label = "Uložit změny";
            }

            $form->add('save', SubmitType::class, array( // typ pro odeslání formuláře
                'attr' => array(
                    'class' => 'btn btn-success float-right mt-3',
                ),
                'label' => $label,
            ));
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        // vloží defaultní hodnoty do polí (pokud např. upravujeme rezervaci)
        $resolver->setDefaults([
            'data_class' => Reservation::class
        ]);
    }
}
