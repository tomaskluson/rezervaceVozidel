<?php

namespace App\Controller;

use App\Entity\Reservation;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CalendarController extends AbstractController
{
    /**
     * @Route("/calendar", name="calendar")
     * @param Request $request
     * @return Response
     */
    public function calendarAction(Request $request)
    {
        // blokovat anonymy
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        $myReservations = $request->query->get('myReservations');

        $monthsBack = $request->query->get('monthsBack') ? $request->query->get('monthsBack') : 6;

        $em = $this->getDoctrine()->getManager();

        $cars = $em->getRepository("App:Car")->findAll();

        // vybrat jen vlastní rezervace (nadcházející), nebo všechny
        if ($myReservations) {
            $query = $em->getRepository("App:Reservation")->findAllBookingsBy($user, $monthsBack);
        }else{
            $query = $em->getRepository("App:Reservation")->findAllLimit($monthsBack);
        }
        $bookings = $query->getQuery()->execute();

        $message = (empty($bookings) && $myReservations) ? "Neexistují žádné Vaše rezervace." : "Neexistují žádné rezervace.";

        // zobrazit hlášku, že neexistují žádné rezervace
        if (empty($bookings)){
            $this->addFlash(
                'warning',
                $message
            );
        }

        return $this->render('all/calendar.html.twig', array(
            'bookings' => $bookings,
            'cars' => $cars->getQuery()->execute()
        ));
    }

    /**
     * @Route("/viewBooking/{id}", name="viewBooking")
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function viewBookingAction(int $id)
    {
        $em = $this->getDoctrine()->getManager();

        /* @var $res Reservation */
        $res = $em
            ->getRepository('App:Reservation')
            ->findOneBy(array(
                    'id' => $id,
                )
            );

        // zobrazit hlášku že tahle rezervace neexistuje
        if (empty($res)){
            $this->addFlash(
                'warning',
                'Tato rezervace neexistuje.'
            );
        }

        return $this->render('ajax/viewBooking.html.twig', array(
            'res' => $res
        ));

    }
}
