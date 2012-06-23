<?php
/**
 * Display a single block in the module space
 *
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.3.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 *
 */
/**
 * @return array
 * @author Marcel van der Boom <mrb@hsdev.com>
 * @param  string $name name of the block to render
 * @return array data for the template display
 */
function blocks_user_display(Array $args=array())
{
    extract($args);

    // Get all the available blocks
    $benum = 'enum';  $data = array();
    foreach(xarMod::apiFunc('blocks', 'user', 'getall') as $bid => $binfo)
    {
        $benum .= ':'.$binfo['name'];
    }
    if(!xarVarFetch('name',$benum,$name)) return;

    // Template issues a wrapped xar:block tag.
    $data['name'] = $name;
    return $data;
}
?>
