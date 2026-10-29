---
title: "The pages"
order: 31
---

# The pages

## Routes

| Name | Path | Methods | Access |
|---|---|---|---|
| `forum_index` | `/bbs` | any | everyone; in the sitemap (0.7, daily) |
| `forum_category` | `/bbs/c/{slug}` | any | `FORUM_READ`; in the sitemap (0.6, daily) |
| `forum_category_new` | `/bbs/nouveau-forum` | any | `FORUM_ADMIN`; `?dans=<group slug>` |
| `forum_tag` | `/bbs/t/{slug}` | any | everyone |
| `forum_search` | `/bbs/recherche` | any | everyone; `?q=` |
| `forum_display` | `/bbs/display` | POST | everyone |
| `forum_topic_new` | `/bbs/nouveau/{category}` | any | `ROLE_USER`; `{category}` optional; `?depuis=<post id>` |
| `forum_topic` | `/bbs/{slug}` | any | `FORUM_READ`; in the sitemap (0.5, weekly); `?page`, `?quote=<post id>`, `?reply=<post id>` |
| `forum_topic_reply` | `/bbs/{slug}/repondre` | POST | `ROLE_USER`, `FORUM_REPLY` |
| `forum_topic_vote` | `/bbs/{slug}/voter` | POST | `ROLE_USER`, `FORUM_READ` |
| `forum_topic_edit` | `/bbs/{slug}/modifier` | any | `ROLE_USER`, `FORUM_EDIT` |
| `forum_topic_moderate` | `/bbs/{slug}/moderer/{action}` | POST | `action`: `pin`, `unpin`, `lock`, `unlock`, `delete` |
| `forum_post` | `/bbs/message/{id}` | GET | `FORUM_READ`; redirects to the post in its topic, on its page |
| `forum_post_edit` | `/bbs/message/{id}/modifier` | any | `ROLE_USER`, `FORUM_EDIT` |
| `forum_post_delete` | `/bbs/message/{id}/supprimer` | POST | `ROLE_USER`, `FORUM_EDIT` |
| `forum_admin_order` | `/admin/bbs/ordre` | GET, POST | `ROLE_ADMIN` |

`forum_category_new` and `forum_topic_new` carry a route priority of 5, so
`/bbs/{slug}` does not take `nouveau-forum` or `nouveau` for a topic. Likes
and follows go through omnisense/omnibase's thread API
(`/api/thread/{slug}/like`, `unlike`, `follow`, `unfollow`).

## The index

`/bbs` has two views:

- `classic`, the board list grouped under its groups, with each board's
  topics and replies, its last message (who, when, in which topic), its folder
  badge, and a list of the latest topics below (`@Forum/client/index.html.twig`);
- `feed`, a flat list of the latest topics with the two latest announcements
  and up to six pinned topics on top (`@Forum/client/index_fil.html.twig`).

The feed has two orders: `recent` (the latest activity) and `popular` (among
the topics that moved in the last 30 days, the most replies, then the most
views). Both list only the boards the reader may read, and topics that are
out.

## Display choices

The reader's choices are kept in a cookie for a year, never in the address:

| Choice | Cookie | Values |
|---|---|---|
| `view` (the index) | `FORUM/VIEW` | `classic`, `feed` |
| `sort` (the feed) | `FORUM/SORT` | `recent`, `popular` |
| `style` (a topic) | `FORUM/TOPIC` | `retro` (one box of rows), `cards` (a card per message) |

Each button is a small POST form to `forum_display` with the choice and
`back`, the page to return to. An unknown name or value is ignored; `back`
must be a path on the site (it starts with `/`, not `//`), otherwise the
index.

## A board, a group, a tag, the search

A group's page lists its boards. A board's page lists its topics, pinned
first, then by last activity; its moderators also see its upcoming topics,
marked. A tag's page lists the topics carrying the tag. The search needs at
least three characters and matches, without case, a topic's title or the
content of any of its posts (a `LIKE`; no index).

## A topic

A topic's page shows its posts, `posts_per_page` at a time, the reply form for
those who may reply, the poll, the previous and next topics of its board, and
a "jump to" list of the boards the reader may read. An outline of the whole
topic is built from one query: each post's parent (the message it answers, or
the trunk), its branch, level and depth, its number and page, and how many
posts answer it; the timeline script and the "in reply to" lines read it.

## Infinite lists

A board, a tag, the search, the feed and a topic load their next page as the
reader scrolls (`forum-feed.js`, `forum-thread.js`): the script asks the same
address with the header `X-Forum-Stream` and receives the rows alone. The
responses carry `Vary: X-Forum-Stream`, and a page loaded that way does not
count a view.

## The sitemap

`Base\Forum\EventListener\SitemapListener` adds, on omnisense/omnibase's
`SitemapEvent::BUILD`, every board without a `requiredRole` (its update date)
and the latest topics of those boards that are out, up to 5,000
(`MAX_TOPICS`), each with the date of its last activity.
