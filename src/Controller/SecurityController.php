<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserFormType;
use Doctrine\DBAL\DBALException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * @param Request $request
     * @param UserPasswordEncoderInterface $encoder
     * @Route("/register", name="register")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function registerAction(Request $request, UserPasswordEncoderInterface $encoder)
    {
        //em - entity manager
        $em = $this->getDoctrine()->getManager();

        // vytvoříme nového uživatele
        $user = new User();

        // automaticky vytvoří form z classy
        $form = $this->createForm(UserFormType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            try {
                $password = $encoder->encodePassword($user, $user->getPassword()); // heslo musíme zakódovat do DB, aby nebylo jednoduché heslo přečíst, v Symfony je defaultně nastavený hash bcrypt
                $user->setPassword($password);
                // nastavíme základní práva
                $user->setRoles(["ROLE_USER"]);
                $user->setCanReserve(false);
                $em->persist($user);
                $em->flush();
            } catch (DBALException $e) {
                $this->addFlash(
                    "danger",
                    "Litujeme, ale Váš email již nelze znovu zaregistrovat. Zkuste se přihlásit." // $e->getMessage() -> možno použít pro hlášky přímo z core, mělo by vypsat podrobnější chybu
                );
                return $this->redirectToRoute('register');
            }

            return $this->redirectToRoute('app_login');
        }
        return $this->render('security/register.html.twig', array(
            'form' => $form->createView()
        ));
    }
}
