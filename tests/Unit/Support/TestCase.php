<?php

declare(strict_types=1);

namespace IntelligenceCloud\Tests\Unit\Support;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use IntelligenceCloud\Auth\TokenStore;
use IntelligenceCloud\Client;
use Psr\Http\Message\RequestInterface;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /** @var list<array{request:RequestInterface}> */
    protected array $history = [];
    /** @var list<float> */
    protected array $sleeps = [];
    protected ?MockHandler $mock = null;

    /** A client over a queue of scripted responses. No network. */
    protected function scripted(array $responses, ?TokenStore $store = null, array $opts = []): Client
    {
        $this->history = [];
        $this->sleeps = [];
        $this->mock = new MockHandler($responses);
        $stack = HandlerStack::create($this->mock);
        $stack->push(Middleware::history($this->history));
        return new Client(...($opts + [
            'email' => 'integration@example.com',
            'password' => 'pw',
            'tokenStore' => $store,
            'httpClient' => new GuzzleClient(['handler' => $stack]),
            'sleeper' => function (float $s): void {
                $this->sleeps[] = $s;
            },
        ]));
    }

    /** A client over the stateful fake server. */
    protected function faked(FakeServer $server, array $opts = []): Client
    {
        $this->sleeps = [];
        $stack = HandlerStack::create($server->handler());
        return new Client(...($opts + [
            'email' => 'admin@example.com',
            'password' => 'pw',
            'httpClient' => new GuzzleClient(['handler' => $stack]),
            'sleeper' => function (float $s): void {
                $this->sleeps[] = $s;
            },
        ]));
    }

    protected static function json(int $status, mixed $body, array $headers = []): Response
    {
        return new Response($status, $headers + ['Content-Type' => 'application/json'], json_encode($body, JSON_THROW_ON_ERROR));
    }

    protected static function login(string $access = 'at1', string $refresh = 'rt1', int $expiresIn = 899): Response
    {
        return self::json(200, ['access_token' => $access, 'refresh_token' => $refresh, 'token_type' => 'Bearer', 'expires_in' => $expiresIn, 'user' => ['id' => 'u']]);
    }

    protected static function me(string $role = 'frontline_user'): Response
    {
        return self::json(200, ['data' => ['id' => 'u', 'role' => $role, 'reseller_id' => '00000000-0000-4000-8000-0000000000aa', 'unexpected_flag' => true]]);
    }

    protected function request(int $i): RequestInterface
    {
        return $this->history[$i]['request'];
    }

    /** @return list<string> "METHOD /path?query" for every request sent */
    protected function sent(): array
    {
        return array_map(static function (array $h): string {
            $u = $h['request']->getUri();
            return $h['request']->getMethod() . ' ' . preg_replace('#^/api/v1#', '', $u->getPath()) . ($u->getQuery() !== '' ? '?' . $u->getQuery() : '');
        }, $this->history);
    }

    /** @return array<string,mixed> */
    protected function bodyOf(int $i): array
    {
        return json_decode((string) $this->request($i)->getBody(), true) ?? [];
    }
}
