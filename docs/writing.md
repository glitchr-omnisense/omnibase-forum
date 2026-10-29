---
title: "Writing, replying, voting"
order: 32
---

# Writing, replying, voting

## Opening a topic

`/bbs/nouveau/{category}` opens a topic (`Base\Forum\Form\Type\TopicType`,
model `Base\Forum\Form\Model\TopicModel`):

| Field | Rule |
|---|---|
| `category` | required; left out when the page was opened from a board the writer may post in |
| `title` | 3 to 120 characters |
| `tags` | omnisense/omnibase's tags, any number |
| `content` | the opening post, 2 to 20,000 characters, Markdown |
| `pollQuestion`, `pollOptions`, `pollMax` | an optional poll: a question of at most 200 characters, 2 to 10 answers (one per line), at most `pollMax` choices (1 to 10, not more than the answers); only on a board that takes polls |
| `publishedAt` | for `FORUM_SCHEDULE` only: an hour to come out at; an hour already gone means now |
| `author` | for `FORUM_ADMIN` only: the member it goes out under, searched by name |

When the page is not opened from a board, the board list offers only the
boards the writer may post in (`FORUM_POST`); with none, the page flashes
`@forum.aside.cannot_post` and returns to the index.

`?depuis=<post id>` opens a topic out of a message: the new topic starts on
the same board, its content prefilled with a link back to that message
(`@forum.topic.linked_opening`).

On submit, in order: a scheduling refused for that board puts an error on
`publishedAt`; a board the writer may not post in flashes
`@forum.flash.board_locked`; a post sent within the flood interval flashes
`@forum.flash.flood` with the seconds to wait. Otherwise the topic, its
opening post and its poll are saved, and the page redirects to the topic,
flashing `@forum.flash.topic_created` or, for an upcoming topic,
`@forum.flash.topic_scheduled` with its hour.

## Replying

The reply form (`Base\Forum\Form\Type\PostType`, 2 to 20,000 characters) is
on the topic's page for those who may reply, and posts to
`/bbs/{slug}/repondre`. `?quote=<post id>` prefills it with that post quoted;
`?reply=<post id>` links the reply to that post without quoting it. The link
is sent as `reply_to` and kept when the post belongs to the same topic. After
the reply, the page redirects to the new post, on its page; a refusal or an
invalid form flashes its message and returns to the topic.

## The flood interval

A member waits `forum.flood_interval` seconds (15 by default) after their last
post before posting again, a topic or a reply. 0 turns it off; moderators are
never held.

## Editing

`/bbs/{slug}/modifier` edits a topic's title and tags (the opening post is
edited as a post). A moderator of the topic's board can also move it to
another board they moderate; while the topic is upcoming, those who may
schedule there can move its hour; the forum admin can change whose name it is
under.

`/bbs/message/{id}/modifier` edits a post. When its author edits it, the post
records the hour and counts the edit; a moderator's edit does not.

## Moderating and deleting

The moderation buttons post to `/bbs/{slug}/moderer/{action}` with a CSRF
token (`forum_moderate_<topic id>`): `pin`, `unpin`, `lock`, `unlock`, and
`delete`, which an author may also do on their own topic. Deleting a topic
returns to its board.

`/bbs/message/{id}/supprimer` (CSRF token `forum_post_<post id>`) deletes a
post softly: it keeps its place and number, shown as deleted. The same button
on a deleted post restores it, for a moderator. Deleting the opening post
deletes the topic.

## Voting

A poll's form posts to `/bbs/{slug}/voter` with a CSRF token
(`forum_vote_<poll id>`) and `choice` (one answer) or `choice[]` (several).
The vote is refused, with a flash, when the topic is locked
(`@forum.poll.flash.closed`), when no valid answer is sent
(`@forum.poll.flash.no_choice`), when more answers than allowed are sent
(`@forum.poll.flash.too_many`), or when the member has voted
(`@forum.poll.flash.already`). A vote is final, and its answers are saved
together: a second click arriving with the first meets the unique key and is
answered as already voted.

## Opening a board from the site

`/bbs/nouveau-forum` (`FORUM_ADMIN`) opens a board in the group named by
`?dans=<group slug>`, or a new group without it
(`Base\Forum\Form\Type\CategoryType`: title, group, description, announcement,
polls). It is placed last among its siblings (ten past the highest position),
and the page redirects to it, flashing `@forum.flash.board_created` or
`@forum.flash.group_created`. The order page moves it; the back office edits
the rest.
