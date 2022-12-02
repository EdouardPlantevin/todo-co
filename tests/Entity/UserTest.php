<?php

namespace App\Tests\Entity;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserTest extends KernelTestCase
{

    public function getEntity(): User
    {
        return (new User())->setEmail('test@test.com')->setUsername('test')->setPassword('password');
    }

    public function testValidEntity(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $user = $this->getEntity();

        $error = $container->get('validator')->validate($user);

        $this->assertCount(0, $error);
    }

    public function testBlankEntity(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $user = $this->getEntity();

        $user->setEmail('')
            ->setUsername('');

        $error = $container->get('validator')->validate($user);

        $this->assertCount(2, $error);
    }

    public function testWrongEmail(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $user = $this->getEntity();

        $user->setEmail('wrongEmail');

        $error = $container->get('validator')->validate($user);

        $this->assertCount(1, $error);
    }

    public function testGetterAndSetter(): void
    {
        $user = $this->getEntity();

        $user->setRoles(["ROLE_ADMIN"]);

        $this->assertNull($user->getId());
        $this->assertEquals('test@test.com', $user->getEmail());
        $this->assertEquals('test', $user->getUsername());
        $this->assertEquals('test', $user->getUserIdentifier());
        $this->assertEquals(["ROLE_ADMIN", "ROLE_USER"], $user->getRoles());
        $this->assertNotNull($user->getPassword());

        $task = new Task();
        $task->setTitle("Task #1")->setContent("Content #1");

        $array = new ArrayCollection();
        $array->add($task);

        $user->addTask($task);

        $this->assertEquals($array, $user->getTasks());

        $user->removeTask($task);

        $this->assertEmpty($user->getTasks());


    }
}
