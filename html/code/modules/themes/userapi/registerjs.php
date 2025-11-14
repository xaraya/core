<?php

/**
 * @package modules\themes
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Themes\UserApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Themes\UserApi;
use xarJS;

/**
 * themes userapi registerjs function
 * @extends MethodClass<UserApi>
 */
class RegisterjsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Registerjs function
     * Register javascript in the queue for later rendering
     * @author Chris Powis <crisp@xaraya.com>
     * @access public
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string  $args[type] type of js to include, either src or code, optional, default src<br/>
     * string  $args[code] code to include if $type is code<br/>
     * mixed   $args[filename] array containing filename(s) or string comma delimited list
     *         name of file(s) to include, required if $type is src, or<br/>
     *         file(s) to get contents from if $type is code and $code isn't supplied<br/>
     * string  $args[module] name of module to look for file(s) in, optional, default current module<br/>
     * string  $args[position] position to render the js, eg head or body, optional, default head<br/>
     * string  $args[index] optional index in queue relative to other scripts<br/>
     * @return bool|void true on success
     * @see UserApi::registerjs()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (empty($lib) && empty($plugin) && empty($code) && empty($filename) && empty($src)) {
            return;
        }
        if (empty($position)) {
            $args['position'] = 'head';
        }
        if (empty($index)) {
            $args['index'] = null;
        }

        $javascript = xarJS::getInstance($this->getParent());
        return $javascript->register($args);
    }
}
