<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class ProductController extends AbstractController
{
    /**
     * @Route("/product/show/{id}", name="product_show")
     * @param Product|null $product
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function show(Product $product = null, Request $request): Response
    {

        if($product === null){
            $this->addFlash('danger', 'Le produit n\'a pas été trouvé !');
            return $this->redirectToRoute('index');
        }

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $em = $this->getDoctrine()->getManager();
            $picture = $form->get('picture')->getData();
            if ($picture) {
                $newFilename = uniqid().'.'.$picture->guessExtension();
                try {
                    $picture->move(
                        $this->getParameter('upload_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Impossible d\'uploader le fichier !');
                }
                $product->setPicture($newFilename);
            }
            $em->persist($product);
            $em->flush();
            $this->addFlash(
                'success',
                'Le produit a bien été modifié'
            );
        }
        return $this->render('product/show.html.twig',[
            'product' => $product,
            'modif' => $form->createView()
        ]);
    }

    /**
     * @Route("product/delete/{id}", name="product_delete")
     * @param Product|null $product
     * @return RedirectResponse
     */
    public function delete(Product $product=null): RedirectResponse
    {
        if($product === null){
            $this->addFlash('danger', 'Le produit n\'a pas été trouvé');
            return $this->redirectToRoute('produit');
        } else {
            $em = $this->getDoctrine()->getManager();
            $em->remove($product);
            $em->flush();
            $this->addFlash('success', 'Le produit à bien été supprimé');
            return $this->redirectToRoute('index');
        }
    }

    /**
     * @Route("/product/add", name="product_add")
     * @param Request $request
     * @return Response
     */
    public function addProduct(Request $request):Response
    {
        $product = new Product();
        $em = $this->getDoctrine()->getManager();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $picture = $form->get('picture')->getData();
            if ($picture) {
                $newFilename = uniqid().'.'.$picture->guessExtension();
                try {
                    $picture->move(
                        $this->getParameter('upload_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Impossible d\'uploader le fichier !');
                }
                $product->setPicture($newFilename);
            }
            $em->persist($product);
            $em->flush();
            $this->addFlash('success', 'Le produit à bien été ajouté !');
            return $this->redirectToRoute('index');
        }
        return $this->render('product/add.html.twig', [
            'form' => [
                'add' => $form->createView()
            ]
        ]);
    }
}