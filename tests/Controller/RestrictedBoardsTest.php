<?php

namespace Tests\Base\Forum\Controller;

use Base\Entity\Thread\Tag;
use Base\Forum\Entity\Category;
use Base\Forum\Entity\Topic;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * The tag page and the search list only the topics of the boards the reader may read, as the
 * index's feed and the sitemap do: a board kept for a role (Category::$requiredRole) listed its
 * topics' titles, boards and last posters to anyone.
 */
final class RestrictedBoardsTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    /** @var list<object> */
    private array $created = [];
    private string $word;

    protected function setUp(): void
    {
        $_SERVER['KERNEL_CLASS'] ??= $_ENV['KERNEL_CLASS'] ?? 'App\\Kernel';
        if (!class_exists($_SERVER['KERNEL_CLASS'])) {
            self::markTestSkipped('Needs a host application kernel.');
        }
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->word = 'zq'.bin2hex(random_bytes(3));

        $group = new Category('Le club '.$this->word);
        $open = new Category('Ouvert '.$this->word, $group);
        $kept = new Category('Bureau '.$this->word, $group);
        $kept->setRequiredRole('ROLE_ADMIN');
        foreach ([$group, $open, $kept] as $category) {
            if (method_exists($category, 'setSlug')) {
                $category->setSlug(strtolower(preg_replace('/\W+/', '-', (string) $category->getTitle())));
            }
            $this->em->persist($category);
        }
        $tag = new Tag('tag '.$this->word, 'tag-'.$this->word);
        $this->em->persist($tag);

        foreach ([[$open, 'Sortie publique '.$this->word], [$kept, 'Budget secret '.$this->word]] as [$board, $title]) {
            $topic = new Topic(null, $board, $title);
            $topic->setSlug(strtolower(preg_replace('/\W+/', '-', $title)));
            $topic->addTag($tag);
            $this->em->persist($topic);
            $this->created[] = $topic;
        }
        $this->em->flush();
        array_push($this->created, $tag, $kept, $open, $group);
        $this->em->clear(); // the pages read the boards as stored, the group with its children
    }

    protected function tearDown(): void
    {
        $created = array_map(fn (object $entity) => [$entity::class, $entity->getId()], $this->created);
        $this->em->clear();
        foreach ($created as [$class, $id]) {
            $managed = $this->em->find($class, $id);
            if ($managed) {
                $this->em->remove($managed);
                $this->em->flush();
            }
        }
        parent::tearDown();
    }

    private function page(string $path): string
    {
        $request = Request::create('https://localhost'.$path, 'GET', [], [], [], ['REMOTE_ADDR' => '127.0.0.1', 'HTTP_USER_AGENT' => 'PHPUnit']);
        $response = static::$kernel->handle($request);
        $this->assertSame(200, $response->getStatusCode(), $path);

        return (string) $response->getContent();
    }

    public function testTheTagPageLeavesOutTheBoardsTheReaderMayNotRead(): void
    {
        $page = $this->page('/bbs/t/tag-'.$this->word);

        $this->assertStringContainsString('Sortie publique '.$this->word, $page);
        $this->assertStringNotContainsString('Budget secret '.$this->word, $page);
    }

    public function testTheSearchLeavesOutTheBoardsTheReaderMayNotRead(): void
    {
        $page = $this->page('/bbs/recherche?q='.$this->word);

        $this->assertStringContainsString('Sortie publique '.$this->word, $page);
        $this->assertStringNotContainsString('Budget secret '.$this->word, $page);
    }
}
