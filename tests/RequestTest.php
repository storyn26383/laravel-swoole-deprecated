<?php

namespace Tests;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Sasaya\LaravelSwoole\Request;

class RequestTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testMagicGet()
    {
        $mock = m::mock(\Swoole\Http\Request::class);

        $array = ['foo' => 'bar'];

        $mock->get = $array;
        $mock->post = $array;
        $mock->header = $array;
        $mock->server = $array;
        $mock->cookie = $array;
        $mock->files = $array;

        $request = new Request($mock);

        $this->assertEquals($array, $request->get);
        $this->assertEquals($array, $request->post);
        $this->assertEquals($array, $request->header);
        $this->assertEquals($array, $request->server);
        $this->assertEquals($array, $request->cookie);
        $this->assertEquals($array, $request->files);
    }

    public function testMagicGetDefaultValue()
    {
        $mock = m::mock(\Swoole\Http\Request::class);

        $request = new Request($mock);

        $this->assertEquals([], $request->get);
        $this->assertEquals([], $request->post);
        $this->assertEquals([], $request->header);
        $this->assertEquals([], $request->server);
        $this->assertEquals([], $request->cookie);
        $this->assertEquals([], $request->files);

        $this->assertEquals(null, $request->others);
    }

    public function testUri()
    {
        $mock = m::mock(\Swoole\Http\Request::class);

        $mock->header = ['host' => 'foo.bar.com'];
        $mock->server = ['request_uri' => '/foo/bar'];

        $request = new Request($mock);

        $this->assertEquals('http://foo.bar.com/foo/bar', $request->uri());
    }

    public function testUriWithQueryString()
    {
        $mock = m::mock(\Swoole\Http\Request::class);

        $mock->header = ['host' => 'foo.bar.com'];
        $mock->server = ['query_string' => 'foo=bar', 'request_uri' => '/foo/bar'];

        $request = new Request($mock);

        $this->assertEquals('http://foo.bar.com/foo/bar?foo=bar', $request->uri());
    }

    public function testUriWithoutRequestUri()
    {
        $mock = m::mock(\Swoole\Http\Request::class);

        $mock->header = ['host' => 'foo.bar.com'];
        $mock->server = ['request_uri' => '/'];

        $request = new Request($mock);

        $this->assertEquals('http://foo.bar.com', $request->uri());
    }

    public function testHttps()
    {
        $mock = m::mock(\Swoole\Http\Request::class);

        $mock->header = ['host' => 'foo.bar.com'];
        $mock->server = ['request_uri' => '/', 'https' => 'on'];

        $request = new Request($mock);

        $this->assertEquals('https://foo.bar.com', $request->uri());

        $mock->server['https'] = '1';

        $request = new Request($mock);

        $this->assertEquals('https://foo.bar.com', $request->uri());
    }

    public function testMethod()
    {
        $mock = m::mock(\Swoole\Http\Request::class);

        $mock->server = ['request_method' => $method = 'GET'];

        $request = new Request($mock);

        $this->assertEquals($method, $request->method());
    }

    public function testParameters()
    {
        $mock = m::mock(\Swoole\Http\Request::class);

        $mock->get = ['foo' => 'foo'];
        $mock->post = ['bar' => 'bar'];

        $request = new Request($mock);

        $this->assertEquals(['bar' => 'bar', 'foo' => 'foo'], $request->parameters());
    }

    public function testGetCantReplacePost()
    {
        $mock = m::mock(\Swoole\Http\Request::class);

        $mock->get = ['foo' => 'foo'];
        $mock->post = ['foo' => 'bar'];

        $request = new Request($mock);

        $this->assertEquals(['foo' => 'bar'], $request->parameters());
    }

    public function testNoGet()
    {
        $mock = m::mock(\Swoole\Http\Request::class);

        $mock->post = $post = ['foo' => 'bar'];

        $request = new Request($mock);

        $this->assertEquals($post, $request->parameters());
    }

    public function testCookies()
    {
        $mock = m::mock(\Swoole\Http\Request::class);

        $mock->cookie = $cookie = ['foo' => 'bar'];

        $request = new Request($mock);

        $this->assertEquals($cookie, $request->cookies());
    }

    public function testFiles()
    {
        $mock = m::mock(\Swoole\Http\Request::class);

        $mock->files = $files = ['foo' => 'bar'];

        $request = new Request($mock);

        $this->assertEquals($files, $request->files());
    }

    public function testServer()
    {
        $mock = m::mock(\Swoole\Http\Request::class);

        $mock->header = ['foo' => 'foo', 'foo-foo' => 'foo'];
        $mock->server = ['bar' => 'bar'];

        $request = new Request($mock);

        $this->assertEquals([
            'HTTP_FOO' => 'foo',
            'HTTP_FOO_FOO' => 'foo',
            'BAR' => 'bar'
        ], $request->server());
    }

    public function testContent()
    {
        // TODO: not working, why?
        // $mock = m::mock(\Swoole\Http\Request::class);
        //
        // $mock->shouldReceive('rawContent')->with()->once()->andReturn($content = 'foo');

        $mock = $this->createMock(\Swoole\Http\Request::class);

        $mock->expects($this->once())
             ->method('rawContent')
             ->with()
             ->willReturn($content = 'foo');

        $request = new Request($mock);

        $this->assertEquals($content, $request->content());
    }

    public function testCreateFromSwooleRequest()
    {
        $mock = m::mock(\Swoole\Http\Request::class);

        $this->assertEquals(new Request($mock), Request::createFromSwooleRequest($mock));
    }

    public function testToIlluminateRequest()
    {
        $mock = $this->createMock(\Swoole\Http\Request::class);

        $mock->expects($this->once())
             ->method('rawContent')
             ->with()
             ->willReturn('');

        $mock->get = ['foo' => 'foo'];
        $mock->post = ['bar' => 'bar'];
        $mock->header = ['host' => 'foo.bar.com'];
        $mock->server = [
            'query_string' => 'foo=foo',
            'request_method' => 'POST',
            'request_uri' => '/',
        ];
        $mock->cookie = ['cookie' => 'cookie'];
        $mock->files = ['file' => [
            'name' => 'testing',
            'type' => 'text/plain',
            'tmp_name' => __DIR__ . '/fixtures/testing',
            'error' => 0,
            'size' => 8,
        ]];

        $this->assertInstanceOf(
            \Illuminate\Http\Request::class,
            $request = Request::createFromSwooleRequest($mock)->toIlluminateRequest()
        );

        $this->assertEquals('POST', $request->method());
        $this->assertEquals('/', $request->path());
        $this->assertEquals('http://foo.bar.com/?foo=foo', $request->fullUrl());
        $this->assertEquals('foo', $request->input('foo'));
        $this->assertEquals('bar', $request->input('bar'));
        $this->assertArrayHasKey('file', $request->file());
        $this->assertContains('foo.bar.com', $request->headers->get('host'));
        $this->assertContains('cookie', $request->cookies->get('cookie'));
    }
}
