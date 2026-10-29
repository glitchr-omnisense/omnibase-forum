<?php

namespace Tests\Base\Forum\Service;

use Base\Forum\Service\MarkdownRenderer;
use PHPUnit\Framework\TestCase;

/**
 * The renderer is the forum's only door between what a member types and the
 * HTML every other member reads, so its promises are pinned here: Markdown
 * renders, raw HTML never does, and the two forum habits (>>12, @pseudo)
 * become links without reaching into code.
 */
class MarkdownRendererTest extends TestCase
{
    private MarkdownRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new MarkdownRenderer();
    }

    public function testMarkdownRenders(): void
    {
        $html = $this->renderer->render("**bold** and _italic_\n\n> quoted");

        $this->assertStringContainsString('<strong>bold</strong>', $html);
        $this->assertStringContainsString('<em>italic</em>', $html);
        $this->assertStringContainsString('<blockquote>', $html);
    }

    public function testRawHtmlIsEscaped(): void
    {
        $html = $this->renderer->render('<script>alert(1)</script> <img src=x onerror=alert(1)>');

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    public function testJavascriptLinksAreDropped(): void
    {
        $html = $this->renderer->render('[click](javascript:alert(1))');

        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function testPostReferencesBecomeAnchors(): void
    {
        $this->assertStringContainsString('href="#post-12"', $this->renderer->render('see >>12'));
    }

    public function testPostReferencesInsideCodeAreLeftAlone(): void
    {
        $html = $this->renderer->render('a quote: `>>12` in code');

        $this->assertStringContainsString('<code>', $html);
    }

    public function testEmptyInputRendersNothing(): void
    {
        $this->assertSame('', $this->renderer->render(null));
        $this->assertSame('', $this->renderer->render("  \n "));
    }

    public function testExcerptIsPlainTextAndCut(): void
    {
        $excerpt = $this->renderer->excerpt(str_repeat('**word** ', 50), 40);

        $this->assertStringNotContainsString('<', $excerpt);
        $this->assertLessThanOrEqual(41, mb_strlen($excerpt));
        $this->assertStringEndsWith('…', $excerpt);
    }

    public function testQuotePrefixesEveryLineAndNamesTheAuthor(): void
    {
        $quote = $this->renderer->quote("first\nsecond", 'Chimbo');

        $this->assertStringContainsString('@Chimbo', $quote);
        $this->assertStringContainsString("> first\n> second", $quote);
    }

    /** The author's line of a quote is in the reader's language (forum: reply.quote_wrote), not French whatever the locale. */
    public function testTheQuotesAuthorLineIsInTheReadersLanguage(): void
    {
        foreach (['en' => '> **@Chimbo** wrote:', 'fr' => '> **@Chimbo** a écrit :'] as $locale => $line) {
            $translator = new \Symfony\Component\Translation\Translator($locale);
            $translator->addLoader('yaml', new \Symfony\Component\Translation\Loader\YamlFileLoader());
            $translator->addResource('yaml', __DIR__.'/../../translations/forum+intl-icu.'.$locale.'.yaml', $locale, 'forum+intl-icu');
            $quote = (new MarkdownRenderer(null, $translator))->quote('first', 'Chimbo');
            $this->assertStringStartsWith($line."\n", $quote, $locale);
        }
        $this->assertStringStartsWith("> **@Chimbo** wrote:\n", $this->renderer->quote('first', 'Chimbo'), 'without a translator');
    }
}
