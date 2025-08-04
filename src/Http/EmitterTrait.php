<?php

declare(strict_types=1);


namespace Omegaalfa\Wrouter\Http;

use Psr\Http\Message\ResponseInterface;
use function sprintf;
use function ucwords;

trait EmitterTrait
{
    /**
     * Emit the status line.
     *
     * Emits the status line using the protocol version and status code from
     * the response; if a reason phrase is available, it, too, is emitted.
     *
     * It is important to mention that this method should be called after
     * `emitHeaders()` in order to prevent PHP from changing the status code of
     * the emitted response.
     *
     * @param ResponseInterface $response
     *
     * @return void
     */
    private function emitStatusLine(ResponseInterface $response): void
    {
        $reasonPhrase = $response->getReasonPhrase();
        $statusCode = $response->getStatusCode();
        if (!headers_sent()) {
            header(sprintf(
                'HTTP/%s %d%s',
                $response->getProtocolVersion(),
                $statusCode,
                ($reasonPhrase ? ' ' . $reasonPhrase : '')
            ), true, $statusCode);
        }
    }

    /**
     * Emit response headers.
     *
     * Loops through each header, emitting each; if the header value
     * is an array with multiple values, ensures that each is sent
     * in such a way as to create aggregate headers (instead of replace
     * the previous).
     *
     * @param ResponseInterface $response
     *
     * @return void
     */
    private function emitHeaders(ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();

        foreach ($response->getHeaders() as $header => $values) {
            if (is_string($header)) {
                $name = $this->filterHeader($header);
                $first = !($name === 'Set-Cookie');
                foreach ($values as $value) {
                    if (!headers_sent()) {
                        header(sprintf(
                            '%s: %s',
                            $name,
                            $value
                        ), $first, $statusCode);
                    }
                    $first = false;
                }
            }
        }
    }

    /**
     * Filter a header name to wordcase
     *
     * @param string $header
     *
     * @return string
     */
    private function filterHeader(string $header): string
    {
        return ucwords($header, '-');
    }
}