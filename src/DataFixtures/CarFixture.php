<?php

namespace App\DataFixtures;

use App\Entity\Car;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CarFixture extends Fixture
{
    public const CAR_CAR1 = 'car-car1';
    public const CAR_CAR2 = 'car-car2';

    private $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    public function load(ObjectManager $manager)
    {
        $car = new Car();
        $car->setSpz("1T2345");
        $car->setNote("První vozidlo");
        $car->setIsDeactivated(0);
        $car->setImageName($this->params->get('default_image'));
        $car->setColorBackground("#000000");
        $car->setColorText("#FFFFFF");

        $car2 = new Car();
        $car2->setSpz("6T7892");
        $car2->setNote("Druhé vozidlo");
        $car2->setIsDeactivated(0);
        $car2->setImageName($this->params->get('default_image'));
        $car2->setColorBackground("#B31111");
        $car2->setColorText("#FFFFFF");

        // díky tomuto se dostaneme k objektům z jiných fixtures
        $this->addReference(self::CAR_CAR1, $car);
        $this->addReference(self::CAR_CAR2, $car2);

        $manager->persist($car);
        $manager->persist($car2);
        $manager->flush();
    }
}
