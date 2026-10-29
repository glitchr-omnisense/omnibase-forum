---
title: "Concepts"
order: 20
---

# Concepts

## Groups and boards

`Base\Forum\Entity\Category` is a two-level tree. A category without a parent
is a group (`isGroup()`): it holds boards and no topics. A category with a
parent is a board (`isBoard()`): it holds topics.

| Field | Column | Meaning |
|---|---|---|
| `title` | string(128) | required |
| `slug` | string(128), unique | from the title |
| `description` | text, nullable | under the title |
| `icon`, `color` | string(64), string(9), nullable | how the board is drawn |
| `position` | integer | its order among its siblings (the order page writes 10, 20, 30...) |
| `locked` | boolean | no new topic and no reply, except by its moderators |
| `announcement` | boolean | an announcement board: its topics are the admin role's to write |
| `polls` | boolean, default true | whether a topic opened there may carry a poll (`allowsPolls()`) |
| `requiredRole` | string(64), nullable | the role a reader needs; null, everyone reads |
| `parent`, `children` | self-reference, SET NULL | the tree |

`CategoryRepository::findTree()` returns the groups with their boards,
`findBoards()` the boards, `findOneBySlug()` one category.

## Topics

`Base\Forum\Entity\Topic` extends omnisense/omnibase's `Base\Entity\Thread`
(discriminator `forum_topic`). From the thread it has its owners (the author),
title (translatable), slug, tags, followers, likes, state and publication
date. It adds:

| Field | Meaning |
|---|---|
| `category` | its board (required) |
| `posts` | its messages, the first one being the opening post |
| `pinned`, `locked` | a post-it on top of its board; no reply but its moderators' |
| `views` | readers, counted once per session, never its author, never a page loaded by the stream |
| `replies` | the posts after the first |
| `lastPostAt`, `lastPoster` | its last activity, by which the lists sort |
| `poll` | its poll, if it has one |

`addPost()` updates `replies`, `lastPostAt` and `lastPoster`. `setAuthor()`
changes the owner and the opening post's author together. Views are counted
with a direct `UPDATE`, so a reader does not touch the thread's `updatedAt`.

### Scheduled topics

A topic is out the moment it is written. `schedule($at)` gives it an hour to
come out at: the publication date becomes that hour when it is in the future,
now otherwise, and a topic nobody has answered moves its last activity there
too, so it comes into the lists at its hour. `isUpcoming()` is true until
then.

No command has to run: every list goes through
`TopicRepository::whereOut()`, which keeps the topics whose publication date
is null or past. Until its hour, an upcoming topic is its authors' and its
board's moderators' only; nobody replies to it; anyone else gets a 404 on its
address ([Who may do what](permissions.md)).

## Posts

`Base\Forum\Entity\Post` holds the Markdown `content`, its `author`, the
message it answers (`replyTo`), its edit count and the hour of its last edit
(`markEdited()`, when its author edits it), and a soft delete (`deletedAt`,
`deletedBy`): `delete()` keeps the post's place and number, `restore()` brings
it back. `getNumber()` is its 1-based position in the topic, `isFirst()` says
whether it is the opening post, `getShownAt()` is the hour shown on it (for
the opening post of a scheduled topic, the hour the topic came out).

Deleting the opening post deletes the topic.

## Polls

`Base\Forum\Entity\Poll` belongs to one topic: a question (200 characters),
2 to 10 answers (`MIN_OPTIONS`, `MAX_OPTIONS`, each cut at 120 characters) and
`maxChoices` (1, a single answer; more, up to that many, never more than
there are answers). `PollVote` records one answer of one member; a unique
key on poll, member and answer keeps a member from counting twice. A poll is
open while its topic is not locked (`isOpen()`); `getCounts()` gives the votes
per answer, `getTotal()` the voters, `getChoicesOf($user)` what a member
chose.

## Tags

The tags are omnisense/omnibase's `Tag`, shared with every thread.
`TopicRepository::findTagUsage()` returns the tag cloud: the tags in use on
topics that are out, highest `priority` first (the order page sets it), then
the most used.
