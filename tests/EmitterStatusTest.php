<?php

declare(strict_types=1);

use Laminas\Diactoros\Response;
use Omegaalfa\Wrouter\Http\Emitter;
use PHPUnit\Framework\TestCase;

final class EmitterStatusTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testEmitOutputsStatusLineBodyAndHeaders(): void
    {
        header_remove();

        $response = (new Response())
            ->withHeader('X-Custom', 'value');
        $response->getBody()->write('payload');

        ob_start();
        $emitter = new Emitter();
        $result = $emitter->emit($response);
        $output = ob_get_clean();

        $this->assertTrue($result);
        $this->assertSame('payload', $output);

        $headers = function_exists('xdebug_get_headers')
            ? xdebug_get_headers()
            : headers_list();
        $this->assertContains('X-Custom: value', $headers);
    }
}
