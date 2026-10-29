# Changelog

## [Unreleased]

### Fixed
- `forum.title_max_length` and `forum.content_max_length` bound the title and the post: the new
  topic and reply forms take their longest from them (`TopicType`, `PostType`); `TopicModel` and
  `PostModel` hard-coded 120 and 20000 whatever the options.
- A quote's author line is in the reader's language (`reply.quote_wrote`), not "a écrit :"
  whatever the locale.
- The categories screen labels the `polls` field in the reader's language, not "Sondages".
- README: omnisense/omnibase and its GitHub address in place of base-bundle on the abandoned
  gitlab.glitchr.dev; the demo image named omnibase-forum-demo.
