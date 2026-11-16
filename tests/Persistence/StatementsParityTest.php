<?php

namespace Tests\Base\Forum\Persistence;

use Base\Entity\Thread\Tag;
use Base\Forum\Entity\Category;
use Base\Forum\Entity\Post;
use Base\Forum\Entity\Topic;
use Base\Forum\Repository\PostRepository;
use Base\Forum\Repository\TopicRepository;
use Base\Repository\ThreadIntlRepository;
use Base\Testing\Engines;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The forum's statements (Persistence\{Doctrine,Eloquent}\{Topic,Post}Statements) give the same rows on both
 * engines: the same repositories over the same SQLite rows, in the style of omnibridge/eloquent's
 * RepositoryParityTest. The rows are written column by column from the entities' description (no behaviour of
 * either engine involved), then read by each engine.
 */
final class StatementsParityTest extends TestCase
{
    private static ?Engines $engines = null;
    /** @var array<string, int> */
    private static array $ids = [];

    /** @return array<string, array{0: string}> */
    public static function backends(): array
    {
        return Engines::backends();
    }

    public static function setUpBeforeClass(): void
    {
        $engines = self::$engines = Engines::of([Topic::class, Post::class, Category::class, \App\Entity\User::class], ['project_dir' => \dirname(__DIR__, 2)]);

        $ann = self::row(\App\Entity\User::class, ['username' => 'ann', 'email' => 'ann@example.org', 'roles' => 'ROLE_USER']);
        $bob = self::row(\App\Entity\User::class, ['username' => 'bob', 'email' => 'bob@example.org', 'roles' => 'ROLE_USER']);
        $club = self::row(Category::class, ['title' => 'Club', 'slug' => 'club']);
        $hall = self::row(Category::class, ['title' => 'Hall', 'slug' => 'hall']);
        $plain = self::row(Tag::class, ['slug' => 'plain', 'priority' => 0]);
        $first = self::row(Tag::class, ['slug' => 'first', 'priority' => 5]);
        $topic = static fn (string $slug, int $category, int $replies, string $lastPostAt, ?string $publishedAt): int => self::row(Topic::class, [
            'slug' => $slug, 'category' => $category, 'replies' => $replies, 'views' => 0,
            'lastPostAt' => $lastPostAt, 'publishedAt' => $publishedAt, 'pinned' => false,
        ]);
        self::$ids = [
            'ann' => $ann, 'bob' => $bob, 'club' => $club, 'hall' => $hall, 'plain' => $plain, 'first' => $first,
            'quiet' => $topic('quiet', $club, 3, '2026-03-01 10:00:00', null),
            'busy' => $topic('busy', $club, 30, '2026-03-05 10:00:00', '2026-01-01 00:00:00'),
            'later' => $topic('later', $hall, 1, '2026-04-01 10:00:00', '2099-01-01 00:00:00'),
            'old' => $topic('old', $hall, 0, '2026-01-10 10:00:00', null),
        ];
        $tags = $engines->description()->entity(Topic::class)->relations['tags']->joinTable;
        \assert(null !== $tags);
        foreach ([['quiet', $plain], ['quiet', $first], ['busy', $plain], ['later', $first]] as [$thread, $tag]) {
            $engines->insert($tags->name, [$tags->joinColumns[0]->name => self::$ids[$thread], $tags->inverseJoinColumns[0]->name => $tag]);
        }
        self::$ids['p1'] = self::row(Post::class, ['topic' => self::$ids['quiet'], 'author' => $ann, 'content' => 'Hello', 'createdAt' => '2026-03-01 10:00:00']);
        self::$ids['p2'] = self::row(Post::class, ['topic' => self::$ids['quiet'], 'author' => $bob, 'replyTo' => self::$ids['p1'], 'content' => 'Hi', 'createdAt' => '2026-03-01 10:05:00']);
    }

    private static function engines(): Engines
    {
        return self::$engines ?? throw new \LogicException('setUpBeforeClass() did not run.');
    }

