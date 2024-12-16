<?php

namespace Omegaalfa\Wrouter;

use ReflectionException;
use ReflectionFunction;

class RouterCache
{

	/**
	 * @var string
	 */
	protected string $cachePath = __DIR__ . '/cache/cache_routes.php';

	/**
	 * @var array
	 */
	protected array $compiledRoutes;

	/**
	 * @var bool
	 */
	public bool $isCached = false;

	/**
	 * @var bool
	 */
	public bool $generateRoutes = false;


	/**
	 * @param  bool  $cached
	 *
	 * @return void
	 */
	protected function isCache(bool $cached = false): void
	{
		if($cached) {
			$this->isCached = true;
		}
	}

	/**
	 * @param  bool  $generate
	 *
	 * @return void
	 */
	protected function isGenerateRoutes(bool $generate = false): void
	{
		if($generate) {
			$this->generateRoutes = true;
		}
	}


	private function serializeRoutes(array $routeCompiled): array
	{
		$data = [];
		foreach($routeCompiled as $key => $parameter) {
			if(str_contains($key, '{') && str_contains($key, '}')) {
				$regex = preg_replace('/{([^{}]+)}/', '(\d+)', $key);
				$key = "/" . str_replace("/", "\/", $regex) . "/";
			}

			$data[$key] = $parameter;
		}

		return $data;
	}

	/**
	 * @param  callable  $closure
	 *
	 * @return string
	 * @throws ReflectionException
	 */
	private function closureToString(callable $closure): string
	{
		$reflection = new ReflectionFunction($closure);
		$file = \file($reflection->getFileName());
		$start = $reflection->getStartLine() - 1;
		$end = $reflection->getEndLine();
		$code = implode("", array_slice($file, $start, $end - $start));

		// Clean up the closure code
		$code = preg_replace('/\s+/', ' ', $code); // Remove extra whitespaces
		$code = preg_replace('/^.*function/', 'function', $code); // Remove everything before the function keyword
		$code = str_replace(');', '', $code); // Remove return statements

		return $code;
	}


	/**
	 * @param  array   $routes
	 * @param  string  $method
	 *
	 * @return void
	 * @throws ReflectionException
	 */
	private function dump(array $routes, string $method): void
	{
		$directory = dirname($this->cachePath);
		if(!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
			throw new \RuntimeException(sprintf('Directory "%s" was not created', $directory));
		}

		$cacheFile = fopen($this->cachePath, 'wb');
		if($cacheFile === false) {
			throw new \RuntimeException('Failed to open cache file for writing.');
		}


		fwrite($cacheFile, "<?php\n");
		fwrite($cacheFile, "use Psr\Http\Message\RequestInterface;\n");
		fwrite($cacheFile, "use Psr\Http\Message\ResponseInterface;\n");
		fwrite($cacheFile, "function getRoutesCached(){\n");
		fwrite($cacheFile, "return [\n");

		foreach($routes as $path => $handler) {
			if(is_callable($handler)) {
				$handlerCode = $this->closureToString($handler);
				if($handlerCode) {
					fwrite($cacheFile, "  '{$path}' => [$method => $handler],\n");
				}
			} else {
				fwrite($cacheFile, "  '{$path}' => ['$method' => $handler],\n");
			}
		}

		fwrite($cacheFile, "];");
		fwrite($cacheFile, "}");
		fclose($cacheFile);
	}

	/**
	 * @param  string    $path
	 * @param  string    $method
	 * @param  callable  $handler
	 *
	 * @return void
	 * @throws ReflectionException
	 */
	public function generateRoutes(string $path, string $method, callable $handler): void
	{
		$this->compiledRoutes[$path] = $this->closureToString($handler);
		$routes = $this->serializeRoutes($this->compiledRoutes);
		$this->dump($routes, $method);
	}
}