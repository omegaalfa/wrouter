<?php

declare(strict_types=1);

use Omegaalfa\Wrouter\Http\HttpMethod;
use PHPUnit\Framework\TestCase;

final class HttpMethodTest extends TestCase
{
    public function testIsValidRecognizesStandardMethods(): void
    {
        $this->assertTrue(HttpMethod::isValid('GET'));
        $this->assertTrue(HttpMethod::isValid('post'));
        $this->assertTrue(HttpMethod::isValid('PuT'));
        $this->assertFalse(HttpMethod::isValid('FOO'));
        $this->assertFalse(HttpMethod::isValid(''));
    }

    public function testAllReturnsAllMethodsAsUppercaseStrings(): void
    {
        $methods = HttpMethod::all();

        $this->assertContains('GET', $methods);
        $this->assertContains('POST', $methods);
        $this->assertContains('PUT', $methods);
        $this->assertContains('DELETE', $methods);
        $this->assertContains('PATCH', $methods);
        $this->assertContains('HEAD', $methods);
        $this->assertContains('OPTIONS', $methods);
        $this->assertContains('TRACE', $methods);
        $this->assertContains('CONNECT', $methods);
    }

    public function testGetDescriptionReturnsHumanReadableText(): void
    {
        $this->assertSame(
            'Retrieve data from server',
            HttpMethod::GET->getDescription()
        );

        $this->assertSame(
            'Submit data to server',
            HttpMethod::POST->getDescription()
        );

        $this->assertSame(
            'Remove resource from server',
            HttpMethod::DELETE->getDescription()
        );
    }
}

