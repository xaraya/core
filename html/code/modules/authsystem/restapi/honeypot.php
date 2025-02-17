<?php

/**
 * @package modules\authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Authsystem\RestApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Authsystem\RestApi;
use sys;

sys::import('xaraya.modules.method');

/**
 * authsystem restapi honeypot function
 * @extends MethodClass<RestApi>
 */
class HoneypotMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Sample REST API call supported by this module (if any)
     * @return string of info
     * @see RestApi::honeypot()
     */
    public function __invoke(array $args = [])
    {
        // @checkme handle POSTed args by passing $args['input'] only in handler?
        //extract($args);
        if (empty($args['username']) || empty($args['password'])) {
            $result = 'Missing username or password.';
        } else {
            $result = 'Wanna have a cookie?';
        }
        return $result;
    }
}
