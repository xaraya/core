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
 * themes userapi renderjs function
 * @extends MethodClass<UserApi>
 */
class RenderjsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Renderjs function
     * Render queued javascript
     * @author Chris Powis <crisp@xaraya.com>
     * @access public
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string  $args[position] position to render, optional<br/>
     * string  $args[index] index to render, optional<br/>
     * string  $args[type] type to render, optional
     * @return string templated output of js to render
     * @see UserApi::renderjs()
     */
    public function __invoke(array $args = [])
    {
        $javascript = xarJS::getInstance($this->getParent());
        return $javascript->render($args);
    }
}
