<?php

declare(strict_types=1);

namespace Omegaalfa\Wrouter\Http;

enum HttpMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case DELETE = 'DELETE';
    case PATCH = 'PATCH';
    case HEAD = 'HEAD';
    case OPTIONS = 'OPTIONS';
    case TRACE = 'TRACE';
    case CONNECT = 'CONNECT';

    /**
     * Check if a method is valid
     */
    public static function isValid(string $method): bool
    {
        return self::tryFrom(strtoupper($method)) !== null;
    }

    /**
     * Get all HTTP methods as array
     *
     * @return array<string>
     */
    public static function all(): array
    {
        return array_map(fn(self $method) => $method->value, self::cases());
    }


    /**
     * Get method description
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::GET => 'Retrieve data from server',
            self::POST => 'Submit data to server',
            self::PUT => 'Update/replace resource on server',
            self::DELETE => 'Remove resource from server',
            self::PATCH => 'Partially update resource on server',
            self::HEAD => 'Retrieve headers only',
            self::OPTIONS => 'Get allowed methods for resource',
            self::TRACE => 'Perform message loop-back test',
            self::CONNECT => 'Establish tunnel to server'
        };
    }
}