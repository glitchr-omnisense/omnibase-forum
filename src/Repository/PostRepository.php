<?php

namespace Base\Forum\Repository;

use App\Entity\User;
use Base\Database\Repository\ServiceEntityRepository;
use Base\Forum\Entity\Post;
use Base\Forum\Entity\Topic;
use Base\Database\Repository\Finder\Criterion;
use Base\Database\Repository\Finder\Group;
use Base\Database\Repository\Finder\Key;
use Base\Database\Repository\Finder\Parser;

/**
 * @method Post|null find($id, $lockMode = null, $lockVersion = null)
 * @method Post|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method Post[]    findAll()
 * @method Post[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class PostRepository extends ServiceEntityRepository
{
    /** A topic's posts in reading order (Paginator::paginate($posts, $posts->ofTopic($topic), PostRepository::READING_ORDER, ...)). */
    public const READING_ORDER = ['createdAt' => 'ASC', 'id' => 'ASC'];

    /** @return array<int|string, mixed> the criteria of a topic's posts: the topic compared by its key */
    public function ofTopic(Topic $topic): array
    {
        return ['topic' => Key::of($topic)];
    }

    /**
     * The whole topic, light: one row of values per post, in reading order, for
     * the timeline and the posts' own hierarchy (TopicController::outline()).
     * No content, no entities - a topic of a thousand posts stays cheap.
     *
     * @return list<array{id: int, at: \DateTimeInterface, replyTo: ?int, deletedAt: ?\DateTimeInterface, authorId: ?int, author: ?string}>
     */
    public function findOutline(Topic $topic): array
    {
        $rows = $this->findValuesBy(
            ['id', 'at' => 'createdAt', 'replyTo', 'deletedAt', 'authorId' => 'author', 'author' => 'author.username'],
            ['topic' => Key::of($topic)],
            self::READING_ORDER,
        );

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'at' => self::moment($row['at']),
            'replyTo' => null === $row['replyTo'] ? null : (int) $row['replyTo'],
            'deletedAt' => self::moment($row['deletedAt']),
            'authorId' => null === $row['authorId'] ? null : (int) $row['authorId'],
            'author' => null === $row['author'] ? null : (string) $row['author'],
        ], $rows);
    }

    /** A moment as a row of values holds it (a date, or its text): a date, null for none. */
    private static function moment(mixed $value): ?\DateTimeInterface
    {
        return $value instanceof \DateTimeInterface || null === $value ? $value : new \DateTimeImmutable((string) $value);
    }

    /** The member's most recent post, for the flood check. */
    public function findLastByAuthor(User $author): ?Post
    {
        return $this->findOneBy(['author' => Key::of($author)], ['createdAt' => 'DESC']);
    }

    /** Which page of a topic a post lands on, for permalinks. */
    public function findPageOf(Post $post, int $perPage): int
    {
        // The posts before it in reading order: `createdAt < :at OR (createdAt = :at AND id < :id)`.
        $before = $this->count([
            'topic' => Key::of($post->getTopic()),
            Group::of(Parser::SEPARATOR_OR,
                Criterion::of('createdAt', $post->getCreatedAt(), Parser::OPTION_LOWER),
                Group::of(Parser::SEPARATOR_AND,
                    Criterion::of('createdAt', $post->getCreatedAt()),
                    Criterion::of('id', $post->getId(), Parser::OPTION_LOWER),
                ),
            ),
        ]);

        return intdiv($before, max(1, $perPage)) + 1;
    }

    /** How many posts a member wrote - the profile's "Messages sur le forum". */
    public function countByAuthor(User $author): int
    {
        return $this->count([
            'author' => Key::of($author),
            Group::of(Parser::SEPARATOR_AND, Criterion::of('deletedAt', null, Parser::OPTION_NULL)),
        ]);
    }

    /**
     * The ids of the topics a post of which holds the term (lower-cased, its % already around it),
     * any post, deleted ones included, as the topic search's join on the posts read them.
     *
     * @return list<int>
     */
    public function topicIdsWithContentLike(string $like): array
    {
        $ids = [];
        foreach ($this->findBy([Group::of(Parser::SEPARATOR_AND, Criterion::of('content', $like, Parser::OPTION_INSENSITIVE, Parser::OPTION_PARTIAL))]) as $post) {
            if (null !== $id = $post->getTopic()?->getId()) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }
}
