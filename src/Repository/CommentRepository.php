<?php

declare(strict_types=1);

namespace App\Repository;

use App\Exception\CommentStorageException;
use App\Model\Comment;
use LogicException;
use PDO;
use PDOException;

final readonly class CommentRepository
{
    public function __construct(private PDO $connection)
    {
    }

    /**
     * @return list<Comment>
     */
    public function findByProductId(int $productId): array
    {
        try {
            $statement = $this->connection->prepare(
                'SELECT id, product_id, author, content, created_at, updated_at
                 FROM comments
                 WHERE product_id = :product_id
                 ORDER BY created_at DESC, id DESC'
            );
            $statement->execute(['product_id' => $productId]);

            return array_map(
                static fn (array $row): Comment => Comment::fromArray($row),
                $statement->fetchAll(),
            );
        } catch (PDOException $exception) {
            throw new CommentStorageException('Les commentaires sont temporairement indisponibles.', 0, $exception);
        }
    }

    public function findById(int $id): ?Comment
    {
        try {
            $statement = $this->connection->prepare(
                'SELECT id, product_id, author, content, created_at, updated_at
                 FROM comments
                 WHERE id = :id'
            );
            $statement->execute(['id' => $id]);
            $row = $statement->fetch();

            return false === $row ? null : Comment::fromArray($row);
        } catch (PDOException $exception) {
            throw new CommentStorageException('Les commentaires sont temporairement indisponibles.', 0, $exception);
        }
    }

    public function create(Comment $comment): void
    {
        try {
            $statement = $this->connection->prepare(
                'INSERT INTO comments (product_id, author, content)
                 VALUES (:product_id, :author, :content)'
            );
            $statement->execute([
                'product_id' => $comment->productId,
                'author' => $comment->author,
                'content' => $comment->content,
            ]);
        } catch (PDOException $exception) {
            throw new CommentStorageException('Le commentaire n’a pas pu être enregistré.', 0, $exception);
        }
    }

    public function update(Comment $comment): void
    {
        if (null === $comment->id) {
            throw new LogicException('Un commentaire sans identifiant ne peut pas être modifié.');
        }

        try {
            $statement = $this->connection->prepare(
                'UPDATE comments
                 SET author = :author, content = :content, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id AND product_id = :product_id'
            );
            $statement->execute([
                'id' => $comment->id,
                'product_id' => $comment->productId,
                'author' => $comment->author,
                'content' => $comment->content,
            ]);
        } catch (PDOException $exception) {
            throw new CommentStorageException('Le commentaire n’a pas pu être modifié.', 0, $exception);
        }
    }

    /**
     * @param Comment $comment
     * @return void
     *
     * Mon coeur balance entre delete avec une id ou avec un objet...
     * c'est un test POO alors je garde l'objet
     * Mais en regle général j'utilise l'orm en mode objet
     * et le repo pour opti avec des raccourcis
     */
    public function delete(Comment $comment): void
    {
        try {
            $statement = $this->connection->prepare(
                'DELETE FROM comments WHERE id = :id'
            );
            $statement->execute(['id' => $comment->id]);
        } catch (PDOException $exception) {
            throw new CommentStorageException('Le commentaire n’a pas pu être supprimé.', 0, $exception);
        }
    }
}
