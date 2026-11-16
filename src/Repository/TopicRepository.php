<?php

namespace Base\Forum\Repository;

use Base\Entity\Thread\Tag;
use Base\Forum\Entity\Category;
use Base\Forum\Entity\Topic;
use Base\Repository\ThreadRepository;
use Base\Database\Repository\Finder\Aggregate;
use Base\Database\Repository\Finder\Change;
use Base\Database\Repository\ServiceEntityRepository;
use Base\Database\Repository\Finder\Criterion;
use Base\Database\Repository\Finder\Group;
use Base\Database\Repository\Finder\Key;
use Base\Database\Repository\Finder\Parser;
use Base\Repository\ThreadTranslations;
use Base\Database\Repository\Finders;

/**
 * Extends ThreadRepository (not ServiceEntityRepository) on purpose: Thread
 * is #[Hierarchify], which requires HierarchifyTrait on the repository of
 * every subclass, and that trait comes with the parent.
 *
 * @method Topic|null find($id, $lockMode = null, $lockVersion = null)
 * @method Topic|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method Topic[]    findAll()
 * @method Topic[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class TopicRepository extends ThreadRepository
{
    /**
     * The search reads the titles in the translations and the posts' contents in their own
     * repositories (searchCriteria()).
     */
    public function __construct(
        Finders $finders,
        private readonly ThreadTranslations $translations,
        private readonly PostRepository $posts,
    ) {
        parent::__construct($finders);
    }

    /**
     * Only the topics that are out: a scheduled one (Topic::schedule()) stays
     * out of every list until its hour. Every query below that lists topics
     * goes through here - a list that forgot it would give the topic away
     * before its time, on the home page as much as on the BBS.
     *
     * The criterion `publishedAt IS NULL OR publishedAt <= now`, an entry of a criteria array.
     */
    public function out(): Group
    {
        return Group::of(Parser::SEPARATOR_OR,
            Criterion::of('publishedAt', null, Parser::OPTION_NULL),
            Criterion::of('publishedAt', new \DateTime(), Parser::OPTION_LOWER_EQUAL),
        );
    }

    /**
     * A listing's order: pinned first, then by last activity. Soft-deleted
     * topics are filtered by base-bundle's Trasheable filter, so nothing to
     * do here about them.
     */
    public const LIST_ORDER = ['pinned' => 'DESC', 'lastPostAt' => 'DESC', 'createdAt' => 'DESC'];

    /** The front page's order: latest activity, never pinned first (that only makes sense inside a board). */
    public const LATEST_ORDER = ['lastPostAt' => 'DESC', 'createdAt' => 'DESC'];

    /** The "popular" feed's order: replies, then readers, then the most recently active. */
    public const TRENDING_ORDER = ['replies' => 'DESC', 'views' => 'DESC', 'lastPostAt' => 'DESC'];

    /** No topic at all: what a list of no readable board reads (an empty list matches nothing). */
    private const NONE = ['id' => []];

    /**
     * A listing's criteria: scheduled topics are left out, unless asked for ($withUpcoming: a
     * board's own list, read by those who may see them); none when the readable boards given are
     * none, so the list reads nothing.
     *
     * @param int[]|null $readableCategoryIds null: every board's
     *
     * @return array<int|string, mixed>
     */
    private function listed(?array $readableCategoryIds = null, bool $withUpcoming = false): array
    {
        if ([] === $readableCategoryIds) {
            return self::NONE;
        }

        $criteria = $withUpcoming ? [] : [$this->out()];
        if (null !== $readableCategoryIds) {
            $criteria['category'] = Key::of(array_values($readableCategoryIds));
        }

        return $criteria;
    }

    /**
     * A board's topics. $withUpcoming for the moderators of that board: they
     * see what is scheduled there, on top (its last activity is its hour to
     * come) and marked as such (_topic_row.html.twig). In LIST_ORDER.
     *
     * @return array<int|string, mixed>
     */
    public function categoryCriteria(Category $category, bool $withUpcoming = false): array
    {
        return ['category' => Key::of($category)] + $this->listed(null, $withUpcoming);
    }

    /**
     * A tag's topics, of the readable boards only (the caller passes them, as for
     * latestCriteria(); null: every board's).
     *
     * In LIST_ORDER.
     *
     * @param int[]|null $readableCategoryIds
     *
     * @return array<int|string, mixed>
     */
    public function tagCriteria(Tag $tag, ?array $readableCategoryIds = null): array
    {
        return $this->listed($readableCategoryIds) + ['tags' => $tag];
    }

    /**
     * Latest activity across the whole forum, for the front page and the
     * "unread" style feeds - never pinned first here, that only makes sense
     * inside a board (LATEST_ORDER).
     *
     * @param int[]|null $readableCategoryIds
     *
     * @return array<int|string, mixed>
     */
    public function latestCriteria(?array $readableCategoryIds = null): array
    {
        return $this->listed($readableCategoryIds);
    }

    /**
     * What people are talking about: among the topics that have moved in the
     * last `days`, the busiest first - replies, then readers, then the most
     * recently active. The feed's second order ("popular"). Readable
     * boards only, the caller passing them as for latestCriteria().
     *
     * In TRENDING_ORDER.
     *
     * @param int[]|null $readableCategoryIds
     *
     * @return array<int|string, mixed>
     */
    public function trendingCriteria(?array $readableCategoryIds = null, int $days = 30): array
    {
        $criteria = $this->listed($readableCategoryIds);
        $criteria[] = Group::of(Parser::SEPARATOR_AND, Criterion::of('lastPostAt', new \DateTime(sprintf('-%d days', $days)), Parser::OPTION_GREATER_EQUAL));

        return $criteria;
    }

    /**
     * The pinned topics of the given boards, most recently active first: the
     * post-its of the flat view. Readable boards only - the caller passes
     * them, as for latestCriteria().
     *
     * @param int[] $boardIds
     * @return Topic[]
     */
    public function findPinned(array $boardIds, int $limit = 6): array
    {
        if (!$boardIds) {
            return [];
        }

        return $this->findBy($this->listed($boardIds) + ['pinned' => true], ['lastPostAt' => 'DESC'], $limit);
    }

    /**
     * The announcements of the flat view: the latest topics of the
     * announcement boards (Category::$announcement - "Annonce", "Messages
     * Officiels"), in the order they came out - a scheduled one takes its
     * place at its hour, not at the hour it was written.
     *
     * @param int[] $boardIds the readable announcement boards
     * @return Topic[]
     */
    public function findAnnouncements(array $boardIds, int $limit = 2): array
    {
        if (!$boardIds) {
            return [];
        }

        return $this->findBy($this->listed($boardIds), ['publishedAt' => 'DESC', 'id' => 'DESC'], $limit);
    }

    /**
     * Plain title/content search: a LIKE over the topic title and its posts.
     * Small forums do not need an index; when the site wants Typesense, the
     * Topic entity is where a mapping goes. Readable boards only, as tagCriteria().
     *
     * Three finder calls: the topics whose title (any translation) holds the term, those a post
     * of which holds it, then the listing of these topics - each topic once, as the DISTINCT over
     * the two joins gave it. In LIST_ORDER.
     *
     * @param int[]|null $readableCategoryIds
     *
     * @return array<int|string, mixed>
     */
    public function searchCriteria(string $term, ?array $readableCategoryIds = null): array
    {
        $criteria = $this->listed($readableCategoryIds);
        if (self::NONE === $criteria) {
            return self::NONE;
        }

        $like = '%' . mb_strtolower(trim($term)) . '%';
        $ids = [];
        foreach ($this->translations->findBy([Group::of(Parser::SEPARATOR_AND, Criterion::of('title', $like, Parser::OPTION_INSENSITIVE, Parser::OPTION_PARTIAL))]) as $translation) {
            if (null !== $id = $translation->getTranslatable()?->getId()) {
                $ids[$id] = $id;
            }
        }
        foreach ($this->posts->topicIdsWithContentLike($like) as $id) {
            $ids[$id] = $id;
        }
        // No topic found: id IN () matches nothing.
        return $criteria + ['id' => array_values($ids)];
    }

    public function findOneBySlug(string $slug): ?Topic
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * The topic next to this one in its board (by id), out ones only: the "sujet suivant /
     * précédent" links.
     */
    public function findNeighbour(Topic $topic, bool $next): ?Topic
    {
        return $this->findOneBy([
            $this->out(),
            'category' => Key::of($topic->getCategory()),
            Group::of(Parser::SEPARATOR_AND, Criterion::of('id', $topic->getId(), $next ? Parser::OPTION_GREATER : Parser::OPTION_LOWER)),
        ], ['id' => $next ? 'ASC' : 'DESC']);
    }

    /**
     * A view is not an edit: bump the counter with a direct UPDATE so the
     * Thread's updatedAt (Timestamp on update) is not touched by readers.
     */
    public function incrementViews(Topic $topic): void
    {
        $this->updateBy(['id' => $topic->getId()], ['views' => Change::add(1)]);
    }

    /**
     * Per-board counters for the index page: topics and replies, in one
     * query. Keyed by category id.
     *
     * @return array<int, array{topics:int, replies:int}>
     */
    public function countPerCategory(): array
    {
        $counts = [];
        foreach ($this->findValuesBy(['category', 'topics' => Aggregate::count(), 'replies' => Aggregate::sum('replies')], [$this->out()]) as $row) {
            $counts[(int) $row['category']] = ['topics' => (int) $row['topics'], 'replies' => (int) $row['replies']];
        }

        return $counts;
    }

    /**
     * The boards holding at least one popular topic, for the folder badge of
     * a board row. One grouped query for the whole index rather than a count
     * per row, and the very threshold a topic row uses, so "populaire" means
     * the same thing at both levels of the hierarchy.
     *
     * @return array<int, true> keyed by category id, to be read with
     *                          hot[board.id]|default(false) in Twig
     */
    public function findHotCategoryIds(int $threshold): array
    {
        $hot = [];
        foreach ($this->findValuesBy(['category'], [$this->out(), Group::of(Parser::SEPARATOR_AND, Criterion::of('replies', $threshold, Parser::OPTION_GREATER_EQUAL))], distinct: true) as $row) {
            $hot[(int) $row['category']] = true;
        }

        return $hot;
    }

    /**
     * The most recently active topic of each board, for the classic index's
     * "last message" column (who wrote last, when, in which topic), as
     * phpBB's forum list had it. One query: a topic whose last activity is
     * its board's latest. Keyed by category id.
     *
     * @return array<int, Topic>
     */
    public function findLastPerCategory(): array
    {
        // Scheduled topics out of the MAX() as well: otherwise a board whose
        // newest topic is still to come would show no last message at all.
        $last = [];
        foreach ($this->findBy([$this->out(), Criterion::greatestPer('lastPostAt', 'category')]) as $topic) {
            $id = $topic->getCategory()?->getId();
            if (null !== $id && !isset($last[$id])) {
                $last[$id] = $topic;
            }
        }

        return $last;
    }

    /**
     * Tags in use on the forum - the tag cloud. Rows of
     * ['tag' => Tag, 'nb' => int]. Queried from the Tag side: DQL will not
     * select a joined entity without its root alias, and the count is what
     * the join is for.
     *
     * Highest Tag::$priority first, as the admin set it on /admin/bbs/ordre;
     * usage only decides between tags of equal priority - which, until an
     * admin orders them, is all of them (priority 0).
     */
    public function findTagUsage(int $limit = 30): array
    {
        $rows = $this->findValuesBy(['tag' => 'tags.id', 'priority' => 'tags.priority', 'nb' => Aggregate::count()], [$this->out()], ['priority' => 'DESC', 'nb' => 'DESC'], $limit);
        $tags = [];
        foreach ((new ServiceEntityRepository($this->finders(), Tag::class))->findBy(['id' => array_column($rows, 'tag')]) as $tag) {
            $tags[$tag->getId()] = $tag;
        }

        return array_values(array_filter(array_map(static fn (array $row): ?array => isset($tags[$row['tag']]) ? ['tag' => $tags[$row['tag']], 'nb' => (int) $row['nb']] : null, $rows)));
    }

    /**
     * How many topics carry each tag, every topic counted (scheduled ones too), for the admin's
     * order page (OrderController): one count per tag, the tags without a topic left out.
     *
     * @param iterable<Tag> $tags
     *
     * @return array<int, int> tag id => topics
     */
    public function countPerTag(iterable $tags): array
    {
        $uses = [];
        foreach ($tags as $tag) {
            if (0 < $nb = $this->count(['tags' => $tag])) {
                $uses[$tag->getId()] = $nb;
            }
        }

        return $uses;
    }
}
