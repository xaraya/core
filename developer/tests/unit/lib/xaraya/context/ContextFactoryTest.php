<?php

use PHPUnit\Framework\TestCase;
use Xaraya\Context\ContextFactory;
use Xaraya\Context\Context;

final class ContextFactoryTest extends TestCase
{
    protected function getServerVars()
    {
        return [
            'REQUEST_METHOD' => 'DELETE',
            'REQUEST_URI' => '/xaraya/index.php/site?all=yes',
            'SCRIPT_NAME' => '/xaraya/index.php',
            'PATH_INFO' => '/site',
            'QUERY_STRING' => 'all=yes',
            'HTTP_X_REQUEST_ID' => 'req_123',
        ];
    }

    protected function getQueryVars()
    {
        return [
            'all' => 'yes',
        ];
    }

    public function testFromRequest(): void
    {
        $serverVars = $this->getServerVars();
        $queryVars = $this->getQueryVars();
        $context = new Context([
            'server' => $serverVars,
            'query' => $queryVars,
        ]);
        $request = ContextFactory::makeRequest($context);
        // reset context as request attribute first to create a fresh context
        $request = $request->withAttribute(ContextFactory::REQUEST_ATTRIBUTE, null);

        $context = ContextFactory::fromRequest($request);
        $this->assertEquals($serverVars, $context['server']);
        $this->assertEquals($queryVars, $context['query']);

        $context['updated'] = 'from_attribute';
        // context is set as request attribute
        $expected = $request->getAttribute(ContextFactory::REQUEST_ATTRIBUTE, null);
        $this->assertEquals($expected, $context);

        $context['updated'] = 'from_request';
        // check if we already have a context as request attribute
        $expected = ContextFactory::fromRequest($request);
        $this->assertEquals($expected, $context);
    }

    public function testFromGlobals(): void
    {
        $serverVars = $this->getServerVars();
        $queryVars = $this->getQueryVars();
        $_SERVER = array_replace($_SERVER ?? [], $serverVars);
        $_GET = array_replace($_GET ?? [], $queryVars);

        $context = ContextFactory::fromRequest();
        $allowed = array_flip(array_keys($serverVars));
        $this->assertEquals($serverVars, array_intersect_key($context['server'], $allowed));
        $this->assertEquals($queryVars, $context['query']);

        $_SERVER = [];
        $_GET = [];
    }

    public function testSerialize(): void
    {
        $context = new Context([
            'hello' => 'world',
            'source' => __METHOD__,
            // Exception: Serialization of 'ReflectionMethod' is not allowed
            'twig' => new ReflectionMethod(Context::class, 'getSession'),
        ]);

        // check that serializing dropped most of the context
        $serialized = serialize($context);
        $expected = 'O:22:"Xaraya\Context\Context":1:{s:6:"source";s:33:"ContextFactoryTest::testSerialize";}';
        $this->assertEquals($expected, $serialized);

        // check that unserializing doesn't return the same context except source
        $unserialized = unserialize($serialized);
        $this->assertNotEquals($context, $unserialized);
        $expected = 'ContextFactoryTest::testSerialize';
        $this->assertEquals($expected, $unserialized['source']);
    }

    public function testMakeRequest(): void
    {
        $serverVars = $this->getServerVars();
        $queryVars = $this->getQueryVars();
        $context = new Context([
            'server' => $serverVars,
            'query' => $queryVars,
        ]);

        $request = ContextFactory::makeRequest($context);
        $headers = ['x-request-id' => ['req_123']];
        $this->assertEquals($serverVars, $request->getServerParams());
        $this->assertEquals($queryVars, $request->getQueryParams());
        $this->assertEquals($headers, $request->getHeaders());

        $context['updated'] = 'from_attribute';
        // context is set as request attribute
        $expected = $request->getAttribute(ContextFactory::REQUEST_ATTRIBUTE, null);
        $this->assertEquals($expected, $context);
    }

    public function testMakeRequestFromGlobals(): void
    {
        $serverVars = $this->getServerVars();
        $queryVars = $this->getQueryVars();
        $_SERVER = array_replace($_SERVER ?? [], $serverVars);
        $_GET = array_replace($_GET ?? [], $queryVars);

        $request = ContextFactory::makeRequest();
        $headers = ['X-Request-Id' => ['req_123']];
        $allowed = array_flip(array_keys($serverVars));
        $this->assertEquals($serverVars, array_intersect_key($request->getServerParams(), $allowed));
        $this->assertEquals($queryVars, $request->getQueryParams());
        $this->assertEquals($headers, $request->getHeaders());

        $_SERVER = [];
        $_GET = [];
    }
}
