<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TaskControllerTest extends WebTestCase
{
    //Demarer les fixtures
    public function setUp(): void
    {
        parent::setUp();
        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
        $this->databaseTool->loadFixtures([
            'App\DataFixtures\AppFixturesTest'
        ]);
        self::ensureKernelShutdown();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->databaseTool);
    }

    public function userRoleUser(): User
    {

        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'test@test.com']);
        return $user;

    }

    public function userRoleAdmin(): User
    {

        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin@admin.com']);
        return $user;

    }

    public function userAnonyme(): User
    {

        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'anonyme@anonyme.com']);
        return $user;

    }

    public function testGetTasks()
    {
        $client = static::createClient();
        $user = $this->userRoleUser();
        $client->loginUser($user);

        $client->request('GET', '/tasks');

        $this->assertResponseIsSuccessful();

    }


    //Creer une tache
    public function testCreateTask()
    {
        $client = static::createClient();
        $user = $this->userRoleUser();
        $client->loginUser($user);
        //on se positionne sur l'url
        $crawler = $client->request('GET', '/tasks/create');

        $submitButton = $crawler->selectButton('Ajouter');
        $form = $submitButton->form();

        $form["task[title]"] = "Tache #test";
        $form["task[content]"] = "Contenu #test";

        //Soumettre le formulaire
        $client->submit($form);

        //On verify que tout est bien passé
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $container = static::getContainer();
        $taskRepository = $container->get(TaskRepository::class);
        $task = $taskRepository->findOneBy(['title' => 'Tache #test']);
        $this->assertEquals($task->getContent(), "Contenu #test");

    }

   //modifier une tache
   public function testEditTask()
   {
       $client = static::createClient();
       $user = $this->userRoleUser();
       $client->loginUser($user);

       $container = static::getContainer();
       $taskRepository = $container->get(TaskRepository::class);
       $task = $taskRepository->findOneBy(['owner' => $user]);

       //on se positionne sur l'url
       $crawler = $client->request('GET', "/tasks/ " . $task->getId() . "/edit");

       $submitButton = $crawler->selectButton('Modifier');
       $form = $submitButton->form();

       $form["task[title]"] = "Tache modifie";
       $form["task[content]"] = "contenu modifie";

       //Soumettre le formulaire
       $client->submit($form);
       //On verify que tout est bien passé
       $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

       $container = static::getContainer();
       $taskRepository = $container->get(TaskRepository::class);
       $task = $taskRepository->findOneBy(['title' => 'Tache modifie']);
       $this->assertEquals($task->getContent(), "contenu modifie");
   }

    //modifier une tache
    public function testEditWrongTask()
    {
        $client = static::createClient();
        $userTask = $this->userAnonyme();
        $user = $this->userRoleUser();
        $client->loginUser($user);

        $container = static::getContainer();
        $taskRepository = $container->get(TaskRepository::class);
        $task = $taskRepository->findOneBy(['owner' => $userTask]);

        //on se positionne sur l'url
        $client->request('GET', "/tasks/ " . $task->getId() . "/edit");

        $client->followRedirect();

        $this->assertRouteSame("task_list");
    }

    public function testToggleTask()
    {
        $client = static::createClient();
        $user = $this->userRoleUser();
        $client->loginUser($user);

        $container = static::getContainer();
        $taskRepository = $container->get(TaskRepository::class);
        $task = $taskRepository->findOneBy(['isDone' => false]);

        //on se positionne sur l'url
        $client->request('GET', "/tasks/ " . $task->getId() . "/toggle");

        $container = static::getContainer();
        $taskRepository = $container->get(TaskRepository::class);
        $task = $taskRepository->find($task->getId());
        $this->assertEquals($task->getIsDone(), true);
    }

    public function testRemoveTask()
    {
        $client = static::createClient();
        $user = $this->userRoleUser();
        $client->loginUser($user);

        $container = static::getContainer();
        $taskRepository = $container->get(TaskRepository::class);
        $task = $taskRepository->findOneBy(['owner' => $user]);
        $idTask = $task->getId();

        //on se positionne sur l'url
        $client->request('GET', "/tasks/ " . $task->getId() . "/delete");

        $container = static::getContainer();
        $taskRepository = $container->get(TaskRepository::class);
        $task = $taskRepository->find($idTask);
        $this->assertEquals($task, null);
    }

    public function testRemoveTaskFail()
    {
        $client = static::createClient();
        $userTask = $this->userAnonyme();
        $user = $this->userRoleUser();
        $client->loginUser($user);

        $container = static::getContainer();
        $taskRepository = $container->get(TaskRepository::class);
        $task = $taskRepository->findOneBy(['owner' => $userTask]);

        //on se positionne sur l'url
        $client->request('GET', "/tasks/ " . $task->getId() . "/delete");

        $container = static::getContainer();
        $taskRepository = $container->get(TaskRepository::class);
        $task = $taskRepository->find($task->getId());
        $this->assertEquals($task, $task);
    }

    public function testAssignEmptyOwnerTask()
    {
        $client = static::createClient();
        $userTask = $this->userAnonyme();
        $user = $this->userRoleUser();
        $client->loginUser($user);

        $container = static::getContainer();
        $taskRepository = $container->get(TaskRepository::class);
        $task = $taskRepository->findOneBy(['owner' => $userTask]);

        //on se positionne sur l'url
        $client->request('GET', "/tasks/assign-anonymous");

        $container = static::getContainer();
        $taskRepository = $container->get(TaskRepository::class);
        $task = $taskRepository->findOneBy(['owner' => null]);
        $this->assertEquals($task, null);
    }


    public function testEditWrongUserTask()
    {
        $client = static::createClient();
        $user = $this->userAnonyme();
        $userTask = $this->userRoleUser();
        $client->loginUser($user);

        $container = static::getContainer();
        $taskRepository = $container->get(TaskRepository::class);
        $task = $taskRepository->findOneBy(['owner' => $userTask]);

        //on se positionne sur l'url
        $client->request('GET', "/tasks/ " . $task->getId() . "/edit");

        $client->followRedirect();

        $this->assertRouteSame("task_list");
    }
}
