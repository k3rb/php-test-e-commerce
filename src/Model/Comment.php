<?php

declare(strict_types=1);

namespace App\Model;

use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

final class Comment
{
    public function __construct(
        public readonly int $productId,
        #[Assert\NotBlank(message: 'Veuillez renseigner votre nom.')]
        #[Assert\Length(max: 100, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.')]
        public ?string $author = '',
        #[Assert\NotBlank(message: 'Veuillez saisir un commentaire.')]
        #[Assert\Length(max: 2000, maxMessage: 'Le commentaire ne peut pas dépasser {{ limit }} caractères.')]
        public ?string $content = '',
        public readonly ?int $id = null,
        public readonly ?DateTimeImmutable $createdAt = null,
        public readonly ?DateTimeImmutable $updatedAt = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * Comme pas d'orm on fait une petite methode pour me mapping entre snake/camel
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productId: (int) $data['product_id'],
            author: (string) $data['author'],
            content: (string) $data['content'],
            id: (int) $data['id'],
            createdAt: new DateTimeImmutable((string) $data['created_at']),
            updatedAt: new DateTimeImmutable((string) $data['updated_at']),
        );
    }
}
