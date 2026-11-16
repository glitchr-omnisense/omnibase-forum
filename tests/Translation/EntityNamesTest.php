<?php

namespace Tests\Base\Forum\Translation;

use Base\Forum\ForumBundle;
use Base\Service\Model\EntityNouns;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Every entity of the forum has its name in the "entities" catalogues, under the key omnibase gives it
 * (EntityNouns: "forum.category"...), singular and plural, in each language the bundle ships: omnibase/admin titles
 * its screens with it (/admin/categories read "Category", its class name, without one).
 */
final class EntityNamesTest extends TestCase
{
    public function testEachEntityIsNamedInEachLanguage(): void
    {
        $namespaces = EntityNouns::namespacesOf([ForumBundle::class]);
        $root = \dirname(__DIR__, 2);
        $catalogues = glob($root.'/translations/entities+intl-icu.*.yaml') ?: [];
        self::assertNotSame([], $catalogues, 'the bundle ships its entities catalogues');

        foreach ($catalogues as $file) {
            $catalogue = Yaml::parseFile($file);
            foreach (glob($root.'/src/Entity/*.php') ?: [] as $entityFile) {
                $class = 'Base\\Forum\\Entity\\'.basename($entityFile, '.php');
                $key = camel2snake(implode('.', array_unique(explode('\\', str_replace(array_keys($namespaces), array_values($namespaces), $class)))));
                foreach (['_singular', '_plural'] as $noun) {
                    $name = array_reduce(explode('.', $key), static fn (mixed $node, string $part): mixed => \is_array($node) ? ($node[$part] ?? null) : null, $catalogue)[$noun] ?? null;
                    self::assertIsString($name, basename($file).': '.$key.'.'.$noun);
                    self::assertNotSame('', $name);
                }
            }
        }
    }
}
