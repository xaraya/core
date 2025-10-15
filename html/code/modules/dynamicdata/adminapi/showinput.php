<?php

/**
 * @package modules\dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\DynamicData\AdminApi;

use Xaraya\Modules\DynamicData\MethodClass;
use Xaraya\Modules\DynamicData\AdminApi;
use sys;

sys::import('modules.dynamicdata.method');


/**
 * dynamicdata adminapi showinput function
 * @extends MethodClass<AdminApi>
 */
class ShowinputMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * show some predefined form input field in a template
     * @param array<string,mixed> $args array of optional parameters<br/>
     * @param mixed $args array containing the definition of the field (type, name, value, ...)
     * @return string containing the HTML (or other) text to output in the BL template
     * @see AdminApi::showinput()
     */
    public function __invoke(array $args = [])
    {
        $property = $this->prop()->getProperty($args);

        if (!empty($args['preset']) && empty($args['value'])) {
            return $property->_showPreset($args);

        } elseif (!empty($args['hidden'])) {
            return $property->showHidden($args);

        } else {
            return $property->showInput($args);
        }
        // TODO: input for some common hook/utility modules
    }
}
