<?php

declare(strict_types=1);

namespace App\Model;

use UnexpectedValueException;

/**
 * Dans ce projet, je met le product "DTO" dans les model du projet.
 * C'est la solution la plus simple et pragmatique dans ce contexte.
 */
final readonly class Product
{
    public function __construct(
        public int $id,
        public string $title,
        public float $price,
        public string $description,
        public string $category,
        public string $image,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (
            !isset(
                $data['id'],
                $data['title'],
                $data['price'],
                $data['description'],
                $data['category'],
                $data['image'],
            )
            || !is_int($data['id'])
            || !is_string($data['title'])
            || (!is_int($data['price']) && !is_float($data['price']))
            || !is_string($data['description'])
            || !is_string($data['category'])
            || !is_string($data['image'])
        ) {
            throw new UnexpectedValueException('Le format d’un produit retourné par Fake Store API est invalide.');
        }

        return new self(
            id: $data['id'],
            title: $data['title'],
            price: (float) $data['price'],
            description: $data['description'],
            category: $data['category'],
            image: $data['image'],
        );
    }
}
