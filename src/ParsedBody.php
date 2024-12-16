<?php

declare(strict_types=1);

namespace Omegaalfa\Wrouter;

use Psr\Http\Message\ServerRequestInterface;

class ParsedBody
{
	/**
	 * @param  ServerRequestInterface  $request
	 *
	 * @return ServerRequestInterface
	 */
	public function process(ServerRequestInterface $request): ServerRequestInterface
	{
		$contentType = $request->getHeaderLine('Content-Type');
		if($request->getMethod() !== 'GET') {
			foreach($this->supportedContentTypes() as $type) {
				if(str_contains($contentType, $type)) {
					$request = $this->parseBody($request, $type);
					break;
				}
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
		];
	}

	/**
	 * @param  ServerRequestInterface  $request
	 * @param  string                  $contentType
	 *
	 * @return ServerRequestInterface
	 */
	private function parseBody(ServerRequestInterface $request, string $contentType): ServerRequestInterface
	{
		return match ($contentType) {
			'application/json' => $this->parseJson($request),
			'application/x-www-form-urlencoded' => $this->parseFormUrlEncoded($request),
			'application/xml', 'text/xml' => $this->parseXml($request),
			default => $request,
		};
	}

	/**
	 * @param  ServerRequestInterface  $request
	 *
	 * @return ServerRequestInterface
	 */
	private function parseJson(ServerRequestInterface $request): ServerRequestInterface
	{
		$body = $request->getBody()->getContents();
		try {
			$parsedBody = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

			if(!is_array($parsedBody) && !is_object($parsedBody)) {
				throw new \InvalidArgumentException('O corpo da requisição JSON não é válido.');
			}

			return $request->withParsedBody($parsedBody);
		} catch(\JsonException|\InvalidArgumentException $e) {
			// Log or handle the error
			return $request;
		}
	}

	/**
	 * @param  ServerRequestInterface  $request
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
	 * @param  ServerRequestInterface  $request
	 *
	 * @return ServerRequestInterface
	 */
	private function parseXml(ServerRequestInterface $request): ServerRequestInterface
	{
		$body = $request->getBody()->getContents();
		$xml = simplexml_load_string($body, "SimpleXMLElement", LIBXML_NOENT | LIBXML_NOCDATA | LIBXML_NOBLANKS);

		if($xml !== false) {
			try {
				$json = json_encode($xml, JSON_THROW_ON_ERROR);
				return $request->withParsedBody(json_decode($json, true, 512, JSON_THROW_ON_ERROR));
			} catch(\JsonException $e) {
				// Log or handle the error
				return $request;
			}
		}

		return $request;
	}
}