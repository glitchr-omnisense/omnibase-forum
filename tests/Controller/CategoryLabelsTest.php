<?php

namespace Tests\Base\Forum\Controller;

use Base\Forum\Controller\Admin\Crud\CategoryCrudController;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Translation\TranslatableInterface;

/** The categories screen's `polls` field is labelled in the reader's language (forum: form.board_polls), not "Sondages" in every locale. */
final class CategoryLabelsTest extends KernelTestCase
{
    public function testThePollsLabelIsTranslated(): void
    {
        if (!class_exists('App\\Kernel') || !class_exists(CategoryCrudController::class)) {
            self::markTestSkipped('Requires the omnibase harness with omnibase/admin.');
        }
        self::bootKernel();
        $controller = static::getContainer()->get(CategoryCrudController::class);
        $label = null;
        foreach ($controller->configureFields('edit') as $field) {
            if ('polls' === $field->getAsDto()->getProperty()) {
                $label = $field->getAsDto()->getLabel();
            }
        }
        self::assertInstanceOf(TranslatableInterface::class, $label);
        $translator = static::getContainer()->get('translator');
        self::assertSame('Polls allowed', $label->trans($translator, 'en'));
        self::assertSame('Sondages autorisés', $label->trans($translator, 'fr'));
    }
}
