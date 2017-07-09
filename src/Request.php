<?php

namespace Sasaya\LaravelSwoole;

class Request
{
    protected $request;

    public function __construct(\Swoole\Http\Request $request)
    {
        $this->request = $request;
    }

    public static function createFromSwooleRequest(\Swoole\Http\Request $request)
    {
        return new static($request);
    }

    public function uri()
    {
        $uri = $this->header['origin'] . $this->server['request_uri'];

        if (array_key_exists('query_string', $this->server)) {
            $uri .= "?{$this->server['query_string']}";
        }

        return $uri;
    }

    public function method()
    {
        return $this->server['request_method'];
    }

    public function parameters()
    {
        return $this->post + $this->get;
    }

    public function cookies()
    {
        return $this->cookie;
    }

    public function files()
    {
        return $this->files;
    }

    public function server()
    {
        $headers = [];

        foreach ($this->header as $key => $value) {
            $headers['http_' . str_replace('-', '_', $key)] = $value;
        }

        return array_change_key_case(array_merge($this->server, $headers), CASE_UPPER);
    }

    public function content()
    {
        return $this->request->rawContent();
    }

    public function toIlluminateRequest()
    {
        return \Illuminate\Http\Request::create(
            $this->uri(),
            $this->method(),
            $this->parameters(),
            $this->cookies(),
            $this->files(),
            $this->server(),
            $this->content()
        );
    }

    public function __get($key)
    {
        if (in_array($key, ['get', 'post', 'header', 'server', 'cookie', 'files'])) {
            return property_exists($this->request, $key) ? $this->request->$key : [];
        }
    }
}
