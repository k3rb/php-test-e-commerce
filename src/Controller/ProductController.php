<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\CommentStorageException;
use App\Form\CommentType;
use App\Model\Comment;
use App\Repository\CommentRepository;
use App\Service\FakeStoreProduct\Exception\ProductNotFoundException;
use App\Service\FakeStoreProduct\Exception\ProductProviderException;
use App\Service\FakeStoreProduct\FakeStoreProductProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{

    #[Route('/', name: 'app_product_list', methods: ['GET'])]
    public function index(FakeStoreProductProvider $productProvider): Response
    {
        try {
            $products = $productProvider->findAll();
        } catch (ProductProviderException $exception) {
            return $this->render('home/index.html.twig', [
                'products' => [],
                'error' => $exception->getMessage(),
            ], new Response(status: Response::HTTP_SERVICE_UNAVAILABLE));
        }

        return $this->render('product/list.html.twig', [
            'products' => $products,
            'error' => null,
        ]);
    }

    #[Route('/products/{id}', name: 'app_product_show', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function show(
        int                      $id,
        Request                  $request,
        FakeStoreProductProvider $productProvider,
        CommentRepository        $commentRepository,
    ): Response
    {
        try {
            $product = $productProvider->find($id);
        } catch (ProductNotFoundException $exception) {
            throw $this->createNotFoundException('Ce produit n’existe pas.', $exception);
        } catch (ProductProviderException $exception) {
            return $this->render('product/show.html.twig', [
                'product' => null,
                'error' => $exception->getMessage(),
                'comments' => [],
                'commentForm' => null,
                'commentError' => null,
            ], new Response(status: Response::HTTP_SERVICE_UNAVAILABLE));
        }

        /**
         * Pour moi ici, ce n'est pas ce que je devrais faire dans un projet moderne.
         * Je veux garder la simplicité du test et ne pas mettre d'ajax avec une API REST mais c'est dur de pas le faire.
         * Pour moi dans ce controller, on ne devrait avoir que du code lié a Product mais comme on gère de l'écran et pas un endpoint
         * Je laisse comme ça
         */

        $comment = new Comment(productId: $product->id);
        $commentForm = $this->createForm(CommentType::class, $comment);
        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            try {
                $commentRepository->create($comment);
                $this->addFlash('success', 'Votre commentaire a bien été ajouté.');

                return $this->redirectToRoute('app_product_show', [
                    'id' => $product->id,
                    '_fragment' => 'comments',
                ]);
            } catch (CommentStorageException $exception) {
                $commentForm->addError(new FormError($exception->getMessage()));
            }
        }

        $commentError = null;
        try {
            $comments = $commentRepository->findByProductId($product->id);
        } catch (CommentStorageException $exception) {
            $comments = [];
            $commentError = $exception->getMessage();
        }

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'error' => null,
            'comments' => $comments,
            'commentForm' => $commentForm,
            'commentError' => $commentError,
        ]);
    }
}
