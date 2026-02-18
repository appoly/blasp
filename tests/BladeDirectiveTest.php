<?php

namespace Blaspsoft\Blasp\Tests;

use Illuminate\Support\Facades\Blade;

class BladeDirectiveTest extends TestCase
{
    protected function renderBlade(string $template, array $data = []): string
    {
        $compiled = Blade::compileString($template);

        ob_start();
        extract($data);
        eval('?>' . $compiled);
        return ob_get_clean();
    }

    public function test_clean_directive_masks_profane_text()
    {
        $output = $this->renderBlade('@clean($text)', ['text' => 'This is a fucking sentence']);

        $this->assertStringNotContainsString('fucking', $output);
        $this->assertStringContainsString('*', $output);
    }

    public function test_clean_directive_passes_clean_text_unchanged()
    {
        $output = $this->renderBlade('@clean($text)', ['text' => 'This is a clean sentence']);

        $this->assertSame('This is a clean sentence', $output);
    }

    public function test_clean_directive_escapes_html_for_xss_safety()
    {
        $output = $this->renderBlade('@clean($text)', ['text' => '<script>alert("xss")</script>']);

        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);
    }
}
