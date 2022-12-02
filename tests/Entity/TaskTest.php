<?php

namespace App\Tests\Entity;

use App\Entity\Task;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TaskTest extends KernelTestCase
{

    public function getEntity(): Task
    {
        return (new Task())->setTitle("Task #1")->setContent("Content #2");
    }

    public function testEntityIsValid(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $task = $this->getEntity();

        $task->setTitle("Task #1")
            ->setContent("Content #1");

        $errors = $container->get('validator')->validate($task);

        $this->assertCount(0, $errors);
    }

    public function testEntityEmptyEntity()
    {
        self::bootKernel();
        $container = static::getContainer();

        $task = $this->getEntity();
        $task->setTitle('')
            ->setContent('');

        $errors = $container->get('validator')->validate($task);

        $this->assertCount(2, $errors);
    }

    public function testGetterAndSetter()
    {
        $date = new \DateTimeImmutable();
        $task = $this->getEntity()->setIsDone(true);

        $this->assertEquals('Task #1', $task->getTitle());
        $this->assertEquals('Content #2', $task->getContent());
        $this->assertTrue($task->getIsDone());

        $task->setCreatedAt($date);

        $user = new User();

        $user->setEmail("test@test.com")
            ->setUsername("test")
            ->setPassword("password");

        $task->setOwner($user);
        $this->assertEquals($date, $task->getCreatedAt());
        $this->assertEquals($user, $task->getOwner());
        $this->assertNull($task->getId());
    }

}
