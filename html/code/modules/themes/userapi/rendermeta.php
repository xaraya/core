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
use xarMeta;

/**
 * themes userapi rendermeta function
 * @extends MethodClass<UserApi>
 */
class RendermetaMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Render meta function
     * Render queued meta tags
     * @author Chris Powis <crisp@xaraya.com>
     * @access public
     * @param array<string,mixed> $args array of optional parameters (todo)
     * @return string templated output of meta tags to render
     * @see UserApi::rendermeta()
     */
    public function __invoke(array $args = [])
    {
        $meta = xarMeta::getInstance();
        return $meta->render($args);
    }
}
