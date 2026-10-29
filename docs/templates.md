---
title: "Templates and Twig"
order: 34
---

# Templates and Twig

## The layout

`@Forum/client/layout.html.twig` extends the site's `layout1.html.twig`. It
fills `title` (the page's `forum_title` block, else the index's title),
`stylesheets` (`bundles/forum/css/forum.css`) and `content`, in which it
draws the forum's header, its pages and its side column. A page of the forum
fills its own blocks:

| Block | Holds |
|---|---|
| `forum_header` | the header: the banner, the breadcrumb, the view or style switch, the search |
| `forum_banner` | includes `@Forum/client/_banner.html.twig` |
| `forum_breadcrumb`, `forum_crumbs` | the trail; a site with a breadcrumb of its own empties `forum_breadcrumb` |
| `forum_flashes` | the flash messages (includes the site's `flashbag.html.twig` if it has one) |
| `forum_lead` | what a page wants read first, across both columns |
| `forum_body` | the page itself |
| `forum_side` | the side column (`_aside.html.twig`: new topic, new board, sign in, the tag cloud); a page adds to it with `parent()` |
| `forum_footer` | the foot, empty by default |

The host's layout needs the `title`, `stylesheets` and `content` blocks.

## Override points

A site overrides any template in `templates/bundles/ForumBundle/`. The two
meant for it:

- `client/_banner.html.twig`, the banner above the forum;
- `client/_avatar.html.twig`, how a member is pictured.

The other partials (`_topic_row`, `_post`, `_member`, `_folder`, `_badge`,
`_legend`, `_pager`, `topic/_poll`...) can be overridden the same way.

## Scripts and stylesheets

After `assets:install`, under `bundles/forum/`:

| File | Loaded by | Does |
|---|---|---|
| `css/forum.css` | every page | the forum |
| `css/forum-thread.css` | a topic | the post header and its tools, the timeline |
| `js/forum.js` | a topic | the like and follow buttons, a confirmation on the destructive moderation buttons, the Markdown toolbar of every editor, the board jump, a post edited in place |
| `js/forum-thread.js` | a topic | the timeline and the stream of pages, from the topic's outline |
| `js/forum-feed.js` | the feed, a board, a tag, the search | the stream of rows |
| `js/forum-index.js` | the index | folding a group's boards, remembered per group in the browser |
| `js/forum-poll.js` | the new topic page, a poll allowing several answers | the poll builder; the several-answers vote |

## Twig filters and functions

| Name | Kind | Returns |
|---|---|---|
| `forum_markdown` | filter | a post's HTML ([Markdown](markdown.md)), marked safe |
| `forum_excerpt(length = 160)` | filter | a one-line plain-text excerpt |
| `forum_quote(markdown, author)` | function | the text quoted for a reply box |
| `forum_is_hot(topic or replies)` | function | whether a topic has `forum.hot_threshold` replies or more |
| `forum_is_active(topic or date)` | function | whether its last activity is within `forum.active_hours` |
| `forum_active_hours()` | function | `forum.active_hours` |
| `forum_topic_folder(topic)` | function | the topic's badge, `{state, title}` |
| `forum_board_folder(board, last = null, hot = false)` | function | the board's badge, `{state, title}` |

A topic's badge follows this precedence: an announcement (a topic of an
announcement board, `announce`), a post-it (`sticky`), a locked topic
(`lock`), a popular one (`hot`), then the plain folder (`default`); the last
three take their awake art (`new_lock`, `new_hot`, `new`) when the topic has
moved within `active_hours`. A board's badge reads the same way after its
latest message: `announce`, `lock`, `hot` when it holds a popular topic,
`default`; a board kept for a role says so in its title. "Awake" means recent
activity, not unread: the forum keeps no read marker per member.

## Translations

The domain `forum`, in English and French (ICU format), holds every text of
the pages, the forms, the flashes and the back office; the templates call
its keys as `'@forum.<key>'|trans`. A site overrides a message in its own
`translations/forum+intl-icu.<locale>.yaml`.
