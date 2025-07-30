<?php

declare(strict_types=1);

namespace Omegaalfa\Wrouter\Support;

use Psr\Http\Message\ServerRequestInterface;

class ParsedBody
{
    /**
     * @param ServerRequestInterface $request
     *
     * @return ServerRequestInterface
     */
    public function process(ServerRequestInterface $request): ServerRequestInterface
    {
        $contentType = $request->getHeaderLine('Content-Type');
        if ($request->getMethod() !== 'GET') {
            $type = array_find($this->supportedContentTypes(), fn($t) => str_contains($contentType, $t));
            if ($type !== null) {
                $request = $this->parseBody($request, $type);
            }
        }

        return $request;
    }

    /**
     * @return string[]
     */
    private function supportedContentTypes(): array
    {
        return [
            'application/json',
            'application/x-www-form-urlencoded',
            'application/xml',
            'text/xml',
            'multipart/form-data',
        ];
    }

    /**
     * @param ServerRequestInterface $request
     * @param string $contentType
     *
     * @return ServerRequestInterface
     */
    private function parseBody(ServerRequestInterface $request, string $contentType): ServerRequestInterface
    {
        return match ($contentType) {
            'application/json' => $this->parseJson($request),
            'application/x-www-form-urlencoded' => $this->parseFormUrlEncoded($request),
            'application/xml', 'text/xml' => $this->parseXml($request),
            'multipart/form-data' => $this->parseMultipart($request),
            default => $request,
        };
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ServerRequestInterface
     */
    private function parseJson(ServerRequestInterface $request): ServerRequestInterface
    {
        $body = $request->getBody()->getContents();
        try {
            $parsedBody = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($parsedBody)) {
                throw new \InvalidArgumentException('O corpo da requisição JSON não é válido.');
            }

            return $request->withParsedBody($parsedBody);
        } catch (\JsonException|\InvalidArgumentException $e) {
            // Log or handle the error
            return $request;
        }
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ServerRequestInterface
     */
    private function parseFormUrlEncoded(ServerRequestInterface $request): ServerRequestInterface
    {
        $body = $request->getBody()->getContents();
        parse_str($body, $parsedBody);
        return $request->withParsedBody($parsedBody);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ServerRequestInterface
     */
    private function parseXml(ServerRequestInterface $request): ServerRequestInterface
    {
        $body = $request->getBody()->getContents();
        $xml = simplexml_load_string($body, "SimpleXMLElement", LIBXML_NOENT | LIBXML_NOCDATA | LIBXML_NOBLANKS);

        if ($xml !== false) {
            try {
                $json = json_encode($xml, JSON_THROW_ON_ERROR);
                return $request->withParsedBody(json_decode($json, true, 512, JSON_THROW_ON_ERROR));
            } catch (\JsonException $e) {
                // Log or handle the error
                return $request;
            }
        }

        return $request;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ServerRequestInterface
     */
    private function parseMultipart(ServerRequestInterface $request): ServerRequestInterface
    {
        $parsedBody = $request->getParsedBody();
        if (empty($parsedBody) && !empty($_POST)) {
            return $request->withParsedBody($_POST);
        }

        return $request;
    }
}