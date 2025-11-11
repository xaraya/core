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
use xarCSS;

/**
 * themes userapi register function
 * @extends MethodClass<UserApi>
 */
class RegisterMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Handle css tag
     * @author andyv <andyv@xaraya.com>
     * @author Chris Powis <crisp@xaraya.com>
     * @access public
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string $args[scope] scope of style, one of common!theme(default)|module|block|property<br/>
     * string $args[method] style method, one of link(default)|import|embed<br/>
     * string $args[alternatedir] alternative base folder to look in, falling back to...<br/>
     * string $args[base] base folder to look in, default depends on scope<br/>
     * string $args[file] name of file required for link or embed methods<br/>
     * string $args[filext] extension to use for file(s), optional, default "css"<br/>
     * string $args[source] source code, required for embed method, default null<br/>
     * string $args[alternate] switch to set rel="alternate stylesheet", optional true|false(default)<br/>
     * string $args[rel] rel attribute, optional, default "stylesheet"<br/>
     * string $args[type] link/style type attribute, optional, default "text/css"<br/>
     * string $args[media] media attribute, optional, default "screen"<br/>
     * string $args[title] title attribute, optional, default ""<br/>
     * string $args[condition] conditionals for ie browser, optional, default null<br/>
     * string $args[module] module for module|block scope, optional, default current module<br/>
     * string $args[property] property required for property scope
     * @return bool true on success
     * @see UserApi::register()
     */
    public function __invoke(array $args = [])
    {
        $css = xarCSS::getInstance();
        return $css->register($args);
    }
}
