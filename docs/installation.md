---
title: "Installation"
order: 10
---

# Installation

## Requirements

- PHP 8.2 or later;
- doctrine/orm 2.15 or 3;
- omnisense/omnibase 3.0 or 4.0, omnibridge/symfony 1.x, omnibase/admin;
- league/commonmark 2.4 or later;
- Symfony 6, 7 or 8 (config, dependency-injection, form, http-kernel,
  security-bundle);
- an `App\Entity\User` class: authors, last posters, deleters and voters are
  that entity.

omnibase/admin is required, not optional: omnisense/omnibase walks every class
of a bundle's `src/` when it builds its `App\` aliases, so CRUD controllers
extending an admin class that is not installed would break the application.

## The bundle

```bash
composer require omnibase/forum:dev-main
```

```php
// config/bundles.php
Base\Forum\ForumBundle::class => ['all' => true],
```

## The routes

```yaml
# config/routes.yaml
forum_controller:
    resource: "@ForumBundle/src/Controller/Client"
    type: attribute
    prefix: /
```

The order page of the back office, `/admin/bbs/ordre`, is in
`src/Controller/Admin`; a site that wants it imports that directory too (the
CRUD controllers beside it carry no route attribute and add nothing):

```yaml
forum_admin_controller:
    resource: "@ForumBundle/src/Controller/Admin"
    type: attribute
    prefix: /
```

## The configuration

Every key is optional ([Configuration reference](configuration.md)):

```yaml
# config/packages/forum.yaml
forum:
    topics_per_page: 20
    posts_per_page: 10
    flood_interval: 15
    hot_threshold: 25
    active_hours: 72
    moderator_role: ROLE_ADMIN
    admin_role: ROLE_SUPER_ADMIN
```

The moderation rule reads the role hierarchy of `security.yaml`
([Who may do what](permissions.md)).

## The schema and the assets

```bash
bin/console doctrine:migrations:diff
bin/console doctrine:migrations:migrate   # forum_category, forum_topic, forum_post, forum_poll, forum_poll_vote
bin/console assets:install                # public/bundles/forum/css, public/bundles/forum/js
```

## The layout

Every forum page extends `@Forum/client/layout.html.twig`, which extends the
site's `layout1.html.twig` and fills its `title`, `stylesheets` and `content`
blocks ([Templates and Twig](templates.md)).

## Overriding the entities

The bundle maps `Base\Forum\Entity\*` to `App\Entity\Forum\*` and
`Base\Forum\Repository\*` to `App\Repository\Forum\*` (omnisense/omnibase's
App-wins aliasing): an `App\Entity\Forum\Topic` extending
`Base\Forum\Entity\Topic` takes over.

## The demo

The repository holds a demo application under `example/app/` (bundles,
routes, Doctrine, security, `forum.yaml`, a `layout1.html.twig`) and a
`Dockerfile` that builds it with SQLite; its first page seeds two members, a
group with a public and a staff-only board, three tags and a topic with a
reply.
