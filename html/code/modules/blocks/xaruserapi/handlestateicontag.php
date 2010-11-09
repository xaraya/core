<?php
/**
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/* Handle the icon tag state
 *
 * @author Jim McDonald
 * @author Paul Rosania
*/

function blocks_userapi_handleStateIconTag($args)
{
    return "echo xarMod::apiFunc('blocks', 'user', 'drawStateIcon', array('bid' => \$bid)); ";
}

?>
