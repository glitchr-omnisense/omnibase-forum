# omnibase/forum

omnibase/forum is a discussion forum for a Symfony site of the omnibase
family: groups holding boards, topics, Markdown posts, tags, polls, pins,
locks, scheduled topics and moderation. Its namespace is `Base\Forum\`; it is
built on omnisense/omnibase and omnibase/admin.

## What it adds

- A `Topic` that is omnisense/omnibase's `Thread` (a JOINED subclass, table
  `forum_topic`): it inherits the thread's tags, owners, followers, likes,
  mentions, slug, publication state, soft delete and translations, and adds
  its board, posts, pin, lock, views, replies, last activity and poll.
- A `Category` (`forum_category`), a two-level tree: a category without a
  parent is a group, a category with one is a board.
- A `Post` (`forum_post`), Markdown rendered with league/commonmark, raw HTML
  escaped; a soft delete that keeps the numbering; the message it answers.
- A `Poll` (`forum_poll`) and its `PollVote`s (`forum_poll_vote`): one answer
  or several, once per member.
- A voter, `ForumVoter`, with seven attributes (`FORUM_READ`, `FORUM_POST`,
  `FORUM_REPLY`, `FORUM_EDIT`, `FORUM_MODERATE`, `FORUM_SCHEDULE`,
  `FORUM_ADMIN`) and the moderation rule of a role hierarchy.
- Fifteen routes under `/bbs` and the order page `/admin/bbs/ordre`; the board index in two
  views, a topic in two styles, all remembered per browser.
- Two back office screens (categories, topics) and an order page for the
  groups, boards and tags.
- A sitemap listener that lists the public boards and their topics.
- Twig filters and functions for the Markdown, the excerpts, the quotes and
  the folder badges.
- Its stylesheets and scripts under `public/`, English and French messages
  (domain `forum`).

## Pages

- [Installation](installation.md)
- [Concepts](concepts.md)
- [Who may do what](permissions.md)
- [The pages](pages.md)
- [Writing, replying, voting](writing.md)
- [Markdown](markdown.md)
- [Templates and Twig](templates.md)
- [The back office](back-office.md)
- [Configuration reference](configuration.md)
- [Testing](testing.md)
