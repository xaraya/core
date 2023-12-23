<?php
/**
 * @package core\requests
 * @category Xaraya Web Applications Framework
 * @version 2.4.2
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
**/

namespace Xaraya\Requests;

/**
 * Interface between xarServer (static) and RequestHandler (instance)
 * Note: if you want to replace RequestHandler with a custom class, use
 * xarServer::setRequestClass(RequestContext::class);
 */
interface RequestInterface
{
    /**
     * Constructor for the request handler
     * @param array<string, mixed> $args not by reference anymore
     * @uses xarServer::setInstance()
     * @return void
     **/
    public function __construct($args);

    /**
     * Initialize the request after setup
     * @return bool
     */
    public function initialize();

    /**
     * Gets a server variable
     * @param string $name the name of the variable
     * @return mixed value of the variable
     */
    public function getServerVar($name);

    /**
     * Allow setting server variable if needed
     * @param string $name the name of the variable
     * @param mixed $value value of the variable
     * @return void
     */
    public function setServerVar($name, $value);

    /**
     * Gets a query variable
     * @param string $name the name of the variable
     * @return mixed value of the variable
     */
    public function getQueryVar($name);

    /**
     * Gets a body variable
     * @param string $name the name of the variable
     * @return mixed value of the variable
     */
    public function getBodyVar($name);

    /**
     * Gets a cookie variable
     * @param string $name the name of the variable
     * @return mixed value of the variable
     */
    public function getCookieVar($name);

    /**
     * Gets all server variables
     * @return array<string, mixed>
     */
    public function getServerParams();

    /**
     * Gets all query variables
     * @return array<string, mixed>
     */
    public function getQueryParams();

    /**
     * Add all the params we have to the GET array in case they needed to be called in a standard way. e.g. xarVar::fetch
     * @param array<string, mixed> $args
     * @return void
     */
    public function withQueryParams($args);

    /**
     * Gets all body variables
     * @return array<string, mixed>
     */
    public function getParsedBody();
}
