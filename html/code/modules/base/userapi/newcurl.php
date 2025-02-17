<?php

/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Base\UserApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Base\UserApi;
use xarCurl;
use sys;

sys::import('xaraya.modules.method');

/**
 * base userapi newcurl function
 * @extends MethodClass<UserApi>
 */
class NewcurlMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Return a new xarCurl object.
     * @param array<string,mixed> $args Optional set of arguments
     * @return \xarCurl xarCurl Object returned
     * @see UserApi::newcurl()
     */
    public function __invoke(array $args = [])
    {
        sys::import('modules.base.class.xarCurl');
        return new xarCurl($args);
    }
}
