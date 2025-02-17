<?php

/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Base\WsApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Base\WsApi;
use sys;

sys::import('xaraya.modules.method');

/**
 * base wsapi default function
 * @extends MethodClass<WsApi>
 */
class DefaultMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Default web suervices call
     * @param array<string,mixed> $args Array of optional parameters<br/>
     * @return string Default message
     * @see WsApi::default()
     */
    public function __invoke(array $args = [])
    {
        $result = xarML('This is a default return to a web service call.  ');
        if (!empty($args)) {
            $result .= xarML('The following parameters were sent: ');
            foreach ($args as $k => $v) {
                $result .= '[' . $k . '] => "' . $v . '";';
            }
        }
        return $result;
    }
}
