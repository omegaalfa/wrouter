<?php

declare(strict_types=1);

/**
 * BENCHMARK JUSTO DE ROTEADORES PHP
 *
 * Agora com:
 * - Suporte CLI via getopt()
 * - Possibilidade de adicionar um benchmark extra via --path
 * - Sem modificar os benchmarks padrões
 */

use Jaunt\Router;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Omegaalfa\Wrouter\Router\Wrouter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\AppFactory;
// Phroute
use Phroute\Phroute\RouteCollector;
use Phroute\Phroute\Dispatcher;
// Symfony
use Symfony\Component\Routing\Matcher\CompiledUrlMatcher;
use Symfony\Component\Routing\Matcher\Dumper\CompiledUrlMatcherDumper;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

require __DIR__ . '/../vendor/autoload.php';


/* ===================================================
   CLI PARÂMETROS
   =================================================== */

$cliOptions = getopt('', [
    'method::',
    'path::',
    'body::',
    'iterations::',
    'warmup::',
]);

$cliMethod     = $cliOptions['method']     ?? null;
$cliPath       = $cliOptions['path']       ?? null;
$cliBody       = $cliOptions['body']       ?? null;
$cliIterations = isset($cliOptions['iterations']) ? (int)$cliOptions['iterations'] : null;
$cliWarmup     = isset($cliOptions['warmup']) ? (int)$cliOptions['warmup'] : null;

/* ===================================================
   Shared Instances (Justo)
   =================================================== */

final class BenchUtils {
    public static ResponseInterface $dummyResponse;
    public static ServerRequestInterface $dummyRequest;

    public static function init(): void {
        self::$dummyResponse = new Response();
        self::$dummyRequest = new ServerRequest([], [], new Uri('/'), 'GET');
    }
}
BenchUtils::init();

/* ===================================================
   INTERFACE
   =================================================== */

interface BenchRouterAdapterInterface
{
    public function dispatch(ServerRequestInterface $request): ?ResponseInterface;
}

/* ===================================================
   ADAPTERS
   (Mantidos exatamente como estavam)
   =================================================== */

final class WrouterBenchmarkAdapter implements BenchRouterAdapterInterface
{
    private readonly Wrouter $router;

    public function __construct()
    {
        $this->router = new Wrouter();
        registerDefaultRoutes(fn (string $method, string $path, callable $handler) => $this->addRoute($method, $path, $handler));
    }

    public function dispatch(ServerRequestInterface $request): ?ResponseInterface
    {
        $this->router->setRequest($request);
        return $this->router->dispatcher($request->getUri()->getPath());
    }

    private function addRoute(string $method, string $path, callable $handler): void
    {
        $dummyPath = preg_replace('/:([a-zA-Z0-9_]+)/', '0', $path);
        $dummyRequest = new ServerRequest([], [], new Uri($dummyPath), $method);
        $this->router->setRequest($dummyRequest);

        match ($method) {
            'GET' => $this->router->get($path, $handler),
            'POST' => $this->router->post($path, $handler),
            default => throw new RuntimeException("Método {$method} não suportado."),
        };
    }
}

final class JauntBenchmarkAdapter implements BenchRouterAdapterInterface
{
    private Router $router;

    public function __construct()
    {
        $this->router = new Router();

        registerDefaultRoutes(function (string $method, string $path, callable $handler) {
            $this->router->add($method, $path, function ($params = []) use ($handler) {
                return $handler(BenchUtils::$dummyRequest, BenchUtils::$dummyResponse, $params);
            });
        });
    }

    public function dispatch(ServerRequestInterface $request): ?ResponseInterface
    {
        $route = $this->router->match($request->getMethod(), $request->getUri()->getPath());

        if ($route === null) {
            throw new RuntimeException('Rota não encontrada (Jaunt)');
        }

        $response = null;
        foreach ($route['stack'] as $handler) {
            $response = $handler($route['params']);
        }

        return $response instanceof ResponseInterface ? $response : BenchUtils::$dummyResponse;
    }
}

final class PhrouteBenchmarkAdapter implements BenchRouterAdapterInterface
{
    private RouteCollector $collector;
    private Dispatcher $dispatcher;

    public function __construct()
    {
        $this->collector = new RouteCollector();

        registerDefaultRoutes(function (string $method, string $path, callable $handler) {
            $normalized = preg_replace('/:([a-zA-Z_][a-zA-Z0-9_]*)/', '{$1}', $path);

            $this->collector->addRoute($method, $normalized, function (...$args) use ($handler) {
                $params = [];
                if (!empty($args)) {
                    $last = end($args);
                    if (is_array($last)) $params = $last;
                }
                return $handler(BenchUtils::$dummyRequest, BenchUtils::$dummyResponse, $params);
            });
        });

        $this->dispatcher = new Dispatcher($this->collector->getData());
    }

    public function dispatch(ServerRequestInterface $request): ?ResponseInterface
    {
        try {
            $response = $this->dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());
            return $response instanceof ResponseInterface ? $response : BenchUtils::$dummyResponse;
        } catch (\Exception $e) {
            throw new RuntimeException('Rota não encontrada (Phroute): ' . $e->getMessage());
        }
    }
}

