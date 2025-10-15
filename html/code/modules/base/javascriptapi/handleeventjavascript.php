<?php

/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Base\JavascriptApi;

use Xaraya\Modules\Base\MethodClass;
use Xaraya\Modules\Base\JavascriptApi;
use sys;

sys::import('modules.base.method');

/**
 * base javascriptapi handleeventjavascript function
 * @extends MethodClass<JavascriptApi>
 */
class HandleeventjavascriptMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Handle render javascript form field tags
     * Handle <xar:base-trigger-javascript ...> form field tags
     * Format : <xar:place-javascript definition="$definition"/> with $definition an array
     *       or <xar:place-javascript position="head|body|whatever|" type="code|src|whatever|"/>
     * Default position is ''; default type is ''.
     * Typical use in the head section is: <xar:place-javascript position="head"/>
     * @author Jason Judge
     * @param mixed $args array Containing the form field definition or the type, position, ...
     * @return string an empty string
     * @see JavascriptApi::handleeventjavascript()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        // The whole lot can be passed in as an array.
        if (isset($definition) && is_array($definition)) {
            extract($definition);
        }

        // Position and type are mandatory.
        // 'position' is the name or ID of the tag ('body', 'mytag', etc.).
        // 'type' is the type of event ('onload', 'onmouseup', etc.)
        if (empty($position)) {
            $position = '';
        } else {
            $position = addslashes($position);
        }
        if (empty($type)) {
            $type = '';
        } else {
            $type = addslashes($type);
        }

        // Concatenate the JavaScript trigger code fragments.
        // Only pick up the event type JavaScript.

        return "
            echo htmlspecialchars(xarMod" . "::apiFunc('base', 'javascript', 'geteventjs', array('position'=>'$position', 'type'=>'$type')));
        ";
    }
}
