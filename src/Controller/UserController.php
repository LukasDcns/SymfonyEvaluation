<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserFormType;
use App\Repository\UserRepository;
use App\Repository\CartRepository;
use App\Repository\CartContentRepository;
use Cassandra\Type\UserType;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    /**
     * @Route("/user/{id}", name="userDetails")
     * @param User $user
     * @param Request $request
     * @return Response
     */
    public function index(User $user, Request $request, CartRepository $cartRepository, CartContentRepository $cartContentRepository): Response
    {
        if ($user === null){
            return $this->redirectToRoute('index');
        }

        $form = $this->createForm(UserFormType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();
                $this->addFlash('success','L\'utilisateur a bien été modifié !');
            } catch (Exception $e) {
                $this->addFlash('danger', 'L\'utilisateur n\'a pas pu être modifié !');
                return $this->redirectToRoute('index');
            }
        }

        return $this->render('user/details.html.twig', [
            'user' => [
                'informations' => $user,
                'form' => $form->createView(),
                'orders' => $cartRepository->findBy(['user' => $user, 'paid' => true])
            ]
        ]);
    }
}
