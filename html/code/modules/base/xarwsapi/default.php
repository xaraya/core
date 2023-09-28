<?php
/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 */

/**
 * Default web suervices call
 * 
 * @param array<string, mixed> $args Array of optional parameters<br/>
 * @return string Default message
 */
function base_wsapi_default(Array $args=array())
{
    $result = xarML('This is a default return to a web service call.  ');
    if (!empty($args)) {
        $result .= xarML('The following parameters were sent: ');
        foreach ($args as $k => $v) $result .= '[' . $k . '] => "' . $v . '";';
    }
    return $result;
}
