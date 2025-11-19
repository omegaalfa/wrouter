<?php

namespace Omegaalfa\Wrouter\Router;

use ReflectionException;
use ReflectionFunction;

class RouterCache
{
	/**
	 * Routes generated at runtime in this process. These are kept so that
	 * generateRoutes() can be used during the same process even if closure
	 * source extraction fails for writing to disk (useful for tests).
	 *
	 * @var array<int,mixed>
	 */
	private static array $runtimeRoutes = [];

	/**
	 * @var string
	 */
	protected string $cachePath = __DIR__ . '/cache/cache_routes.php';

	/**
	 * Return the cache full path
	 */
	public function getCachePath(): string
	{
		return $this->cachePath;
	}

	/**
	 * Load routes from cache file. Returns an array of routes or empty array.
	 * Each route must be an array with keys: 'method','path','handler','middlewares'
	 * The cached file is a PHP file that returns the routes array.
	 *
	 * @return array<int, mixed>
	 */
	public function loadCachedRoutes(): array
	{
		if (!file_exists($this->cachePath)) {
			return [];
		}

		$routes = include $this->cachePath;
		if (!is_array($routes)) {
			return [];
		}

		// If handlers were stored as source strings, attempt to eval them into Closures.
		foreach ($routes as $i => $route) {
			if (!isset($route['handler'])) {
				continue;
			}

			// Handler persisted as metadata (file,start,end)
			if (is_array($route['handler']) && isset($route['handler']['file'], $route['handler']['start'], $route['handler']['end'])) {
				$file = $route['handler']['file'];
				$start = (int) $route['handler']['start'];
				$end = (int) $route['handler']['end'];

				try {
					$code = $this->getClosureSourceFromFile($file, $start, $end);
					if (is_string($code) && $code !== '') {
						$closure = eval('return ' . $code . ';');
						if ($closure instanceof \Closure) {
							$routes[$i]['handler'] = $closure;
							// try to rehydrate middlewares from references
							$mwInstances = [];
							foreach ($route['middlewares'] ?? [] as $mwRef) {
								if (is_string($mwRef) && class_exists($mwRef)) {
									try {
										$inst = new $mwRef();
										$mwInstances[] = $inst;
									} catch (\Throwable $e) {
										// ignore middleware instantiation failures
									}
								} elseif (is_string($mwRef) && is_callable($mwRef)) {
									try {
										$maybe = $mwRef();
										if ($maybe instanceof \Psr\Http\Server\MiddlewareInterface) {
											$mwInstances[] = $maybe;
										}
									} catch (\Throwable $e) {
										// ignore
									}
								}
							}

							$routes[$i]['middlewares'] = $mwInstances;
							continue;
						}
					}
				} catch (\Throwable $e) {
					// fallthrough: leave handler as metadata and let Router ignore it
				}
			}

			// Backwards compatibility: if handler was stored as a string containing the closure source
			if (is_string($route['handler'])) {
				$code = $route['handler'];
				try {
					$closure = eval('return ' . $code . ';');
					if ($closure instanceof \Closure) {
						$routes[$i]['handler'] = $closure;
					}
				} catch (\ParseError|\Throwable $e) {
					// leave as-is; router will ignore invalid entries
				}
			}
		}

		return $routes;
	}

	/**
	 * Generate or update the routes cache.
	 * Note: handlers must be Closures so we can reflect and extract their source.
	 * Middlewares are not persisted (will be saved as empty array) to avoid object export issues.
	 *
	 * @param string $path
	 * @param string $method
	 * @param callable $handler
	 * @param array<int,mixed> $middlewares
	 *
	 * @return void
	 * @throws ReflectionException
	 */
	public function generateRoutes(string $path, string $method, callable $handler, array $middlewares = []): void
	{
		if (!($handler instanceof \Closure)) {
			throw new \InvalidArgumentException('Handler must be a Closure to be cached.');
		}

		$routes = [];
		if (file_exists($this->cachePath)) {
			$existing = include $this->cachePath;
			if (is_array($existing)) {
				$routes = $existing;
			}
		}

		// Prepare handler metadata (file + start/end lines) so other processes
		// can reconstruct the closure by reading the original file.
		$ref = new ReflectionFunction($handler);
		$file = $ref->getFileName();
		$start = $ref->getStartLine();
		$end = $ref->getEndLine();

		$handlerMeta = [
			'file' => $file,
			'start' => $start,
			'end' => $end,
		];

		// Convert middlewares to serializable references (class-string or callable string)
		$mwRefs = [];
		foreach ($middlewares as $m) {
			if (is_object($m)) {
				$mwRefs[] = get_class($m);
				continue;
			}

			if (is_string($m)) {
				$mwRefs[] = $m;
				continue;
			}

			if (is_callable($m) && is_string($m)) {
				$mwRefs[] = $m;
				continue;
			}

			// unsupported middleware value for persistence: skip
		}

		// add the new route definition. Persist the handler as metadata
		// (we keep the actual Closure in runtimeRoutes for same-process usage).
		$routes[] = [
			'method' => strtoupper($method),
			'path' => $path,
			'handler' => $handlerMeta,
			// store middleware references (class-names or callable strings)
			'middlewares' => $mwRefs,
		];

		// Keep a runtime copy so the same process can use the cached route immediately.
		self::$runtimeRoutes[] = [
			'method' => strtoupper($method),
			'path' => $path,
			'handler' => $handler,
			'middlewares' => $middlewares,
		];

		$this->writeCacheFile($routes);
	}

