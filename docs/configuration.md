---
title: "Configuration reference"
order: 40
---

# Configuration reference

## The `forum` tree

Dumped with Symfony's `YamlReferenceDumper` from
`Base\Forum\DependencyInjection\ForumConfiguration`:

```yaml
forum:

    # Topics listed per page in a category, a tag or the search.
    topics_per_page:      20

    # Posts shown per page inside a topic.
    posts_per_page:       10

    # Seconds a member must wait between two posts (phpBB called it flood_interval).
    flood_interval:       15

    # Replies from which a topic is flagged "hot".
    hot_threshold:        25

    # A board or topic with a message this recent is awake: its folder badge shows the "new" art instead of the sleeping "zzz".
    active_hours:         72
    title_max_length:     120
    content_max_length:   20000

    # Role allowed to pin, lock, move and delete any topic or post.
    moderator_role:       ROLE_ADMIN

    # Role allowed to open new boards and groups from the site itself (the back office has its own CRUD).
    admin_role:           ROLE_SUPER_ADMIN
```

| Key | Type | Default | Read by |
|---|---|---|---|
| `topics_per_page` | integer, at least 1 | 20 | the index's lists, a board, a tag, the search |
| `posts_per_page` | integer, at least 1 | 10 | a topic, a post's page, the outline |
| `flood_interval` | integer, at least 0 | 15 | opening a topic, replying; 0 turns it off |
| `hot_threshold` | integer, at least 1 | 25 | `forum_is_hot()`, the badges, the boards holding a popular topic |
| `active_hours` | integer, at least 1 | 72 | `forum_is_active()`, the badges, the legend |
| `title_max_length` | integer, at least 10 | 120 | nothing |
| `content_max_length` | integer, at least 100 | 20000 | nothing |
| `moderator_role` | string | `ROLE_ADMIN` | `ForumVoter` |
| `admin_role` | string | `ROLE_SUPER_ADMIN` | `ForumVoter` (`FORUM_ADMIN`, the moderation of everything, the announcement boards) |

> [!WARNING]
> Known defect: `title_max_length` and `content_max_length` are read by
> nothing: the forms keep 120 characters for a title and 20,000 for a post
> whatever they say (`Form/Model/TopicModel.php`, `Form/Model/PostModel.php`).

## Parameters

Each key is a container parameter: `%forum.topics_per_page%`,
`%forum.posts_per_page%`, `%forum.flood_interval%`, `%forum.hot_threshold%`,
`%forum.active_hours%`, `%forum.title_max_length%`,
`%forum.content_max_length%`, `%forum.moderator_role%`, `%forum.admin_role%`.

## Services

`config/services.php` autowires and autoconfigures everything under
`Base\Forum\` except the DI classes, the entities and the bundle class; the
client controllers are tagged as controllers; the admin controllers are
loaded when omnibase/admin is installed.

| Service | Role |
|---|---|
| `Base\Forum\Security\ForumVoter` | the seven `FORUM_*` attributes, `moderates()` |
| `Base\Forum\Service\MarkdownRenderer` | `render()`, `excerpt()`, `quote()` |
| `Base\Forum\Twig\ForumTwigExtension` | the filters and functions |
| `Base\Forum\EventListener\SitemapListener` | the boards and topics of `/sitemap.xml` |
| `Base\Forum\Repository\CategoryRepository` | `findTree()`, `findBoards()`, `findOneBySlug()` |
| `Base\Forum\Repository\TopicRepository` | the list queries, `whereOut()`, `findTagUsage()`, `incrementViews()`, the counters |
| `Base\Forum\Repository\PostRepository` | `createTopicQuery()`, `findOutline()`, `findLastByAuthor()`, `findPageOf()`, `countByAuthor()` |

`TopicRepository` extends omnisense/omnibase's `ThreadRepository` (a `Thread`
subclass needs its hierarchy trait), not `ServiceEntityRepository`.
