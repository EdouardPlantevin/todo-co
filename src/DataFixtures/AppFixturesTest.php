<?php

namespace App\DataFixtures;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixturesTest extends Fixture
{

    public function __construct(private UserPasswordHasherInterface $hasher){}

    public function load(ObjectManager $manager): void
    {

        $faker = Factory::create('fr_FR');

        $users = [];

        /* User */
        for ($i = 0; $i < 10; $i++) {
            $user = new User();

            $password = $this->hasher->hashPassword(
                $user,
                'password'
            );

            if ($i == 0) {
                $user->setUsername('test')
                    ->setEmail('test@test.com')
                    ->setPassword($password);
            }
            if ($i == 1) {
                $user->setUsername('admin')
                    ->setEmail('admin@admin.com')
                    ->setPassword($password)
                    ->setRoles(["ROLE_ADMIN"]);
            }
            if ($i == 2) {
                $user->setUsername('anonyme')
                    ->setEmail('anonyme@anonyme.com')
                    ->setPassword($password);
            }

            if ($i != 0 && $i != 1 && $i != 2) {
                $user->setUsername("user$i")
                    ->setEmail("user$i@test.com")
                    ->setPassword($password);
            }

            $manager->persist($user);

            $users[] = $user;
        }

        /* Task */
        for ($i = 0; $i < 30; $i++) {
            $task = new Task();

            $task->setOwner($users[rand(0, count($users) - 1)])
                ->setTitle($faker->text(10))
                ->setContent($faker->text(50))
                ->setIsDone(rand(0, 1) == 0);

            //User
            if ($i == 0) {
                $task->setOwner($users[0]);
            }

            //Admin
            if ($i == 1) {
                $task->setOwner($users[1]);
            }
            // Anonyme
            if ($i == 2) {
                $task->setOwner($users[2]);
            }

            // Null owner
            if ($i == 3) {
                $task->setOwner(null);
            }

            $manager->persist($task);
        }

        /* Task without anonyme */
        for ($i = 0; $i < 10; $i++) {
            $task = new Task();

            $task->setOwner($users[2])
                ->setTitle($faker->text(10))
                ->setContent($faker->text(50))
                ->setIsDone(rand(0, 1) == 0);

            $manager->persist($task);
        }

        $manager->flush();
    }
}
