<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\CommentStorageException;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use App\Service\FakeStoreProduct\Exception\ProductNotFoundException;
use App\Service\FakeStoreProduct\Exception\ProductProviderException;
use App\Service\FakeStoreProduct\FakeStoreProductProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class CommentController extends AbstractController
{
    #[Route(
        '/comments/{id}/edit',
        name: 'app_comment_edit',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST'],
    )]
    /**
     * Dans un monde API j'aurais fait deux routes, une GET et une POST mais la en mode "MVC old school" ça va (selon moi)
     */
    public function edit(
        int                      $id,
        Request                  $request,
        FakeStoreProductProvider $productProvider,
        CommentRepository        $commentRepository,
    ): Response
    {
        try {
            $comment = $commentRepository->findById($id);
        } catch (CommentStorageException $exception) {
            throw new ServiceUnavailableHttpException(null, $exception->getMessage(), $exception);
        }

        if (null === $comment) {
            throw $this->createNotFoundException('Ce commentaire n’existe pas.');
        }

        try {
            $product = $productProvider->find($comment->productId);
        } catch (ProductNotFoundException $exception) {
            throw $this->createNotFoundException('Ce produit n’existe pas.', $exception);
        } catch (ProductProviderException $exception) {
            throw new ServiceUnavailableHttpException(null, $exception->getMessage(), $exception);
        }

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $commentRepository->update($comment);
                $this->addFlash('success', 'Le commentaire a bien été modifié.');

                return $this->redirectToRoute('app_product_show', [
                    'id' => $comment->productId,
                    '_fragment' => 'comments',
                ]);
            } catch (CommentStorageException $exception) {
                $form->addError(new FormError($exception->getMessage()));
            }
        }

        return $this->render('comment/edit.html.twig', [
            'product' => $product,
            'comment' => $comment,
            'form' => $form,
        ]);
    }

    #[Route(
        '/comments/{id}/delete',
        name: 'app_comment_delete',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
    )]
    /**
     * dans un mode API on serait sur une method DELETE
     */
    public function delete(
        int               $id,
        Request           $request,
        CommentRepository $commentRepository,
    ): Response
    {
        try {
            /**
             * On pourrait delete sans faire de recherche avant.. MAIS normalement, je devrais verifier une ou deux choses avant de delete
             * Comme est-ce que j'ai le droit de delete ce commentaire-la
             * Je garde pour le côté scolaire et pour la suite.
             */
            $comment = $commentRepository->findById($id);
        } catch (CommentStorageException $exception) {
            throw new ServiceUnavailableHttpException(null, $exception->getMessage(), $exception);
        }

        try {
            if ($comment) {//je delete que si le commentaire existe
                if (!$this->isCsrfTokenValid('delete-comment-' . $comment->id, $request->request->getString('_token'))) {
                    throw $this->createAccessDeniedException('Le jeton CSRF est invalide.');
                }

                $commentRepository->delete($comment);
            }
            /*
             * Si le commentaire n'existe pas je ne fait pas d'erreur pour ne pas paniqué le client
             * Par contre si on était en mode projet je loguerai a minima un warning pour surveiller qu'on ai pas une erreur
             */
            $this->addFlash('success', 'Le commentaire a bien été supprimé.');
        } catch (CommentStorageException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_product_show', [
            'id' => $comment->productId,
            '_fragment' => 'comments',
        ]);
    }
}