	/**
	 * Return routes generated during this process via generateRoutes().
	 * @return array<int,mixed>
	 */
	public static function getRuntimeRoutes(): array
	{
		return self::$runtimeRoutes;
	}

	/**
	 * Clear runtime routes (useful for tests to force loading from disk).
	 *
	 * @return void
	 */
	public static function clearRuntimeRoutes(): void
	{
		self::$runtimeRoutes = [];
	}

	/**
	 * Write the PHP cache file containing an array of route definitions.
	 * We reconstruct each handler by extracting the closure source from its file.
	 *
	 * @param array<int,mixed> $routes
	 *
	 * @return void
	 */
	private function writeCacheFile(array $routes): void
	{
		$dir = dirname($this->cachePath);
		if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }

		$out = "<?php\n\nreturn [\n";

		foreach ($routes as $route) {

			$method = var_export($route['method'] ?? '', true);
			$path = var_export($route['path'] ?? '', true);
			$middlewares = var_export($route['middlewares'] ?? [], true);

			$handlerCode = '';
			if (isset($route['handler'])) {
				if (is_array($route['handler']) && isset($route['handler']['file'], $route['handler']['start'], $route['handler']['end'])) {
					// persist metadata reference to original file and lines
					$handlerCode = var_export($route['handler'], true);
				} elseif ($route['handler'] instanceof \Closure) {
					// store the closure source as fallback (not preferred between processes)
					$handlerCode = $this->getClosureSource($route['handler']);
					$handlerCode = var_export($handlerCode, true);
				} else {
					// store a noop closure as string
					$handlerCode = var_export('function() { return null; }', true);
				}
			} else {
				$handlerCode = var_export('function() { return null; }', true);
			}

			$out .= "    [\n";
			$out .= "        'method' => $method,\n";
			$out .= "        'path' => $path,\n";
			$out .= "        'handler' => $handlerCode,\n";

			// handler_code: fallback string containing closure source when available
			$handlerCodeFallback = var_export('', true);
			if (isset($route['handler'])) {
				if (is_array($route['handler']) && isset($route['handler']['file'], $route['handler']['start'], $route['handler']['end'])) {
					$code = $this->getClosureSourceFromFile($route['handler']['file'], (int)$route['handler']['start'], (int)$route['handler']['end']);
					if ($code !== '') {
						$handlerCodeFallback = var_export($code, true);
					}
				} elseif ($route['handler'] instanceof \Closure) {
					$code = $this->getClosureSource($route['handler']);
					$handlerCodeFallback = var_export($code, true);
				}
			}

			$out .= "        'handler_code' => $handlerCodeFallback,\n";
			$out .= "        'middlewares' => $middlewares,\n";
			$out .= "    ],\n";
		}

		$out .= "];\n";

		$written = @file_put_contents($this->cachePath, $out, LOCK_EX);
		if ($written === false) {
			throw new \RuntimeException('Unable to write router cache file to ' . $this->cachePath);
		}
	}

	/**
	 * Extract the source code for a Closure using ReflectionFunction.
	 * Returns a string that can be embedded directly in PHP code (e.g. "function(...) { ... }").
	 *
	 * @param \Closure $closure
	 * @return string
	 * @throws ReflectionException
	 */
	private function getClosureSource(\Closure $closure): string
	{
		$ref = new ReflectionFunction($closure);
		$file = $ref->getFileName();
		$start = $ref->getStartLine();
		$end = $ref->getEndLine();

		$lines = file($file);
		$code = '';
		// Reflection start/end are 1-indexed
		for ($i = $start - 1; $i < $end; $i++) {
			$code .= $lines[$i];
		}

		$code = trim($code);

		// Attempt to clean surrounding characters: if the closure is part of a larger expression
		// (e.g. $router->get('/x', function(...) { ... });) we try to find the substring starting
		// at the first occurrence of "function" or "static function" or "fn(".
		$pos = null;
		foreach (["function", "static function", "fn("] as $needle) {
			$p = strpos($code, $needle);
			if ($p !== false) { $pos = $p; break; }
		}

		if ($pos !== null) {
			$code = substr($code, $pos);
		}

		// Ensure code ends appropriately (remove trailing ")" or ";" if present at line end)
		$code = rtrim($code, ";\n\r ");

		return $code;
	}

	/**
	 * Extract closure source from a file given start and end lines.
	 * Returns the closure code as string (e.g. "function(...) { ... }").
	 *
	 * @param string $file
	 * @param int $start
	 * @param int $end
	 * @return string
	 */
	private function getClosureSourceFromFile(string $file, int $start, int $end): string
	{
		if (!is_readable($file)) {
			return '';
		}

		$lines = @file($file);
		if ($lines === false) {
			return '';
		}

		$code = '';
		$max = min($end, count($lines));
		for ($i = max(0, $start - 1); $i < $max; $i++) {
			$code .= $lines[$i];
		}

		$code = trim($code);

		// Try to find the start of the closure within the extracted block
		$pos = null;
		foreach (["function", "static function", "fn("] as $needle) {
			$p = strpos($code, $needle);
			if ($p !== false) { $pos = $p; break; }
		}

		if ($pos !== null) {
			$code = substr($code, $pos);
		}

		$code = rtrim($code, ";\n\r ");

		return $code;
	}

}