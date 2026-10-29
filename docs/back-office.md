---
title: "The back office"
order: 36
---

# The back office

The screens are loaded when omnibase/admin is installed (its
`Base\Admin\Controller\AbstractCrudController` exists).

## Categories

`Base\Forum\Controller\Admin\Crud\CategoryCrudController` edits groups and
boards: title, slug (following the title), parent (none for a group),
position, locked, announcement, polls, icon, colour, the role a reader needs
(`requiredRole`, chosen among the site's roles; empty, everyone reads) and
description. Filters: parent, locked.

## Topics

`Base\Forum\Controller\Admin\Crud\TopicCrudController` edits a topic's title
(a translation field), slug, board, tags, pin and lock; the index shows its
views and replies. Filters: board, pinned, locked. Posts are edited on the
site.

The two CRUD screens carry no `#[OpenToAdmins]`: their writes follow
omnibase/admin's default.

## The order page

`/admin/bbs/ordre` (`forum_admin_order`, `ROLE_ADMIN`;
`Base\Forum\Controller\Admin\OrderController`) orders the whole forum on one
screen: the groups, the boards in each group (a board can change group
there), and the tags of the tag cloud. It posts `order[groups][]`,
`order[boards][<group id>][]` and `order[tags][]` with a CSRF token
(`forum_admin_order`) and flashes `admin.order.saved` (domain `forum`).

Groups and boards are numbered 10, 20, 30..., so a board added later can be
slotted between two without renumbering. A board stays a board and a group a
group: only a board's parent changes. Tags go the other way: the first one
gets the highest `priority`, which the tag cloud reads first.

Its route lives beside the CRUD controllers, in `src/Controller/Admin`, which
a site imports ([Installation](installation.md)).

## The dashboard

```php
yield MenuItem::linkToCrud(\Base\Forum\Entity\Category::class, 'Forums', 'fa-solid fa-comments');
yield MenuItem::linkToCrud(\Base\Forum\Entity\Topic::class, 'Topics', 'fa-solid fa-comment-dots');
yield MenuItem::linkToRoute('forum_admin_order', [], 'Order', 'fa-solid fa-sort');
```

The categories screen labels the `polls` field in the reader's language (`form.board_polls`).
