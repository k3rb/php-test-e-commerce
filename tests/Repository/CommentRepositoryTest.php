<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Model\Comment;
use App\Repository\CommentRepository;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CommentRepositoryTest extends TestCase
{
    private PDO $connection;
    private CommentRepository $repository;

    protected function setUp(): void
    {
        $dsn = getenv('DATABASE_DSN');
        $user = getenv('DATABASE_USER');
        $password = getenv('DATABASE_PASSWORD');

        if (false === $dsn || false === $user || false === $password) {
            throw new RuntimeException('Lancez ces tests dans le conteneur app avec Docker Compose.');
        }

        $this->connection = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        // La table temporaire masque la table de l'application pendant ce test.
        $this->connection->exec('CREATE TEMP TABLE comments (LIKE public.comments INCLUDING ALL)');
        $this->repository = new CommentRepository($this->connection);
    }

    public function testFindByProductIdReturnsOnlyItsCommentsNewestFirst(): void
    {
        $this->repository->create(new Comment(1, 'Premier', 'Premier avis'));
        $this->repository->create(new Comment(2, 'Autre produit', 'Un autre avis'));
        $this->repository->create(new Comment(1, 'Dernier', 'Dernier avis'));

        $comments = $this->repository->findByProductId(1);

        self::assertCount(2, $comments);
        self::assertSame(['Dernier', 'Premier'], array_map(
            static fn (Comment $comment): ?string => $comment->author,
            $comments,
        ));
        self::assertSame([1, 1], array_map(
            static fn (Comment $comment): int => $comment->productId,
            $comments,
        ));
        self::assertNotNull($comments[0]->id);
        self::assertNotNull($comments[0]->createdAt);
    }

    public function testFindByIdReturnsTheStoredCommentOrNull(): void
    {
        $this->repository->create(new Comment(7, 'Rick', 'Wubba lubba dub dub'));
        $id = $this->lastInsertedId();

        $comment = $this->repository->findById($id);

        self::assertNotNull($comment);
        self::assertSame($id, $comment->id);
        self::assertSame(7, $comment->productId);
        self::assertSame('Rick', $comment->author);
        self::assertSame('Wubba lubba dub dub', $comment->content);
        self::assertNull($this->repository->findById($id + 1));
    }

    public function testUpdateAndDeletePersistTheirChanges(): void
    {
        $this->repository->create(new Comment(3, 'Morty', 'Ancien avis'));
        $id = $this->lastInsertedId();
        $comment = $this->repository->findById($id);
        self::assertNotNull($comment);

        $comment->author = 'Morty Smith';
        $comment->content = 'Avis modifié';
        $this->repository->update($comment);

        $updated = $this->repository->findById($id);
        self::assertNotNull($updated);
        self::assertSame('Morty Smith', $updated->author);
        self::assertSame('Avis modifié', $updated->content);

        $this->repository->delete($updated);

        self::assertNull($this->repository->findById($id));
        self::assertSame([], $this->repository->findByProductId(3));
    }

    private function lastInsertedId(): int
    {
        return (int) $this->connection
            ->query('SELECT id FROM comments ORDER BY id DESC LIMIT 1')
            ->fetchColumn();
    }
}
