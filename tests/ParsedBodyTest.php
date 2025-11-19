<?php

declare(strict_types=1);

use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\Uri;
use Omegaalfa\Wrouter\Support\ParsedBody;
use PHPUnit\Framework\TestCase;

final class ParsedBodyTest extends TestCase
{
    public function testProcessDoesNothingForGetRequests(): void
    {
        $request = new ServerRequest(
            [],
            [],
            new Uri('/'),
            'GET',
            $this->createStream('{}'),
            ['Content-Type' => ['application/json']]
        );

        $parsed = (new ParsedBody())->process($request);

        $this->assertSame($request, $parsed);
        $this->assertNull($parsed->getParsedBody());
    }

    public function testParsesJsonBody(): void
    {
        $json = json_encode(['name' => 'john', 'age' => 30], JSON_THROW_ON_ERROR);

        $request = new ServerRequest(
            [],
            [],
            new Uri('/'),
            'POST',
            $this->createStream($json),
            ['Content-Type' => ['application/json']]
        );

        $parsed = (new ParsedBody())->process($request);

        $this->assertSame(['name' => 'john', 'age' => 30], $parsed->getParsedBody());
    }

    public function testParsesFormUrlEncodedBody(): void
    {
        $body = 'foo=bar&age=20';

        $request = new ServerRequest(
            [],
            [],
            new Uri('/'),
            'POST',
            $this->createStream($body),
            ['Content-Type' => ['application/x-www-form-urlencoded']]
        );

        $parsed = (new ParsedBody())->process($request);

        $this->assertSame(['foo' => 'bar', 'age' => '20'], $parsed->getParsedBody());
    }

    public function testParsesXmlBody(): void
    {
        $xml = '<root><foo>bar</foo></root>';

        $request = new ServerRequest(
            [],
            [],
            new Uri('/'),
            'POST',
            $this->createStream($xml),
            ['Content-Type' => ['text/xml']]
        );

        $parsed = (new ParsedBody())->process($request);
        $body = $parsed->getParsedBody();

        $this->assertIsArray($body);
        $this->assertSame('bar', $body['foo'] ?? null);
    }

    public function testParsesMultipartBodyFromPostSuperglobal(): void
    {
        $_POST = ['foo' => 'bar', 'age' => '22'];

        $request = new ServerRequest(
            [],
            [],
            new Uri('/'),
            'POST',
            $this->createStream(''),
            ['Content-Type' => ['multipart/form-data']]
        );

        $parsed = (new ParsedBody())->process($request);

        $this->assertSame($_POST, $parsed->getParsedBody());
    }

    private function createStream(string $content): Stream
    {
        $stream = new Stream('php://memory', 'rw');
        if ($content !== '') {
            $stream->write($content);
            $stream->rewind();
        }

        return $stream;
    }
}

