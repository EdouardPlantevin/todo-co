<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
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

    public function testPageUsers()
    {
        $client = static::createClient();
        $user = $this->userRoleAdmin();
        $client->loginUser($user);

        $client->request('GET', '/users');

        $this->assertResponseIsSuccessful();
    }

    public function testCreateUser()
    {
        $client = static::createClient();
        $user = $this->userRoleAdmin();
        $client->loginUser($user);
        //on se positionne sur l'url
        $crawler = $client->request('GET', '/users/create');

        $submitButton = $crawler->selectButton('Ajouter');
        $form = $submitButton->form();

        $form["user[username]"] = "User test #1";
        $form["user[password][first]"] = "password";
        $form["user[password][second]"] = "password";
        $form["user[email]"] = "usertest@test.com";

        //Soumettre le formulaire
        $client->submit($form);

        //On verify que tout est bien passé
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);
        $userCreate = $userRepository->findOneBy(['username' => 'User test #1']);
        $this->assertEquals($userCreate->getEmail(), "usertest@test.com");
    }

    public function testEditUser()
    {
        $client = static::createClient();
        $user = $this->userRoleAdmin();
        $client->loginUser($user);

        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);
        $user = $userRepository->findOneBy(['username' => 'anonyme']);

        //on se positionne sur l'url
        $crawler = $client->request('GET', "/users/ " . $user->getId() . "/edit");


        $submitButton = $crawler->selectButton('Modifier');
        $form = $submitButton->form();

        $form["user[username]"] = "User anonyme #1";
        $form["user[password][first]"] = "password";
        $form["user[password][second]"] = "password";
        $form["user[email]"] = "useranonyme@test.com";

        $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $container = static::getContainer();
        $userRepository = $container->get(UserRepository::class);
        $userCreate = $userRepository->findOneBy(['username' => 'User anonyme #1']);
        $this->assertEquals($userCreate->getEmail(), "useranonyme@test.com");


    }
}
