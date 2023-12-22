<?php
/**
 * @package core\context
 * @subpackage context
 * @category Xaraya Web Applications Framework
 * @version 2.4.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Context;

class ContextFactory
{
    public const REQUESTID_PREFIX = 'req_';

    /** @var array<string, string> */
    public static array $mapping = [
        'requestId' => 'requestId',
        //'sessionId' => 'sessionId',
        'session' => 'session',
        'userId' => 'userId',
    ];
    /** @var ?\Nyholm\Psr7\Factory\Psr17Factory */
    protected static mixed $psr17Factory = null;
    /** @var ?\Nyholm\Psr7Server\ServerRequestCreator */
    protected static mixed $requestCreator = null;

    /**
     * Create new context from request
     * @param ?\Psr\Http\Message\ServerRequestInterface $request PSR-7 server request if available
     * @param ?string $source source where the context is created
     * @return Context<string, mixed>
     */
    public static function fromRequest(&$request = null, $source = null)
    {
        if (!isset($request)) {
            return static::fromGlobals($source);
        }
        // @todo use static::$mapping of context key to request attribute
        // set context from request attributes
        $context = new Context((array) $request->getAttributes());
        // @todo don't save request in the context for now, unless we really need it later...
        //$context['request'] = &$request;
        $context['requestId'] = static::makeRequestId($request);
        // @todo see rest handler and graphql for getUserId()
        $context['server'] = $request->getServerParams();
        $context['cookie'] = $request->getCookieParams();
        // @todo see RequestContext
        $context['query'] = $request->getQueryParams();
        $context['body'] = $request->getParsedBody();
        return $context;
    }

    /**
     * Create new context from globals
     * @param ?string $source source where the context is created
     * @return Context<string, mixed>
     */
    public static function fromGlobals($source = null)
    {
        $context = new Context();
        $context['requestId'] = static::makeRequestId();
        // @todo see rest handler and graphql for getUserId()
        $context['server'] = $_SERVER;
        $context['cookie'] = $_COOKIE;
        // @todo see RequestContext
        $context['query'] = $_GET;
        $context['body'] = $_POST;
        if (!empty($source)) {
            $context['source'] = $source;
        }
        return $context;
    }

    /**
     * Make (or get) requestId from request
     * @param ?\Psr\Http\Message\ServerRequestInterface $request PSR-7 server request
     * @return string
     */
    public static function makeRequestId(&$request = null)
    {
        if (!isset($request)) {
            $requestId = static::REQUESTID_PREFIX . bin2hex(random_bytes(16));
            return $requestId;
        }
        // @todo use static::$mapping of context key to request attribute
        $requestId = (string) $request->getAttribute('requestId', '');
        if (empty($requestId)) {
            $requestId = static::REQUESTID_PREFIX . (string) spl_object_id($request) . '-' . (string) microtime(true);
            $request = $request->withAttribute('requestId', $requestId);
        }
        return $requestId;
    }

    /**
     * Create PSR-7 server request from context or globals - uses nyholm/psr7
     * @param ?Context<string, mixed> $context
     * @return \Psr\Http\Message\ServerRequestInterface $request PSR-7 server request
     */
    public static function makeRequest($context = null)
    {
        if (empty(static::$requestCreator)) {
            $psr17Factory = new \Nyholm\Psr7\Factory\Psr17Factory();
            $requestCreator = new \Nyholm\Psr7Server\ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
            static::$psr17Factory = $psr17Factory;
            static::$requestCreator = $requestCreator;
        }
        if (!isset($context)) {
            $request = static::$requestCreator->fromGlobals();
            return $request;
        }
        $headers = static::$requestCreator::getHeadersFromServer($context['server'] ?? []);
        $request = static::$requestCreator->fromArrays($context['server'] ?? [], $headers, $context['cookie'] ?? [], $context['query'] ?? [], $context['body'] ?? []);
        return $request;
    }
}
