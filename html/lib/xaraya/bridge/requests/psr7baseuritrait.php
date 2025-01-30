<?php
/**
 * @package core\bridge
 * @subpackage requests
 * @category Xaraya Web Applications Framework
 * @version 2.6.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

namespace Xaraya\Bridge\Requests;

/**
 * For documentation purposes only - available via Psr7BaseUriTrait
 */
interface Psr7BaseUriInterface
{
}

/**
 * Handle base uri for PSR-7 compatible server requests via middleware controllers, routing bridges or others
 */
trait Psr7BaseUriTrait
{
    public static string $baseUri = '';

    /**
     * Strip the base uri for the calling script from the request path and set 'baseUri' request attribute
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public function stripBaseUri(mixed $request): mixed
    {
        if (!is_object($request) || !method_exists($request, 'getAttribute')) {
            return $request;
        }
        // for PSR-7 compatible requests
        /** @var \Psr\Http\Message\ServerRequestInterface $request */
        // did we already filter out the base uri in router middleware?
        if ($request->getAttribute('baseUri') !== null) {
            return $request;
        }
        $baseUri = $this->getBaseUri($request);
        if (!empty($baseUri)) {
            $path = $this->getPathInfo($request);
            $uri = $request->getUri()->withPath($path);
            $request = $request->withUri($uri)->withAttribute('baseUri', $baseUri);
        } else {
            $request = $request->withAttribute('baseUri', $baseUri);
        }
        static::$baseUri = $baseUri;
        return $request;
    }

    /**
     * Set the base uri for the calling script
     */
    public function setBaseUri(string|object $request): void
    {
        // for PSR-7 compatible requests
        if (is_object($request) && method_exists($request, 'getAttribute')) {
            // did we already filter out the base uri in router middleware?
            if ($request->getAttribute('baseUri') !== null) {
                static::$baseUri = $request->getAttribute('baseUri');
            } else {
                // @checkme we don't actually update the request path of the on-going request here
                $this->stripBaseUri($request);
            }
        } else {
            static::$baseUri = $request;
        }
    }
}
