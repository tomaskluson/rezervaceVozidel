<?php

namespace App\Controller;

use App\Entity\Car;
use App\Entity\Reservation;
use App\Entity\User;
use App\Form\CarFormType;
use App\Form\ChangeUserFormType;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    /**
     * @Route("cars", name="cars")
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function listCarsAction(Request $request, PaginatorInterface $paginator, EntityManagerInterface $em)
    {
        // blokovat anonymy
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $query = $em->getRepository("App:Car")->findAll();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/index.html.twig', array(
            'pagination' => $pagination
        ));
    }

    /**
     * @Route("addCar", name="addCar")
     * @param Request $request
     */
    public function addCarAction(Request $request)
    {
        // blokovat anonymy
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // povolit jen adminům přidávat auta
        if ( !$this->isGranted('ROLE_ADMIN') ){
            return new Response('Nemáte právo přidávat auta.');
        }

        $car = new Car(); // vytvoření instance třídy car

        $form = $this->createForm(CarFormType::class, $car);

        $form->handleRequest($request);

        try {
            // pokud je validní po odeslání
            if ($form->isSubmitted() && $form->isValid()) {
                $file = $form['imageFile']->getData();

                $uploading = empty($file) ? false : true;

                // Pokud se nenahrává, nastavíme defaultní obrázek, jinak nastavíme nahraný soubor
                if (!$uploading)
                {
                    $car->setImageName($this->getParameter('default_image'));
                } else {
                    $car->setImageFile($file);
                }

                $em = $this->getDoctrine()->getManager();
                // vložit záznam do DB
                $em->persist($car);
                $em->flush();

                // zobrazit zprávu o úspěšnosti
                $this->addFlash(
                    'success',
                    'Vozidlo úspěšně přidáno'
                );
                return $this->redirectToRoute('cars');
            }
        } catch (DBALException $e) {
            $this->addFlash(
                'danger',
                "Vozidlo nemohlo být přidáno. Ujistěte se, zda již není přidáno."
            );
        }

        return $this->render('admin/addCar.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/changeCar/{id}", name="changeCar")
     * @param Request $request
     * @param $id
     */
    public function changeCarAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        // povolit jen adminům měnit auta
        if ( !$this->isGranted('ROLE_ADMIN') ){
            return new Response('Nemáte právo měnit vozidla.');
        }

        /* @var $car Car */
        $car = $em
            ->getRepository('App:Car')
            ->findOneBy(array(
                    'id' => $id,
                )
            );

        if (empty($car))
            return new Response('Vozidlo neexistuje.');

        $form = $this->createForm(CarFormType::class, $car); // pošleme vozidlo do formuláře (předvyplní se nám hodnoty + zjistí, zda upravujeme vozidlo nebo přidáváme)

        $form->handleRequest($request);

        try {
            if ($form->isSubmitted() && $form->isValid()) {
                $file = $form['imageFile']->getData();

                $uploading = empty($file) ? false : true;

                // pokud nenahráváme a nemáme natavený obrázek, natavíme defaultní.
                //jinak pokud nahráváme a náš obrázek není defaultní, smažeme původní a nastavíme nový
                if (!$uploading && empty($car->getImageName()))
                {
                    $car->setImageName($this->getParameter('default_image'));
                } else if ($uploading) {
                    if ($car->getImageName() != $this->getParameter('default_image')) {
                        $filesystem = new Filesystem();
                        $filesystem->remove($this->getParameter('vehicles_directory') . '/' . $car->getImageName());
                    }
                    $car->setImageFile($file);
                }

                // uložit do DB
                $em->persist($car);
                $em->flush();

                $this->addFlash(
                    'success',
                    'Změna vozidla byla úspěšná.'
                );

                return $this->redirectToRoute('cars');
            }
        } catch (DBALException $e){
            $this->addFlash(
                'danger',
                "Vozidlo nemohlo být změněno. Ujistěte se, zda již toto vozidlo se stejnou SPZ nebylo přidáno."
            );
        }

        return $this->render('admin/changeCar.html.twig', array(
            'form' => $form->createView(),
            'image' => $car->getImageName()
        ));
    }

    /**
     * @Route("/deleteCar/{id}", name="deleteCar")
     * @param $id
     */
    public function deleteCarAction(Request $request, $id)
    {
        //blokovat anonymy
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // povolit jen adminům mazat auta
        if ( !$this->isGranted('ROLE_ADMIN') ){
            $this->addFlash(
                'danger',
                'Nemáte práva odstraňovat vozidla.'
            );
            return $this->redirectToRoute("cars");
        }

        try {
            $em = $this->getDoctrine()->getManager();

            /* @var $car Car */
            // vybereme vozidlo z DB
            $car = $em
                ->getRepository(Car::class)
                ->findOneBy(array(
                        'id' => $id // id vozidla, které posíláme ze šablony, stejně jako changeCar/{id}
                    )
                );

            /* @var $bookings Reservation */
            // nalezne všechny rezervace spojené s tímto vozidlem
            $bookings = $em
                ->getRepository(Reservation::class)
                ->findBy(array(
                    'car' => $car,
                ));

            // odstranění obrázku
            if ($car->getImageName() != $this->getParameter('default_image')) {
                $filesystem = new Filesystem();
                $filesystem->remove($this->getParameter('vehicles_directory') . '/' . $car->getImageName());
            }

            $em->remove($car);
            $em->flush();

            $this->addFlash(
                'success',
                'Vozidlo '.$car->getNote().' úspěšně odstraněno. Počet rezervací odstraněno: '.sizeof($bookings)
            );

        }catch (DBALException $e){
            $this->addFlash(
                'danger',
                "Vozidlo nemohlo být smazáno. Ujistěte se, zda toto vozidlo existuje."

            );
        }
        return $this->redirectToRoute("cars");
    }

    /**
     * @Route("users", name="users")
     * @param PaginatorInterface $paginator
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function usersAction(PaginatorInterface $paginator, Request $request, EntityManagerInterface $em)
    {
        // blokovat anonymy
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // povolit jen adminům měnit usery
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new Response('Nemáte právo měnit uživatele.');
        }

        //$users = $this->getDoctrine()->getRepository("App:User")->findAll();
        $users = $em->getRepository('App:User')->findAll();

        $pagination = $paginator->paginate(
            $users->getQuery()->execute(),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/users.html.twig', array(
            'pagination' => $pagination,
        ));
    }

    /**
     * @Route("/changeUser/{id}", name="changeUser")
     */
    public function changeUserAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        // povolit jen adminům měnit usery
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new Response('Nemáte právo měnit uživatele.');
        }

        /* @var $user User */
        /* vyber uživatele z DB */
        $user = $em
            ->getRepository('App:User')
            ->findOneBy(array(
                    'id' => $id,
                )
            );

        if ($user->getId() == $this->getUser()->getId()) {
            $this->addFlash(
                'warning',
                'Právě editujete sebe! Pozor na uživatelská práva!'
            );
        }

        // vytvoř form uživatele + zadej tam jeho data
        $form = $this->createForm(ChangeUserFormType::class, $user);

        $form->handleRequest($request);

        try {
            if ($form->isSubmitted() && $form->isValid()) {
                // aktualizujeme v databázi
                $em->persist($user);
                $em->flush();

                $this->addFlash(
                    'success',
                    'Uživatel byl úspěšně změněn.'
                );
                // přesměrujeme ho zpět na seznam uživatelů
                return $this->redirectToRoute('users');
            }
        } catch (DBALException $e) {
            $this->addFlash(
                'danger',
                "Uživatel nemohl být změněn. Ujistěte se, zda login již nepoužívá někdo jiný."
            );
        }

        return $this->render('admin/changeUser.html.twig', array(
            'form' => $form->createView(),

        ));
    }
}
