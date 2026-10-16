<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\FakeStoreProduct\Exception\ProductNotFoundException;
use App\Service\FakeStoreProduct\Exception\ProductProviderException;
use App\Service\FakeStoreProduct\FakeStoreProductProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class FakeStoreProductProviderTest extends TestCase
{
    private const PRODUCT = [
        'id' => 1,
        'title' => 'Sac à dos',
        'price' => 109.95,
        'description' => 'Description du produit',
        'category' => 'accessoires',
        'image' => 'https://example.com/bag.png',
    ];

    public function testFindReturnsTheProductProvidedByTheApi(): void
    {
        $response = new MockResponse(json_encode(self::PRODUCT, JSON_THROW_ON_ERROR));
        $provider = new FakeStoreProductProvider(new MockHttpClient($response));

        $product = $provider->find(1);

        self::assertSame(1, $product->id);
        self::assertSame('Sac à dos', $product->title);
        self::assertSame(109.95, $product->price);
    }

    public function testFindAllReturnsTheProductsProvidedByTheApi(): void
    {
        $response = new MockResponse(json_encode([
            self::PRODUCT,
            array_replace(self::PRODUCT, ['id' => 2, 'title' => 'Second produit']),
        ], JSON_THROW_ON_ERROR));
        $provider = new FakeStoreProductProvider(new MockHttpClient($response));

        $products = $provider->findAll();

        self::assertCount(2, $products);
        self::assertSame('Sac à dos', $products[0]->title);
        self::assertSame(2, $products[1]->id);
    }

    public function testFindReportsAnUnknownProduct(): void
    {
        $provider = new FakeStoreProductProvider(new MockHttpClient(
            new MockResponse('{"error":"not found"}', ['http_code' => 404]),
        ));

        $this->expectException(ProductNotFoundException::class);

        $provider->find(999);
    }

    public function testFindAllRejectsAnInvalidCatalogueResponse(): void
    {
        $provider = new FakeStoreProductProvider(new MockHttpClient(
            new MockResponse('{"id":1}'),
        ));

        $this->expectException(ProductProviderException::class);

        $provider->findAll();
    }

    public function testFindAllReportsANetworkFailure(): void
    {
        $client = new MockHttpClient(static function (): MockResponse {
            throw new TransportException('Connexion impossible.');
        });
        $provider = new FakeStoreProductProvider($client);

        $this->expectException(ProductProviderException::class);

        $provider->findAll();
    }

    public function testFindDoesNotMistakeAnEmptyServerErrorForAMissingProduct(): void
    {
        $provider = new FakeStoreProductProvider(new MockHttpClient(
            new MockResponse('', ['http_code' => 500]),
        ));

        $this->expectException(ProductProviderException::class);

        $provider->find(1);
    }
}
