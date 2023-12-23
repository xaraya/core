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

use xarServer;

/**
 * Request handler based on $_SERVER etc.
 */
class RequestHandler implements RequestInterface
{
    /** @var array<string, mixed> */
    private array $args = [];

    /**
     * Constructor for the request handler
     * @param array<string, mixed> $args not by reference anymore
     * @uses xarServer::setInstance()
     * @return void
     **/
    public function __construct($args)
    {
        $this->args = $args;
        xarServer::setInstance($this);
    }

    /**
     * Initialize the request after setup
     * @return bool
     */
    public function initialize()
    {
        return true;
    }

    /**
     * Gets a server variable
     *
     * Returns the value of $name server variable.
     * Accepted values for $name are exactly the ones described by the
     * {@link http://www.php.net/manual/en/reserved.variables.server.php PHP manual}.
     * If the server variable doesn't exist null is returned.
     *
     *
     * @param string $name the name of the variable
     * @return mixed value of the variable
     */
    public function getServerVar($name)
    {
        assert(version_compare("7.2", phpversion()) <= 0);
        if (isset($_SERVER[$name])) {
            return $_SERVER[$name];
        }
        if($name == 'PATH_INFO') {
            return null;
        }
        if (isset($_ENV[$name])) {
            return $_ENV[$name];
        }
        if ($val = getenv($name)) {
            return $val;
        }
        return null; // we found nothing here
    }

    /**
     * Allow setting server variable if needed
     * @param string $name the name of the variable
     * @param mixed $value value of the variable
     * @return void
     */
    public function setServerVar($name, $value)
    {
        $_SERVER[$name] = $value;
    }

    /**
     * Gets a query variable
     * @param string $name the name of the variable
     * @return mixed value of the variable
     */
    public function getQueryVar($name)
    {
        return $_GET[$name] ?? null;
    }

    /**
     * Gets a body variable
     * @param string $name the name of the variable
     * @return mixed value of the variable
     */
    public function getBodyVar($name)
    {
        return $_POST[$name] ?? null;
    }

    /**
     * Gets a cookie variable
     * @param string $name the name of the variable
     * @return mixed value of the variable
     */
    public function getCookieVar($name)
    {
        return $_COOKIE[$name] ?? null;
    }

    /**
     * Gets all server variables
     * @return array<string, mixed>
     */
    public function getServerParams()
    {
        return $_SERVER;
    }

    /**
     * Gets all query variables
     * @return array<string, mixed>
     */
    public function getQueryParams()
    {
        return $_GET;
    }

    /**
     * Add all the params we have to the GET array in case they needed to be called in a standard way. e.g. xarVar::fetch
     * @param array<string, mixed> $args
     * @return void
     */
    public function withQueryParams($args)
    {
        $_GET = $_GET + $args;
    }

    /**
     * Gets all body variables
     * @return array<string, mixed>
     */
    public function getParsedBody()
    {
        return $_POST;
    }

    /**
     * @return mixed
     */
    public function getContext()
    {
        // not used in default request handler
        return null;
    }

    /**
     * @param mixed $context
     * @return void
     */
    public function setContext($context)
    {
        // not used in default request handler
    }
}
