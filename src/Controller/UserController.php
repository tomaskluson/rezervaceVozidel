<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\ReservationFormType;
use Doctrine\DBAL\DBALException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use DateTime;


class UserController extends AbstractController
{
    /**
     * @Route("/myBooking", name="myBooking")
     */
    public function indexAction(Request $request)
    {
        // blokovat anonymy (uživatel se musí přihlásit)
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $em = $this->getDoctrine()->getManager();

        $user = $this->getUser();

        $search = $request->get("search");
        $monthsBack = $request->get("monthsBack");

        // vybrat jen nové (nadcházející rezervace), staré nebo všechny
        switch($search)
        {
            case "all":
                $query = $em->getRepository('App:Reservation')->findAllBookingsBy($user, intval($monthsBack));
                break;
            case "old":
                $query = $em->getRepository("App:Reservation")->findOldBookingsBy($user, intval($monthsBack));
                break;
            case "new":
            default:
                $query = $em->getRepository("App:Reservation")->findNewBookingsBy($user);
        }


        $bookings = $query->getQuery()->execute();

        // zobrazit hlášku, že neexistují žádné rezervace
        if (empty($bookings)){
            $this->addFlash(
                'warning',
                'Zde nemáte žádné rezervace.'
            );
        }

        return $this->render('all/index.html.twig', array(
            'bookings' => $bookings,
        ));
    }

    /**
     * @Route("/allBookings", name="allBookings")
     */
    public function allBookingsAction(Request $request)
    {
        // blokovat anonymní uživatele
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $search = $request->get("search");
        $monthsBack = $request->get("monthsBack");

        if (!$monthsBack)
            $monthsBack = 12;

        $em = $this->getDoctrine()->getManager();

        // vybrat jen nové (nadcházející rezervace) nebo všechny
        switch($search)
        {
            case "all":
                $query = $em->getRepository("App:Reservation")->findAllLimit(intval($monthsBack));
                break;
            case "new":
            default:
                $query = $em->getRepository("App:Reservation")->findAllNewBookings(intval($monthsBack));
        }

        $bookings = $query->getQuery()->execute();

        // zobrazit hlášku, že neexistují žádné rezervace
        if (empty($bookings)){
            $this->addFlash(
                'warning',
                'Neexistují žádné nadcházející rezervace.'
            );
        }

        return $this->render('all/allBooking.html.twig', array(
            'bookings' => $bookings, // zde posíláme proměnnou do kontroleru, můžeme poslat i objekt (PS: toto je array objektů, k jednotlivým vlastnostem přistupujeme v twigu přes tečku)
        ));

    }

    /**
     * @Route("booking/delete/{id}", name="deleteBooking")
     */
    public function deleteBookingAction(int $id)
    {
        // blokovat anonymy
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $em = $this->getDoctrine()->getManager();

        $user = $this->getUser();

        $rezervace = $em
            ->getRepository('App:Reservation')
            ->findOneBy(array(
                    'id' => $id,
                )
            );

        // pokud rezervace neexistuje
        if (!$rezervace)
        {
            $this->addFlash(
                'danger',
                'Rezervace neexistuje.'
            );
            return $this->redirectToRoute('myBooking');
        }

        $isYourBooking = $rezervace->getUser()->getEmail() == $user->getEmail();
        $isAdmin = $this->isGranted("ROLE_ADMIN");

        if ($isYourBooking || $isAdmin) {
            $em->remove($rezervace);
            // zobrazit zprávu o úspěšnosti
            $this->addFlash(
                'success',
                'Rezervace úspěšně smazána.'
            );
            if ($isAdmin && !$isYourBooking)
            {
                $this->addFlash(
                    'info',
                    'Rezervace nebyla vaše. Vlastníkovi rezervace bude odeslán email.' // emaily budeme řešit později
                );
            }
        } else {
            $this->addFlash(
                'danger',
                'Rezervace nemohla být smazána.'
            );
        }
        $em->flush();
        return $this->redirectToRoute('myBooking');
    }

    /**
     * @Route("/addBooking", name="addBooking")
     */
    public function addBookingAction(Request $request)
    {
        // blokovat anonymy
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /* $var $user User */
        $user = $this->getUser();

        // povolit jen lidem s právem rezervace || adminům
        if ( !$this->isGranted('ROLE_ADMIN') ) {
            if (!$user->getCanReserve()) {
                $this->addFlash(
                    'danger',
                    'Nemáte práva rezervovat vozidlo.'
                );
                return $this->redirectToRoute('myBooking');
            }
        }

        $booking = new Reservation();

        $form = $this->createForm(ReservationFormType::class, $booking);

        $form->handleRequest($request);

        try {
            // pokud je validní po odeslání
            if ($form->isSubmitted() && $form->isValid()) {
                // vložit data z formu do proměnných
                $car = $form['car']->getData(); // vrací objekt
                $date_from = $form['reservation_date_from']->getData(); // 01.11.2018 22:45 den-mesic-rok h:m
                $date_to = $form['reservation_date_to']->getData();

                // parsování dat
                $date_from = date('Y-m-d H:i:s', strtotime($date_from));
                $date_to = date('Y-m-d H:i:s', strtotime($date_to));

                // vložit data do objektu
                $booking->setReservationDateFrom(DateTime::createFromFormat('Y-m-d H:i:s', $date_from));
                $booking->setReservationDateTo(DateTime::createFromFormat('Y-m-d H:i:s', $date_to));
                $booking->setUser($user); // rezervace od tohoto usera

                $em = $this->getDoctrine()->getManager();
                // vložit záznam do DB
                $em->persist($booking);
                $em->flush();
                // zobrazit zprávu o úspěšnosti
                $this->addFlash(
                    'success',
                    'Rezervace úspěšně vytvořena.'
                );

                return $this->redirectToRoute('myBooking');
            }
        } catch (DBALException $e){
            $this->addFlash(
                'danger',
                "Rezervace nemohla být uskutečněna. Ujistěte se, zda je volné vozidlo."
            );
        }

        return $this->render('all/addBooking.html.twig', array(
            'form' => $form->createView()
        ));
    }
}