final class SymfonyRouterBenchmarkAdapter implements BenchRouterAdapterInterface
{
    private CompiledUrlMatcher $matcher;
    private RequestContext $context;

    public function __construct()
    {
        $routes = new RouteCollection();
        $this->context = new RequestContext();

        registerDefaultRoutes(function (string $method, string $path, callable $handler) use ($routes) {
            $sfPath = preg_replace('/:([a-zA-Z0-9_]+)/', '{$1}', $path);

            $route = new \Symfony\Component\Routing\Route(
                $sfPath,
                ['_handler' => $handler],
                [], [], '', [], [$method]
            );
            $routes->add(md5($method . $path), $route);
        });

        $dumper = new CompiledUrlMatcherDumper($routes);
        $compiledRoutes = $dumper->getCompiledRoutes();

        $this->matcher = new CompiledUrlMatcher($compiledRoutes, $this->context);
    }

    public function dispatch(ServerRequestInterface $request): ?ResponseInterface
    {
        $this->context->setMethod($request->getMethod());

        try {
            $params = $this->matcher->match($request->getUri()->getPath());
        } catch (\Exception $e) {
            throw new RuntimeException("Rota não encontrada (Symfony)");
        }

        $handler = $params['_handler'];
        unset($params['_handler'], $params['_route']);

        return $handler($request, BenchUtils::$dummyResponse, $params);
    }
}

final class SlimBenchmarkAdapter implements BenchRouterAdapterInterface
{
    private \Slim\App $app;

    public function __construct()
    {
        AppFactory::setResponseFactory(new \Laminas\Diactoros\ResponseFactory());
        $this->app = AppFactory::create();

        registerDefaultRoutes(function (string $method, string $path, callable $handler) {
            $slimPath = preg_replace('/:([a-zA-Z0-9_]+)/', '{$1}', $path);
            $this->app->map([$method], $slimPath, $handler);
        });
    }

    public function dispatch(ServerRequestInterface $request): ?ResponseInterface
    {
        return $this->app->handle($request);
    }
}

final class NaiveRouterBenchmarkAdapter implements BenchRouterAdapterInterface
{
    private array $routes = [];

    public function __construct()
    {
        registerDefaultRoutes(fn (string $m, string $p, callable $h) => $this->addRoute($m, $p, $h));
    }

    public function dispatch(ServerRequestInterface $request): ?ResponseInterface
    {
        $pathSegments = array_values(array_filter(explode('/', trim($request->getUri()->getPath(), '/'))));
        $method = $request->getMethod();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;
            if (count($route['segments']) !== count($pathSegments)) continue;

            $params = [];
            $matched = true;

            foreach ($route['segments'] as $index => $segment) {
                if (str_starts_with($segment, ':')) {
                    $params[ltrim($segment, ':')] = $pathSegments[$index];
                    continue;
                }
                if ($segment !== $pathSegments[$index]) {
                    $matched = false;
                    break;
                }
            }

            if ($matched) {
                return ($route['handler'])($request, BenchUtils::$dummyResponse, $params);
            }
        }
        throw new RuntimeException('Rota não encontrada (Naive)');
    }

    private function addRoute(string $method, string $path, callable $handler): void
    {
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        $this->routes[] = ['method' => $method, 'segments' => $segments, 'handler' => $handler];
    }
}

final class RegexRouterBenchmarkAdapter implements BenchRouterAdapterInterface
{
    private array $routes = [];

    public function __construct()
    {
        registerDefaultRoutes(fn (string $m, string $p, callable $h) => $this->addRoute($m, $p, $h));
    }

    public function dispatch(ServerRequestInterface $request): ?ResponseInterface
    {
        $uriPath = $request->getUri()->getPath();
        $method = $request->getMethod();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            if (preg_match($route['regex'], $uriPath, $matches)) {
                $params = array_filter($matches, fn($k) => is_string($k), ARRAY_FILTER_USE_KEY);
                return ($route['handler'])($request, BenchUtils::$dummyResponse, $params);
            }
        }
        throw new RuntimeException('Rota não encontrada (Regex)');
    }

    private function addRoute(string $method, string $path, callable $handler): void
    {
        $regex = '#^' . preg_replace_callback('/:[a-zA-Z0-9_]+/', fn($m) => '(?P<' . ltrim($m[0], ':') . '>[^/]+)', $path) . '$#';
        $this->routes[] = ['method' => $method, 'regex' => $regex, 'handler' => $handler];
    }
}

/* ===================================================
   ROTAS PADRÃO (não alteradas)
   =================================================== */

