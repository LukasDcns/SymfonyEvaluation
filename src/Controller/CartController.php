<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Entity\Cart;
use App\Entity\CartContent;
use App\Repository\CartContentRepository;
use App\Repository\CartRepository;
use App\Repository\UserRepository;
use App\Repository\ProductRepository;
use DateTime;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;


class CartController extends AbstractController{
    /**
     * @Route("/cart/add/{id}", name="add_product_cartcontent")
     * @param int $id
     * @param UserRepository $userRepository
     * @param CartRepository $cartRepository
     * @param ProductRepository $productRepository
     * @param Request $request
     * @param CartContentRepository $cartContentRepository
     * @return Response
     */
    public function addToCart(int $id, UserRepository $userRepository, CartRepository $cartRepository, ProductRepository $productRepository, Request $request, CartContentRepository $cartContentRepository)
    {
        if ($this->getUser()) {
            $em = $this->getDoctrine()->getManager();

            $product = $productRepository->findOneBy(['id' => $id]);
            $user = $userRepository->findOneBy(['id' => $this->getUser()->getId()]);
            $searchCarts = $cartRepository->findBy(['user' => $user]);

            if ($request->get('quantity') > $product->getStock()) {
                $this->addFlash('danger', 'La quantité choisie est trop élevée !');
            } else {
                $quantity = (int)$request->get('quantity');
                $cartPaid = 0;
                if ($searchCarts) {
                    for ($i = 0; $i < count($searchCarts); $i++) {
                        if ($searchCarts[$i]->getPaid()) {
                            $cartPaid++;
                        }
                    }
                }
                if (!$searchCarts || $cartPaid === count($searchCarts)) {
                    $cart = new Cart();
                    $cart->setUser($user);
                    $cart->setPaid(0);
                    $cart->setPurchaseDate(new DateTime('now'));
                    $em->persist($cart);
                    $em->flush();
                }


                $searchCart = $cartRepository->findBy(['user' => $user->getId(), 'paid' => false])[0];
                $searchContentCart = $cartContentRepository->findOneBy(['cart' => $searchCart]);

                if ($searchContentCart && $product === $searchContentCart->getProduct()) {
                    $searchContentCart->setQuantity($searchContentCart->getQuantity() + $quantity);
                    $em->persist($searchContentCart);
                    $em->flush();
                } else {
                    $cartContent = new CartContent();
                    $cartContent->setAddedDate(new DateTime('now'));
                    $cartContent->setQuantity($quantity);
                    $cartContent->setCart($searchCart);
                    $cartContent->setProduct($product);
                    $em->persist($cartContent);
                    $em->flush();
                }
                $this->addFlash('success', 'Le produit à bien été ajouté au panier !');
                return $this->redirectToRoute('index');
            }
        } else {
            $this->addFlash('danger', 'Vous devez être connecté pour ajouter un article au panier !');
            return $this->redirectToRoute('login');
        }
    }

    /**
     * @Route("/cart", name="cart")
     * @param CartRepository $cartRepository
     * @param UserRepository $userRepository
     * @param CartContentRepository $cartContentRepository
     * @return Response
     */
    public function cart(CartRepository $cartRepository, UserRepository $userRepository, CartContentRepository $cartContentRepository, ProductRepository $productRepository): Response
    {
        $userId = $this->getUser()->getId();
        $cart = $cartRepository->findBy(['user' => $userId, 'paid' => false]);

        if ($cart) {
            $cart = $cart[0];
             if ($cart->getPaid() === false) {
                 $cartContent = $cartContentRepository->findAllByCartId($cart->getId());
                 if (!$cartContent) {
                     $this->addFlash('warning', 'Vous n\'avez aucun produit dans votre panier !');
                     return $this->redirectToRoute('index');
                 }
             }

            return $this->render('cart/index.html.twig', [
                'cart' => $cart,
                'cartContent' => $cartContent
            ]);
        } else {
           return $this->redirectToRoute('index');
        }
    }

    /**
     * @Route("/cart/delete/item/{id}", name="deleteCartContentItem")
     * @param CartContent|null $cartContent
     * @return RedirectResponse
     */
    public function deleteCartContentItem(CartContent $cartContent = null): Response
    {
        if($cartContent === null){
            $this->addFlash('danger', 'Le produit n\'a pas été trouvé dans le panier !');
            return $this->redirectToRoute('cart');
        } else {
            $em = $this->getDoctrine()->getManager();
            $em->remove($cartContent);
            $em->flush();
            $this->addFlash('success', 'Le produit à bien été supprimé du panier');
            return $this->redirectToRoute('cart');
        }
    }

    /**
     * @Route("/cart/paid/cart/{id}", name="paidCart")
     * @param Cart|null $cart
     * @param CartRepository $cartRepository
     * @return RedirectResponse
     */
    public function paidCart(Cart $cart = null, CartRepository $cartRepository)
    {
        $em = $this->getDoctrine()->getManager();
        try {
            $cart = $cartRepository->findOneBy(['id' => $cart->getId()]);
            $cart->setPaid(1);
            $cart->setPurchaseDate(new DateTime("now"));
            $em->persist($cart);
            $em->flush();
            $this->addFlash('success', 'Votre panier a été payé !');
            return $this->redirectToRoute('index');
        } catch (Exception $e) {
            $this->addFlash('danger', 'Votre panier n\'a pas pu être payé !');
            return $this->redirectToRoute('cart');
        }
    }
}