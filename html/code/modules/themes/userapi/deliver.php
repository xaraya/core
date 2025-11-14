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
 * themes userapi deliver function
 * @extends MethodClass<UserApi>
 */
class DeliverMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Handle place-css tag
     * @author andyv <andyv@xaraya.com>
     * @author Chris Powis <crisp@xaraya.com>
     * @access public
     * @param array<string,mixed> $args array of optional paramaters<br/>
     * boolean $args[comments] show comments, optional, default false
     * @todo option to turn on/off style comments in UI, cfr template comments
     * @return string templated output of css to render
     * @see UserApi::deliver()
     */
    public function __invoke(array $args = [])
    {
        $css = xarCSS::getInstance($this->getParent());
        return $css->render($args);
    }
}
