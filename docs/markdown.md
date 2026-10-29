---
title: "Markdown"
order: 33
---

# Markdown

## The renderer

`Base\Forum\Service\MarkdownRenderer` renders a post with league/commonmark:
CommonMark, GitHub flavouring (tables, task lists, strikethrough), autolinks.
Raw HTML is escaped (`html_input: escape`, and the disallowed raw HTML
extension), `javascript:` links are dropped (`allow_unsafe_links: false`),
nesting stops at 20 levels, and a single line break becomes `<br>`.

Two forum habits are added:

- `>>12`, at a word boundary, becomes a link to `#post-12`, the anchor of post
  12 of the same topic; inside code it stays as typed;
- `@name` (2 to 32 letters, digits, `_` or `-`) becomes a link to the member's
  profile when the site has a route named `user_profileByUsername` (with a
  `username` parameter); inside code, a link or a tag it stays as typed.

| Method | Returns |
|---|---|
| `render(?string $markdown)` | the HTML; an empty string for empty input |
| `excerpt(?string $markdown, int $length = 160)` | the rendered text without tags, on one line, cut at `$length` characters with an ellipsis |
| `quote(?string $markdown, ?string $author = null)` | the text with `> ` before every line, under a line naming the author, for a reply box |

## An example

```php
use Base\Forum\Service\MarkdownRenderer;

$markdown = new MarkdownRenderer();
$post = "As >>2 said, see [the docs](javascript:alert(1)) and <script>x()</script>.\n\n- [x] done\n- ~~old~~";
echo $markdown->render($post);
echo "---\n", $markdown->excerpt($post, 40), "\n---\n";
echo $markdown->quote("First line\nSecond line", 'anna');
```

Its output, run in omnisense/omnibase-harness (no router, so no mention
links):

```text
<p>As <a href="#post-2">&gt;&gt;2</a> said, see <a>the docs</a> and &lt;script&gt;x()&lt;/script&gt;.</p>
<ul>
<li><input checked="" disabled="" type="checkbox"> done</li>
<li><del>old</del></li>
</ul>
---
As >>2 said, see the docs and <script>x(…
---
> **@anna** a écrit :
> First line
> Second line
```

The excerpt decodes the entities after removing the tags, so the escaped
`<script>` comes back as text: it is plain text, to be printed escaped (the
`forum_excerpt` filter is not marked safe, so Twig escapes it).

> [!WARNING]
> Known defect: `quote()` writes the author's line in French ("a écrit :")
> whatever the locale (`src/Service/MarkdownRenderer.php:59`).

## In Twig

```twig
{{ post.content|forum_markdown }}       {# safe HTML #}
{{ topic.firstPost.content|forum_excerpt(120) }}
{{ forum_quote(post.content, post.author) }}
```
