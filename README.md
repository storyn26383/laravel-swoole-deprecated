# Laravel Swoole (DEPRECATED)

[![Build Status](https://travis-ci.org/storyn26383/laravel-swoole.svg?branch=master)](https://travis-ci.org/storyn26383/laravel-swoole)
[![Coverage Status](https://coveralls.io/repos/github/storyn26383/laravel-swoole/badge.svg?branch=master)](https://coveralls.io/github/storyn26383/laravel-swoole?branch=master)

## Quick Start

### Usage

```php
require __DIR__.'/../bootstrap/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$server = new Swoole\Http\Server('0.0.0.0', 8000);

$server->on('request', function (Swoole\Http\Request $request, Swoole\Http\Response $response) use ($kernel) {
    $illuminateResponse = $kernel->handle(
        $illuminateRequest = Sasaya\LaravelSwoole\Request::createFromSwooleRequest($request)->toIlluminateRequest()
    );

    $response->status($illuminateResponse->status());

    foreach ($illuminateResponse->headers->all() as $key => $value) {
        $response->header($key, head($value));
    }

    $response->end($illuminateResponse->content());

    $kernel->terminate($illuminateRequest, $illuminateResponse);
});

$server->start();
```
