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
    /** @var array<string, string> */
    public static array $mapping = [
        'requestId' => 'requestId',
        //'sessionId' => 'sessionId',
        'session' => 'session',
        'userId' => 'userId',
    ];

    /**
     * Create new context from request
     * @param mixed $request PSR-7 server request if available
     * @param mixed $source source where the context is created
     * @return Context<string, mixed>
     */
    public static function fromRequest(&$request = null, $source = null)
    {
        if (empty($request)) {
            return static::fromGlobals($source);
        }
        // @todo use static::$mapping of context key to request attribute
        // set context from request attributes
        $context = new Context((array) $request->getAttributes());
        // @todo don't save request in the context for now, unless we really need it later...
        //$context['request'] = &$request;
        $context['requestId'] = static::makeRequestId($request);
        // @todo see rest handler and graphql for getUserId()
        //$context['server'] = $request->getServerParams();
        //$context['cookie'] = $request->getCookieParams();
        // @todo see RequestContext
        //$context['query'] = $request->getQueryParams();
        //$context['body'] = $request->getParsedBody();
        return $context;
    }

    /**
     * Create new context from globals
     * @param mixed $source source where the context is created
     * @return Context<string, mixed>
     */
    public static function fromGlobals($source = null)
    {
        $context = new Context();
        $context['requestId'] = null;
        // @todo see rest handler and graphql for getUserId()
        //$context['server'] = $_SERVER;
        //$context['cookie'] = $_COOKIE;
        // @todo see RequestContext
        //$context['query'] = $_GET;
        //$context['body'] = $_POST;
        if (!empty($source)) {
            $context['source'] = $source;
        }
        return $context;
    }

    /**
     * Make (or get) requestId from request
     * @param mixed $request PSR-7 server request
     * @return string
     */
    public static function makeRequestId(&$request)
    {
        // @todo use static::$mapping of context key to request attribute
        $requestId = $request->getAttribute('requestId');
        if (empty($requestId)) {
            $requestId = 'req_' . (string) spl_object_id($request) . '-' . (string) microtime(true);
            $request = $request->withAttribute('requestId', $requestId);
        }
        return $requestId;
    }
}
