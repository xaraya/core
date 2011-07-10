<?php
/**
 * Handle css tag
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * Handle css tag
 *
 * @author andyv <andyv@xaraya.com>
 * @param $args array containing the parameters
 * @return string the PHP code needed to show the css tag in the BL template
 */
/**
 * Handler for the xar:style tag
 *
 * Attributes:
 * file       - CDATA                   - basename of the style file to include
 * scope     -  [module|(theme)|system] - where to look for it
 * type      - (text/css)               - what content is to be expected
 * media     - (all)                    - for which media are we including style info (space separated list)
 * alternate - [yes|(no)]               - this style is an alternative to the main styling?
 * title     - ""                       - what title can we attach to the styling, if any
 * method    - [(import)|link]          - what method do we use to include the style info
 * condition - [IE|(IE5)|(!IE6)|(lt IE7)] - encase in conditional comment (for serving to ie-win of various flavours)
 *
 * <xar:style file="basename" scope="theme" type="text/css" media="all" alternate="no" title="Great style" method="import"/>
 */
/**
 * @param array    $args array of optional parameters<br/>
 */
function themes_userapi_register(Array $args=array())
{
    // keep track of style when we're caching
    xarCache::addStyle($args);

    sys::import('modules.themes.class.xarcss');
    $obj = new xarCSS($args);
    return $obj->run_output();
}

?>
