# Changelog

## [Unreleased]

### Fixed

- The tag page and the search list only the topics of the boards the reader may read, as the
  index's feed and the sitemap do: a board kept for a role (`Category::$requiredRole`) showed its
  topics' titles, its name and their last posters to anyone. `TopicRepository::createTagQuery()`
  and `createSearchQuery()` take the readable boards' ids (null: every board, as before). Test:
  tests/Controller/RestrictedBoardsTest.php.
