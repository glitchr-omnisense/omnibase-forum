---
title: "Testing"
order: 60
---

# Testing

## Running the suite

From the package's checkout, with its dev dependencies:

```bash
vendor/bin/phpunit
```

`phpunit.xml.dist` boots `tests/bootstrap.php`, which registers the test
namespace itself, so the same suite runs from inside a host application:

```bash
php vendor/bin/phpunit -c vendor/omnibase/forum/phpunit.xml.dist
```

In omnisense/omnibase-harness, mounting the checkout in place of the
installed package:

```bash
docker run --rm --network none \
    -v "$PWD":/srv/demo/vendor/omnibase/forum:ro \
    omnisense/omnibase-harness:latest test forum
```

On this checkout, in the harness:

```text
OK (28 tests, 59 assertions)
```

## What the tests check

| Test | Checks |
|---|---|
| `Controller\CrudRequiredFieldsTest` | omnibase/admin's `CrudFieldsTestCase` on the two screens: a field is required exactly when the record needs it, an optional field takes an empty value, one field per property, the slug follows a field |
| `Entity\PollTest` | a single-answer poll; `maxChoices` never more than the answers; three picks of one member are one voter |
| `Entity\TopicScheduleTest` | a new topic is out at once; an hour to come keeps it upcoming and moves its activity there; an hour gone, or none, means now; the opening post is dated the hour its topic comes out; a reply keeps its own date |
| `Service\MarkdownRendererTest` | Markdown renders; raw HTML is escaped; `javascript:` links are dropped; `>>n` becomes an anchor, not inside code; empty input renders nothing; the excerpt is plain text and cut; a quote prefixes every line and names the author |
| `Twig\FolderStateTest` | a topic sleeps or talks after its last message; popular and locked topics have their awake art; announcement, then post-it, come first; the boards' badges; a reserved board is not an announcement |

The voter, the controllers and the repositories have no test of their own.
