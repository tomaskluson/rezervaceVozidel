<?php

namespace App\DataFixtures;

use App\Entity\Car;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CarFixture extends Fixture
{
    public const CAR_CAR1_REFERENCE = 'car-car1';

    public function load(ObjectManager $manager)
    {
        $car = new Car();
        $car->setSpz("1T2345");
        $car->setNote("První vozidlo");
        $car->setIsDeactivated(0);

        // díky tomuto se dostaneme k objektu z jiných fixtures
        $this->addReference(self::CAR_CAR1_REFERENCE, $car);

        $manager->persist($car);
        $manager->flush();
    }
}
