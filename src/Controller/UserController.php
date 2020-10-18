<?php

namespace App\Controller;

use App\Entity\Reservation;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

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
}
