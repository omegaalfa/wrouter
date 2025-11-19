<?php

declare(strict_types=1);

use Omegaalfa\Wrouter\Http\HttpMethod;
use PHPUnit\Framework\TestCase;

final class HttpMethodDescriptionTest extends TestCase
{
    public function testGetDescriptionIncludesAllMethods(): void
    {
        $expected = [
            'GET' => 'Retrieve data from server',
            'POST' => 'Submit data to server',
            'PUT' => 'Update/replace resource on server',
            'DELETE' => 'Remove resource from server',
            'PATCH' => 'Partially update resource on server',
            'HEAD' => 'Retrieve headers only',
            'OPTIONS' => 'Get allowed methods for resource',
            'TRACE' => 'Perform message loop-back test',
            'CONNECT' => 'Establish tunnel to server',
        ];

        foreach ($expected as $name => $description) {
            $method = HttpMethod::from($name);
            $this->assertSame($description, $method->getDescription());
        }
    }
}
