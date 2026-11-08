# Changelog

## [Unreleased]

### Changed
- Accepts omnisense/omnibase 5.0 (`3.0.*-dev || 4.0.*-dev || 5.0.*-dev`; plan "omnibase agnostic", step S): the bundle runs
  unchanged on it, `Base\Bundle\AbstractBaseBundle` and the other former names it uses being aliases of
  omnibridge/symfony's classes there. The sites on 4.x resolve as before.
- `Category` and `Topic::__toLink()` link through `Base\Contract\Link::to()` (the Urls contract; the same address under
  Symfony, in the core 3.x and 4.x) instead of BaseTrait's `getRouter()->generate()`, which the
  core deprecates since BaseTrait names contracts only (omnisense/omnibase 4.x, step F2f).

### Fixed
- `forum.title_max_length` and `forum.content_max_length` bound the title and the post: the new
  topic and reply forms take their longest from them (`TopicType`, `PostType`); `TopicModel` and
  `PostModel` hard-coded 120 and 20000 whatever the options.
- A quote's author line is in the reader's language (`reply.quote_wrote`), not "a écrit :"
  whatever the locale.
- The categories screen labels the `polls` field in the reader's language, not "Sondages".
- README: omnisense/omnibase and its GitHub address in place of base-bundle on the abandoned
  gitlab.glitchr.dev; the demo image named omnibase-forum-demo.