    /** @dataProvider backends */
    #[DataProvider('backends')]
    public function testTheTopicsStatements(string $backend): void
    {
        $finders = self::engines()->finders($backend);
        $posts = new PostRepository($finders);
        $topics = new TopicRepository($finders, new ThreadIntlRepository($finders), $posts);

        self::assertEquals([
            self::$ids['club'] => ['topics' => 2, 'replies' => 33],
            self::$ids['hall'] => ['topics' => 1, 'replies' => 0],
        ], self::sorted($topics->countPerCategory()), 'the scheduled topic out of the counts');
        self::assertSame([self::$ids['club'] => true], $topics->findHotCategoryIds(25));

        $last = array_map(static fn (Topic $topic): ?string => $topic->getSlug(), $topics->findLastPerCategory());
        self::assertSame([self::$ids['club'] => 'busy', self::$ids['hall'] => 'old'], self::sorted($last), 'the latest out topic of each board');

        $usage = array_map(static fn (array $row): array => [$row['tag']->getSlug(), $row['nb']], $topics->findTagUsage(30));
        self::assertSame([['first', 1], ['plain', 2]], $usage, 'highest priority first, the scheduled topic not counted');

        $quiet = $topics->find(self::$ids['quiet']);
        self::assertInstanceOf(Topic::class, $quiet);
        $topics->incrementViews($quiet);
        $topics->incrementViews($quiet);
        $views = self::engines()->pdo()->query('SELECT views FROM forum_topic WHERE id = '.self::$ids['quiet']);
        self::assertSame(2, false === $views ? null : (int) $views->fetchColumn(), 'twice, on this engine');
        self::engines()->pdo()->exec('UPDATE forum_topic SET views = 0');

        $outline = array_map(static fn (array $row): array => [
            $row['id'], $row['at'] instanceof \DateTimeInterface ? $row['at']->format('Y-m-d H:i') : $row['at'], $row['replyTo'], $row['deletedAt'], $row['authorId'], $row['author'],
        ], $posts->findOutline($quiet));
        self::assertSame([
            [self::$ids['p1'], '2026-03-01 10:00', null, null, self::$ids['ann'], 'ann'],
            [self::$ids['p2'], '2026-03-01 10:05', self::$ids['p1'], null, self::$ids['bob'], 'bob'],
        ], $outline);
    }

    /** @param array<int, mixed> $rows */
    private static function sorted(array $rows): array
    {
        ksort($rows);

        return $rows;
    }

    /**
     * One entity's rows, written column by column in each of its tables (JOINED: the root's first): the values given
     * (an association by its id), every other column NULL where it may be, else a neutral value of its type; the
     * discriminator both engines read.
     *
     * @param array<string, mixed> $values
     */
    private static function row(string $class, array $values): int
    {
        $engines = self::engines();
        $description = $engines->description();
        $entity = $description->entity($class);
        $chain = [];
        for ($c = $class; null !== $c; $c = $description->entity($c)->parent) {
            if (null !== $description->entity($c)->table) {
                array_unshift($chain, $c);
            }
        }
        $id = null;
        foreach ($chain as $index => $owner) {
            $row = null === $id ? [] : [$entity->fields[$entity->identifier[0]]->column => $id];
            foreach ($entity->fields as $property => $field) {
                if (!$field->id && self::owner($chain, $field->inheritedFrom ?? $class) === $owner) {
                    $row[$field->column] = \array_key_exists($property, $values) ? $values[$property] : ($field->nullable ? null : self::neutral($field->type, $property, $field->enum));
                }
            }
            foreach ($entity->relations as $property => $relation) {
                if ($relation->owning && 1 === \count($relation->joinColumns) && self::owner($chain, $relation->inheritedFrom ?? $class) === $owner) {
                    $row[$relation->joinColumns[0]->name] = $values[$property] ?? null;
                }
            }
            if (0 === $index && null !== ($inheritance = $entity->inheritance)) {
                $row[$inheritance->column->name] = $engines->discriminator($class);
            }
            $inserted = $engines->insert((string) $description->entity($owner)->table, $row);
            $id ??= $inserted;
        }

        return (int) $id;
    }

    /**
     * The class of the chain whose table holds a column inherited from $from: $from itself, or the first entity below
     * it (a mapped superclass's or a trait's column lives in the first table under it).
     *
     * @param list<string> $chain
     */
    private static function owner(array $chain, string $from): ?string
    {
        foreach ($chain as $class) {
            if ($class === $from || is_a($class, $from, true)) {
                return $class;
            }
        }

        return null;
    }

    private static function neutral(string $type, string $property, ?string $enum = null): mixed
    {
        // An enumeration or a set (MySQL's ENUM/SET refuse any other value): its first permitted value.
        // The column type of an omnibase enumeration or set (user_role, user_state, thread_state...): its class.
        $enum = \Base\Database\Schema\EntitySchema::types(['project_dir' => \dirname(__DIR__, 2)])[$type] ?? $enum;
        if (null !== $enum && method_exists($enum, 'getPermittedValues')) {
            $permitted = array_values($enum::getPermittedValues());
            if ([] !== $permitted) {
                return (string) $permitted[0];
            }
        }

        return match (true) {
            str_contains($type, 'int'), 'decimal' === $type, 'float' === $type, 'boolean' === $type => 0,
            str_contains($type, 'date') || str_contains($type, 'time') => '2026-01-01 00:00:00',
            str_contains($type, 'json') || str_contains($type, 'array') => '[]',
            default => $property.'-'.bin2hex(random_bytes(4)),
        };
    }
}
