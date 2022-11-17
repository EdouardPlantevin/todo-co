<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\User;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tasks')]
class TaskController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager
    ){}

    #[Route('', name: 'task_list')]
    public function index(TaskRepository $taskRepository): Response
    {
        return $this->render('task/list.html.twig', [
            'tasks' => $taskRepository->findAll()
        ]);
    }

    #[Route('/create', name: 'task_create')]
    public function createAction(Request $request): Response
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            /* Add User */
            $task->setOwner($this->getUser());

            $this->manager->persist($task);
            $this->manager->flush();

            $this->addFlash('success', 'La tâche a été bien été ajoutée.');

            return $this->redirectToRoute('task_list');
        }

        return $this->render('task/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}/edit', name: 'task_edit')]
    public function editAction(Task $task, Request $request): Response
    {

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->manager->flush();

            $this->addFlash('success', 'La tâche a bien été modifiée.');

            return $this->redirectToRoute('task_list');
        }

        return $this->render('task/edit.html.twig', [
            'form' => $form->createView(),
            'task' => $task
        ]);
    }

    #[Route('/{id}/toggle', name: 'task_toggle')]
    public function toggleTaskAction(Task $task): Response
    {
        $task->setIsDone(!$task->getIsDone());

        $this->manager->flush();

        $this->addFlash('success', sprintf('La tâche %s a bien été marquée comme faite.', $task->getTitle()));

        return $this->redirectToRoute('task_list');
    }

    #[Route('/{id}/delete', name: 'task_delete')]
    public function deleteTaskAction(Task $task): Response
    {
        $this->manager->remove($task);
        $this->manager->flush();

        $this->addFlash('success', 'La tâche a bien été supprimée.');

        return $this->redirectToRoute('task_list');
    }

    #[Route('/assign-anonymous', name: 'task_assign_anonymous')]
    public function taskAnonymous(TaskRepository $taskRepository, UserRepository $userRepository, UserPasswordHasherInterface $hasher): Response
    {
        $user = $userRepository->findOneBy(['username' => 'anonyme']);

        if (!$user) {
            $user = new User();

            $password = $hasher->hashPassword(
                $user,
                'password'
            );

            $user->setEmail('anonyme@anonyme.com')
                ->setUsername('anonyme')
                ->setPassword($password);

            $this->manager->persist($user);
        }

        $tasks = $taskRepository->findBy(['owner' => null]);

        foreach ($tasks as $task) {
            $task->setOwner($user);
        }

        $this->manager->flush();

        $this->addFlash("success", "Modification enregistrée");

        return $this->redirectToRoute('task_list');
    }
}