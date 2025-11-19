<?php

declare(strict_types=1);

use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\Uri;
use Omegaalfa\Wrouter\Support\ParsedBody;
use PHPUnit\Framework\TestCase;

final class ParsedBodyInvalidTest extends TestCase
{
    public function testProcessHandlesInvalidJsonWithoutException(): void
    {
        $request = $this->makeRequest('POST', '{invalid-json', 'application/json');

        $parsed = (new ParsedBody())->process($request);

        $this->assertNull($parsed->getParsedBody());
    }

    public function testProcessHandlesMalformedXmlGracefully(): void
    {
        $request = $this->makeRequest('POST', '<root><foo></root>', 'application/xml');

        $parsed = (new ParsedBody())->process($request);
        $this->assertNull($parsed->getParsedBody());
    }

    public function testProcessLeavesMultipartWhenPostEmpty(): void
    {
        $_POST = [];

        $request = $this->makeRequest('POST', '', 'multipart/form-data');

        $parsed = (new ParsedBody())->process($request);

        $this->assertNull($parsed->getParsedBody());
    }

    private function makeRequest(string $method, string $body, string $contentType): ServerRequest
    {
        $stream = new Stream('php://memory', 'rw');
        if ($body !== '') {
            $stream->write($body);
            $stream->rewind();
        }

        return new ServerRequest([], [], new Uri('/'), $method, $stream, ['Content-Type' => [$contentType]]);
    }
}

