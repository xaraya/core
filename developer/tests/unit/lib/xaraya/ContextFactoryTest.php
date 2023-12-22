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

        $context = ContextFactory::fromRequest($request);
        $this->assertEquals($serverVars, $context['server']);
        $this->assertEquals($queryVars, $context['query']);
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
    }

    public function testMakeRequestFromGlobals(): void
    {
        $serverVars = $this->getServerVars();
        $queryVars = $this->getQueryVars();
        $_SERVER = array_replace($_SERVER ?? [], $serverVars);
        $_GET = array_replace($_GET ?? [], $queryVars);

        $request = ContextFactory::makeRequest();
        $headers = ['x-request-id' => ['req_123']];
        $allowed = array_flip(array_keys($serverVars));
        $this->assertEquals($serverVars, array_intersect_key($request->getServerParams(), $allowed));
        $this->assertEquals($queryVars, $request->getQueryParams());
        $this->assertEquals($headers, $request->getHeaders());

        $_SERVER = [];
        $_GET = [];
    }
}