function registerDefaultRoutes(callable $register): void
{
    $register('GET', '/hello', fn($r, $res) => $res);
    $register('GET', '/health', fn($r, $res) => $res);
    $register('GET', '/benchmark/:id', fn($r, $res) => $res);

    for ($i = 0; $i < 1000; $i++) {
        $register('GET', "/static/route/{$i}", fn($r, $res) => $res);
    }
    for ($i = 0; $i < 1000; $i++) {
        $register('GET', "/user/{$i}/orders/:orderId", fn($r, $res) => $res);
    }
    for ($i = 0; $i < 1000; $i++) {
        $register('GET', "/product/{$i}/variant/:v/color/:c", fn($r, $res) => $res);
    }
    for ($i = 0; $i < 5000; $i++) {
        $register('GET', "/very/long/static/route/path/{$i}", fn($r, $res) => $res);
    }
}

/* ===================================================
   AUXILIARES
   =================================================== */

function createRequest(string $method, string $path): ServerRequestInterface
{
    return new ServerRequest([], [], new Uri($path), $method);
}

function generatePath(string $pattern, int $index): string
{
    return str_contains($pattern, ':')
        ? preg_replace('/:[a-zA-Z0-9_]+/', (string)$index, $pattern)
        : $pattern;
}

function getCpuTime(): float
{
    $u = getrusage();
    return ($u['ru_utime.tv_sec'] + $u['ru_utime.tv_usec'] / 1e6)
        + ($u['ru_stime.tv_sec'] + $u['ru_stime.tv_usec'] / 1e6);
}

function benchmarkRouter(
    BenchRouterAdapterInterface $router,
    string $method,
    string $pathPattern,
    int $iterations,
    int $warmup
): array {

    for ($i = 0; $i < $warmup; $i++) {
        $router->dispatch(createRequest($method, generatePath($pathPattern, $i)));
    }

    gc_collect_cycles();
    $requests = [];
    for ($i = 0; $i < $iterations; $i++) {
        $requests[] = createRequest($method, generatePath($pathPattern, $i));
    }

    $cpuStart = getCpuTime();
    $timeStart = hrtime(true);

    foreach ($requests as $req) {
        $router->dispatch($req);
    }

    $duration = (hrtime(true) - $timeStart) / 1e9;
    $cpuTotal = getCpuTime() - $cpuStart;

    return [
        'duration' => $duration,
        'throughput' => $iterations / $duration,
        'latency' => ($duration / $iterations) * 1e6,
        'cpu' => $cpuTotal,
        'memory' => memory_get_peak_usage(true),
    ];
}

function printComparisonTable(array $results): void
{
    $pad = "%-25s %-12s %-12s %-12s %-12s\n";
    printf($pad, 'Router', 'Duração', 'Req/s', 'Latência', 'CPU');
    echo str_repeat('-', 80) . "\n";

    foreach ($results as $name => $m) {
        printf(
            $pad,
            $name,
            sprintf('%.4f s', $m['duration']),
            sprintf('%.0f', $m['throughput']),
            sprintf('%.2f μs', $m['latency']),
            sprintf('%.4f', $m['cpu']),
        );
    }
}

/* ===================================================
   COMPILAÇÃO DOS ROUTERS
   =================================================== */

function buildBenchRouters(): array
{
    return [
        'Omegaalfa Wrouter' => new WrouterBenchmarkAdapter(),
        'Jaunt Router'      => new JauntBenchmarkAdapter(),
        'Phroute Router'    => new PhrouteBenchmarkAdapter(),
        'Slim Framework'    => new SlimBenchmarkAdapter(),
        'Symfony (Compilado)' => new SymfonyRouterBenchmarkAdapter(),
        'Regex Router'      => new RegexRouterBenchmarkAdapter(),
        'Naive Router'      => new NaiveRouterBenchmarkAdapter(),
    ];
}

/* ===================================================
   MAIN
   =================================================== */

function main(): void
{
    global $cliMethod, $cliPath, $cliBody, $cliIterations, $cliWarmup;

    // defaults
    $method = $cliMethod ?: 'GET';
    $iterations = $cliIterations ?: 3000;
    $warmup = $cliWarmup ?: 10;

    // Benchmarks padrão
    $benchmarks = [
        'Rota simples'           => '/hello',
        'Rota estática curta'    => '/static/route/50',
        'Rota dinâmica 1 param'  => '/user/50/orders/123',
        'Rota dinâmica 2 params' => '/product/50/variant/10/color/blue',
        'Rota estática profunda' => '/very/long/static/route/path/500',
    ];

    // Adiciona o benchmark extra do usuário caso exista
    if ($cliPath) {
        $benchmarks["Rota custom CLI"] = $cliPath;
    }

    echo "Construindo e compilando rotas... aguarde...\n";
    $routers = buildBenchRouters();

    foreach ($benchmarks as $label => $pathPattern) {
        echo "\n\n========================================\n";
        echo   "Benchmark: {$label}\n";
        echo   "========================================\n";

        $results = [];
        foreach ($routers as $name => $router) {
            gc_collect_cycles();

            $metrics = benchmarkRouter($router, $method, $pathPattern, $iterations, $warmup);
            $results[$name] = $metrics;
        }

        uasort($results, fn($a, $b) => $b['throughput'] <=> $a['throughput']);

        printComparisonTable($results);
    }
}

main();
