<?php

declare(strict_types=1);

namespace Purple\Tests\Prompt;

use Purple\Prompt\StringPromptTemplate;
use Purple\Tests\Testing\TestCase;

final class StringPromptTemplateTest extends TestCase
{
    public function testRendersScalarAndStructuredPlaceholders(): void
    {
        $template = new StringPromptTemplate('Classify {{ title }} with tags {{ tags }}.');

        $this->assertSame(
            'Classify Jacket with tags ["winter","sale"].',
            $template->render([
                'title' => 'Jacket',
                'tags' => ['winter', 'sale'],
            ]),
        );
    }
}
