<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SecurityControllerTest extends WebTestCase
{

    public function testIfLoginSuccessful(): void
    {
        $client = static::createClient();

        /** @var UrlGeneratorInterface $urlGenerator */
        $urlGenerator = $client->getContainer()->get("router");

        $crawler = $client->request('GET', $urlGenerator->generate('app_login'));
        $submitButton = $crawler->selectButton('Se connecter');
        $form = $submitButton->form();

        $form["username"] = "test";
        $form["password"] = "password";

        $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $client->followRedirect();

        $this->assertRouteSame("homepage");
    }

    public function testIfLoginFail(): void
    {
        $client = static::createClient();

        /** @var UrlGeneratorInterface $urlGenerator */
        $urlGenerator = $client->getContainer()->get("router");

        $crawler = $client->request('GET', $urlGenerator->generate('app_login'));
        $submitButton = $crawler->selectButton('Se connecter');
        $form = $submitButton->form();

        $form["username"] = "test";
        $form["password"] = "wrongpassword";

        $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $client->followRedirect();

        $this->assertSelectorTextContains('.alert.alert-danger', 'Identifiants invalides.');
        $this->assertRouteSame("app_login");
    }
}
