# Changelog

## [Unreleased]

## [1.0.2] — 2026-11-14

### Changed
- Accepts omnisense/omnibase 6.0 beside 5.1: `omnisense/omnibase` `^5.1 || ^6.0` and `omnibridge/symfony` `^1.1 || ^2.0`. The same code runs on both lines.
- The example application's `config/bundles.php` names `Omnibridge\Symfony\Omnibase\BaseBundle` (the former
  `Base\BaseBundle` is gone in 6.0).

## [1.0.1] — 2026-11-14

### Changed
- The current names of what moved to omnibridge/symfony, in place of the former ones the core keeps as aliases only
  until 6.0: `Base\Bundle\AbstractBaseBundle` → `Omnibridge\Symfony\Omnibase\Bundle\AbstractBaseBundle`;
  `Base\Bundle\AbstractBaseConfiguration` → `Omnibridge\Symfony\Omnibase\Bundle\AbstractBaseConfiguration`;
  `Base\Bundle\AbstractBaseExtension` → `Omnibridge\Symfony\Omnibase\Bundle\AbstractBaseExtension`.
  No behaviour change; the bundle no longer needs any alias of the core's `former-names*.php`.

## [1.0.0] — 2026-11-13

First stable release.

### Changed
- Stable requirements, so that `composer require omnibase/forum` installs at Packagist's default stability:
  `omnisense/omnibase` ^5.1 (in place of `3.x || 4.0 || 5.0` dev branches), `omnibridge/symfony` ^1.1, `omnibase/admin` ^1.0;
  no `minimum-stability: dev`; the branch alias `dev-main` → `1.x-dev`.
- CI: `.github/workflows/ci.yml` (glitchr-studio/ci's php-package workflow, pinned: the bundle installed alone,
  composer validate, PHPUnit, a consumer install without require-dev with `src/` linted and the bundle class
  autoloaded) and `release.yml` (that CI on a tag, then the release).
- tests/bootstrap.php: outside a host application, the core's `Base\Entity\User` stands in for `App\Entity\User`, so that
  PollTest runs when the bundle is installed alone.

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
