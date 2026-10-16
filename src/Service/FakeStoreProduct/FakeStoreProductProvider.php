<?php

declare(strict_types=1);

namespace App\Service\FakeStoreProduct;

use App\Model\Product;
use App\Service\FakeStoreProduct\Exception\ProductNotFoundException;
use App\Service\FakeStoreProduct\Exception\ProductProviderException;
use JsonException;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use UnexpectedValueException;

final readonly class FakeStoreProductProvider
{
    public function __construct(private HttpClientInterface $client)
    {
    }

    /**
     * @return list<Product>
     */
    public function findAll(): array
    {
        try {
            $data = $this->client
                ->request('GET', '/products', [
                    'headers' => ['Accept' => 'application/json'],
                ])
                ->toArray();

            if (!array_is_list($data)) {
                throw new UnexpectedValueException('La réponse de Fake Store API n’est pas une liste.');
            }

            foreach ($data as $product) {
                if (!is_array($product)) {
                    throw new UnexpectedValueException('Un produit retourné par Fake Store API est invalide.');
                }
            }

            return array_map(
                static fn (array $product): Product => Product::fromArray($product),
                $data,
            );
        } catch (
            TransportExceptionInterface
            | HttpExceptionInterface
            | DecodingExceptionInterface
            | UnexpectedValueException $exception
        ) {
            throw new ProductProviderException(
                'Le catalogue est temporairement indisponible.',
                previous: $exception,
            );
        }
    }

    public function find(int $id): Product
    {
        try {
            $response = $this->client->request('GET', sprintf('/products/%d', $id), [
                'headers' => ['Accept' => 'application/json'],
            ]);

            if (404 === $response->getStatusCode()) {
                throw new ProductNotFoundException(sprintf('Le produit %d n’existe pas.', $id));
            }

            if ($response->getStatusCode() >= 400) {
                $response->getContent();
            }

            $content = $response->getContent(false);

            if ('' === trim($content) || 'null' === trim($content)) {
                throw new ProductNotFoundException(sprintf('Le produit %d n’existe pas.', $id));
            }

            $data = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

            if (!is_array($data)) {
                throw new UnexpectedValueException('La réponse de Fake Store API n’est pas un produit.');
            }

            return Product::fromArray($data);
        } catch (ProductNotFoundException $exception) {
            throw $exception;
        } catch (
            TransportExceptionInterface
            | HttpExceptionInterface
            | JsonException
            | UnexpectedValueException $exception
        ) {
            throw new ProductProviderException(
                'La fiche produit est temporairement indisponible.',
                previous: $exception,
            );
        }
    }
}
