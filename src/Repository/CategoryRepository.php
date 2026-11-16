<?php

namespace Base\Forum\Repository;

use Base\Database\Repository\ServiceEntityRepository;
use Base\Database\Repository\Finder\Criterion;
use Base\Database\Repository\Finder\Group;
use Base\Database\Repository\Finder\Key;
use Base\Database\Repository\Finder\Parser;
use Base\Forum\Entity\Category;

/**
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    /**
     * The whole tree in display order: groups first, each followed by its
     * boards - one query, then assembled in memory.
     *
     * @return Category[] root groups, with their children loaded
     */
    public function findTree(): array
    {
        $all = $this->findBy([], ['position' => 'ASC', 'title' => 'ASC']);

        return array_values(array_filter($all, fn (Category $c) => $c->isGroup()));
    }

    /** @return Category[] the boards (leaves) topics can be posted in, by their group's position then their own */
    public function findBoards(): array
    {
        return $this->findBy([self::boards()], self::BOARD_ORDER);
    }

    /** @return Category[] the boards a topic may be opened in (not locked), in the order of findBoards() */
    public function findOpenBoards(): array
    {
        return $this->findBy([
            self::boards(),
            'locked' => false,
        ], self::BOARD_ORDER);
    }

    /** @return Category[] the groups (the categories without a parent), by position then title */
    public function findGroups(): array
    {
        return $this->findBy(['parent' => Key::of(null)], ['position' => 'ASC', 'title' => 'ASC']);
    }

    /** The boards: a parent, its key compared (`c.parent IS NOT NULL`), no join. */
    private static function boards(): Group
    {
        return Group::of(Parser::SEPARATOR_AND, Criterion::of('parent', Key::of(null), Parser::OPTION_NOT_EQUAL));
    }

    /** A board's group first, then the board: the path "parent.position" orders by the group's. */
    private const BOARD_ORDER = ['parent.position' => 'ASC', 'position' => 'ASC', 'title' => 'ASC'];

    public function findOneBySlug(string $slug): ?Category
    {
        return $this->findOneBy(['slug' => $slug]);
    }
}
