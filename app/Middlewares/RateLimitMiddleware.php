<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Handlers\ErrorHandler;
use DI\Attribute\Inject;
use Predis\ClientInterface;
use Predis\Pipeline\Pipeline;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Slim\Exception\HttpTooManyRequestsException;
use Throwable;

final class RateLimitMiddleware implements MiddlewareInterface
{
    #[Inject]
    private ClientInterface $redis;

    #[Inject]
    private LoggerInterface $logger;

    public function __construct(private readonly string $bucket = 'default') {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() === 'OPTIONS') {
            return $handler->handle($request);
        }

        $config = $this->resolveConfig();
        $limit = $config['limit'];
        $window = $config['window'];

        $now = now()->getTimestamp();
        $windowStart = intdiv($now, $window) * $window;
        $resetAfter = ($windowStart + $window) - $now;

        $key = sprintf(
            'rate-limit:%s:%s:%d',
            $this->bucket,
            $this->identity($request, $config['by'] ?? ['ip']),
            $windowStart
        );

        try {
            [$hits] = $this->redis->pipeline(function (Pipeline $pipe) use ($key, $resetAfter) {
                $pipe->incr($key);
                $pipe->expire($key, $resetAfter);
            });
        } catch (Throwable $e) {
            $this->logger->error('Rate limiter failure', ['bucket' => $this->bucket, 'exception' => $e]);

            if (($config['on_failure'] ?? 'closed') === 'open') {
                return $handler->handle($request);
            }

            throw $e;
        }

        if (!is_int($hits)) {
            throw new RuntimeException('Unexpected response from rate limit store');
        }

        $blocked = $hits > $limit;
        $remaining = max(0, $limit - $hits);

        $headers = [
            'RateLimit-Limit' => (string) $limit,
            'RateLimit-Remaining' => (string) $remaining,
            'RateLimit-Reset' => (string) $resetAfter,
        ];

        if ($blocked) {
            $headers['Retry-After'] = (string) $resetAfter;

            throw new HttpTooManyRequestsException(
                ErrorHandler::withErrorHeaders($request, $headers)
            );
        }

        $response = $handler->handle($request);

        foreach ($headers as $name => $value) {
            if (!$response->hasHeader($name)) {
                $response = $response->withHeader($name, $value);
            }
        }

        return $response;
    }

    /** @return array<string, mixed> */
    private function resolveConfig(): array
    {
        $config = config('rate-limit');

        return $config['buckets'][$this->bucket] ?? $config['default'];
    }

    /** @param list<string> $by */
    private function identity(ServerRequestInterface $request, array $by): string
    {
        $parts = [];

        if (in_array('ip', $by, true)) {
            $address = $request->getServerParams()['REMOTE_ADDR'] ?? null;
            $packed = is_string($address) ? inet_pton($address) : false;

            if ($packed === false) {
                throw new RuntimeException('A valid client IP address is required for rate limiting');
            }

            $parts[] = $packed;
        }

        if (in_array('email', $by, true)) {
            $body = $request->getParsedBody();

            $email = is_array($body) && is_string($body['email'] ?? null) ? $body['email'] : '';

            $parts[] = mb_strtolower(trim($email));
        }

        if (in_array('user', $by, true)) {
            $userId = $request->getAttribute('user_id');

            if (!is_int($userId) && !is_string($userId)) {
                throw new RuntimeException('Authenticated user is required for this rate limit bucket');
            }

            $parts[] = $userId;
        }

        return hashToken(implode('|', $parts));
    }
}
