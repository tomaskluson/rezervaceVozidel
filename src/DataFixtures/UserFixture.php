<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixture extends Fixture
{
    public const USER_ADMIN_REFERENCE = 'user-admin';
    public const USER_USER_REFERENCE = 'user-user';

    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }
    public function load(ObjectManager $manager)
    {
        $user1 = new User();
        $user1->setPassword($this->encoder->encodePassword($user1, "kokos1"));
        $user1->setCanReserve(0); // otestujeme i to, že admin bude ignorovat tuto vlastnost
        $user1->setDisplayname("Admin");
        $user1->setEmail("admin@example.com");
        $user1->setRoles(["ROLE_ADMIN"]);

        $user2 = new User();
        $user2->setPassword($this->encoder->encodePassword($user2, "kokos1"));
        $user2->setCanReserve(0);
        $user2->setDisplayname("Test");
        $user2->setEmail("test@example.com");
        $user2->setRoles(["ROLE_USER"]);

        // díky tomuto se pak dostaneme k těmto uživatelům z jiných fixtur
        $this->addReference(self::USER_ADMIN_REFERENCE, $user1);
        $this->addReference(self::USER_USER_REFERENCE, $user2);

        $manager->persist($user1);
        $manager->persist($user2);

        $manager->flush();
    }
}
