<?php

namespace Tests\Base\Forum\Form;

use Base\Forum\Form\Model\PostModel;
use Base\Forum\Form\Model\TopicModel;
use Base\Forum\Form\Type\PostType;
use Base\Forum\Form\Type\TopicType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Constraints\Length;

/**
 * forum.title_max_length and forum.content_max_length bound the title and the post: the new
 * topic and the reply forms take the longest from them (the field's constraint and its
 * maxlength). TopicModel and PostModel hard-coded 120 and 20000 whatever the options.
 */
final class MaxLengthsTest extends KernelTestCase
{
    public function testTheFormsTakeTheLongestFromTheOptions(): void
    {
        if (!class_exists('App\\Kernel')) {
            self::markTestSkipped('Requires the host application kernel (the omnibase harness).');
        }
        self::bootKernel();
        $container = static::getContainer();
        $title = (int) $container->getParameter('forum.title_max_length');
        $content = (int) $container->getParameter('forum.content_max_length');

        $topic = $container->get('form.factory')->create(TopicType::class, new TopicModel(), ['with_content' => true]);
        self::assertSame([$title], self::maxima($topic->get('title')->getConfig()->getOption('constraints')));
        self::assertSame($title, $topic->get('title')->getConfig()->getOption('attr')['maxlength']);
        self::assertSame([$content], self::maxima($topic->get('content')->getConfig()->getOption('constraints')));
        $post = $container->get('form.factory')->create(PostType::class, new PostModel());
        self::assertSame([$content], self::maxima($post->get('content')->getConfig()->getOption('constraints')));

        $hardCoded = [];
        foreach ([TopicModel::class => ['title', 'content'], PostModel::class => ['content']] as $class => $properties) {
            foreach ($properties as $property) {
                foreach ((new \ReflectionProperty($class, $property))->getAttributes(Length::class) as $attribute) {
                    if (null !== $attribute->newInstance()->max) {
                        $hardCoded[] = $class.'::$'.$property;
                    }
                }
            }
        }
        self::assertSame([], $hardCoded, 'no longest written in the models');
    }

    /** @return list<int> */
    private static function maxima(array $constraints): array
    {
        return array_values(array_map(static fn (Length $l) => $l->max, array_filter($constraints, static fn ($c) => $c instanceof Length)));
    }
}
