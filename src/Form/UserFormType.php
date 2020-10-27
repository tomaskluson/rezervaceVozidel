<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('displayname', TextType::class, [
                'attr' => array(
                    'class' => 'form-control',
                ),
                'label' => 'Vaše jméno',
                'help' => 'Bude zobrazeno při rezervacích'
            ])
            ->add('email', EmailType::class, [
                'attr' => array(
                    'class' => 'form-control',
                ),
                'label' => 'E-mail',
                'help' => 'Pro přihlášení'
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /* @var $user User */

            $user = $event->getData();
            $form = $event->getForm();

            // pokud uživatel neexistuje, právě se registruje
            if ($user->getId() === null) {
                $form->add('password', RepeatedType::class, [
                    'type' => PasswordType::class,
                    'first_options'  => array(
                        'attr' => array(
                            'class' => 'form-control',
                        ),
                        'label' => 'Heslo'
                    ),
                    'second_options' => array(
                        'attr' => array(
                            'class' => 'form-control',
                        ),
                        'label' => 'Heslo znovu'
                    ),
                    'invalid_message' => 'Zadali jste neplatné/špatné heslo.', // pokud bylo zadané neplatné heslo -> hláška
                ]);
                $label = "Registrovat";
                $button = "btn-danger"; // červený button, bootstrap styl
            } else {
                // v případě že si uživatel mění účet
                $form->add('oldPassword',PasswordType::class, [
                    'attr' => array(
                        'class' => 'form-control',
                    ),
                    'constraints' => new UserPassword([
                        'groups' => 'profile_password',
                        'message' => 'Zadejte prosím své stávající heslo.', // pokud nezadal stávající heslo
                    ]),
                    'mapped' => false,
                    'required' => false,
                    'label' => 'Staré heslo',
                    'help' => 'Pokud nechcete měnit své heslo, zanechte pole prázdné.',
                ])->add('newPassword',RepeatedType::class, [
                    'type' => PasswordType::class,
                    'constraints' => new NotBlank([
                        'groups' => 'profile_password',
                        'message' => 'Heslo nemůže být prázdné!',
                    ]),
                    'mapped' => false,
                    'required' => false,
                    'invalid_message' => 'Hesla se neshodují!',
                    'first_options' => array(
                        'label' => 'Nové heslo',
                        'attr' => array(
                            'class' => 'form-control',
                        ),
                    ),
                    'second_options' => array(
                        'label' => 'Nové heslo znovu',
                        'attr' => array(
                            'class' => 'form-control',
                        ),
                    ),
                ]);

                $label = "Uložit změny";
                $button = "btn-success"; // zelený button, bootstrap styl
            }

            $form->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'btn '.$button.' float-right mt-3' // mt-3 -> margin top o 1rem, mohli jste si všimnout i u předchozích Forms
                ],
                'label' => $label
            ]);

        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => function (FormInterface $form) {
                if ($form->has('newPassword')) {
                    $newPassword = $form->get('newPassword')->getData();
                    $oldPassword = $form->get('oldPassword')->getData();
                    if ($oldPassword || $newPassword) {
                        return ['profile', 'profile_password']; // když si uživatel bude měnit heslo
                    } else {
                        return ['profile'];
                    }
                }
            },
        ]);
    }
}
