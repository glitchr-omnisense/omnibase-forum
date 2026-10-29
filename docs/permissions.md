---
title: "Who may do what"
order: 30
---

# Who may do what

## The voter

`Base\Forum\Security\ForumVoter` answers seven attributes. Its subject is a
category, a topic or a post; a topic and a post are judged by their board.

| Attribute | Subject | Granted when |
|---|---|---|
| `FORUM_READ` | category, topic, post | the board has no `requiredRole`, or the reader has it |
| `FORUM_POST` | board | signed in, the board readable and a board (not a group); on an announcement board, the admin role only, locked or not; on a locked board, its moderators only |
| `FORUM_REPLY` | topic | signed in, readable, and neither the topic nor its board locked; its moderators always |
| `FORUM_EDIT` | topic, post | signed in and readable: its moderators, or its author while the topic is not locked |
| `FORUM_MODERATE` | category, topic, post | the reader moderates its board; with no subject, the reader moderates any board |
| `FORUM_SCHEDULE` | board | the reader moderates it and may post there; with no subject, moderates any board |
| `FORUM_ADMIN` | none | the reader has `forum.admin_role` |

An upcoming topic (scheduled, not out yet) changes three answers: nobody
replies to it, and anyone who is neither one of its owners nor a moderator of
its board is refused everything on it and on its posts, `FORUM_READ`
included.

## Who moderates a board

`ForumVoter::moderates($roles, $category)` reads the role hierarchy of the
firewall (`security.role_hierarchy`):

- the admin role (`forum.admin_role`, `ROLE_SUPER_ADMIN` by default)
  moderates every board;
- the moderator role (`forum.moderator_role`, `ROLE_ADMIN` by default)
  moderates the open boards, and the boards kept for a role it stands above;
- a board kept for the moderator role's own rank is the admins' to moderate:
  its moderators read it, the admins keep order there;
- a board kept for a role outside the moderator's branch, the moderator
  cannot even read;
- announcement boards are the admin role's alone.

With this hierarchy:

```yaml
# config/packages/security.yaml
security:
    role_hierarchy:
        ROLE_HELPER: ROLE_USER
        ROLE_ADMIN: ROLE_HELPER
        ROLE_SUPER_ADMIN: ROLE_ADMIN
```

a `ROLE_ADMIN` member moderates the open boards and a board kept for
`ROLE_HELPER`; a board kept for `ROLE_ADMIN` is moderated by
`ROLE_SUPER_ADMIN` only.

## What the controllers check

| Action | Check |
|---|---|
| reading a board, a topic, a post's address | `FORUM_READ` (403; an upcoming topic one may not see: 404) |
| opening a topic | `ROLE_USER`, then `FORUM_POST` on the board chosen; the board list offers only those the writer may post in |
| replying | `ROLE_USER`, `FORUM_REPLY` |
| voting | `ROLE_USER`, `FORUM_READ`, a CSRF token |
| editing a topic or a post | `ROLE_USER`, `FORUM_EDIT`; moving a topic to another board needs `FORUM_MODERATE` on both |
| pin, unpin, lock, unlock | `ROLE_USER`, `FORUM_MODERATE`, a CSRF token |
| deleting a topic | `ROLE_USER`, `FORUM_EDIT` (an author deletes their own), a CSRF token |
| deleting a post | `ROLE_USER`, `FORUM_EDIT`, a CSRF token; restoring a deleted post, `FORUM_MODERATE` |
| scheduling, posting in another member's name | `FORUM_SCHEDULE`, `FORUM_ADMIN` |
| opening a board or a group from the site | `FORUM_ADMIN` |
| the order page | `ROLE_ADMIN` |

The moderators are exempt from the flood interval.

## In templates

```twig
{% if is_granted('FORUM_POST', category) %}
    <a href="{{ path('forum_topic_new', {category: category.slug}) }}">{{ '@forum.topic.new'|trans }}</a>
{% endif %}
```

> [!WARNING]
> Known defect: the tag page (`/bbs/t/{slug}`) and the search
> (`/bbs/recherche`) list the topics of every board, a board kept for a role
> included: their titles, boards and last posters show to a reader who may not
> open them (`TopicRepository::createTagQuery()`, `createSearchQuery()`). The
> index's feed and the sitemap leave those boards out.
