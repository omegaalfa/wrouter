<?php

declare(strict_types=1);

use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\Uri;
use Omegaalfa\Wrouter\Support\ParsedBody;
use PHPUnit\Framework\TestCase;

final class ParsedBodyHelperTest extends TestCase
{
    public function testSupportedContentTypesIncludesJson(): void
    {
        $parsed = new ParsedBody();
        $method = new \ReflectionMethod(ParsedBody::class, 'supportedContentTypes');
        $method->setAccessible(true);

        $types = $method->invoke($parsed);

        $this->assertContains('application/json', $types);
        $this->assertContains('application/x-www-form-urlencoded', $types);
    }

    public function testParseBodyReturnsOriginalWhenTypeNotSupported(): void
    {
        $request = $this->createRequest('PUT', 'test', 'application/unknown');
        $method = new \ReflectionMethod(ParsedBody::class, 'parseBody');
        $method->setAccessible(true);

        $result = $method->invoke($parsed = new ParsedBody(), $request, 'application/unknown');

        $this->assertSame($request, $result);
    }

    private function createRequest(string $method, string $body, string $contentType): ServerRequest
    {
        $stream = new Stream('php://memory', 'rw');
        if ($body !== '') {
            $stream->write($body);
            $stream->rewind();
        }

        return new ServerRequest([], [], new Uri('/'), $method, $stream, ['Content-Type' => [$contentType]]);
    }
}
