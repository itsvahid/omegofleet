<?php

namespace App\DataFixtures;

use App\Entity\Role;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $superAdmin = new User();
        $superAdmin->setName('Super Admin');
        $superAdmin->setRole(Role::ROLE_SUPER_ADMIN);

        $manager->persist($superAdmin);

        $manager->flush();
    }
}
