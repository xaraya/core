<?php
/**
 * Display a single block in the module space
 *
 * @package modules\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/13.html
 *
 */

/**
 * 
 * @author Marcel van der Boom <mrb@hsdev.com>
 * 
 * @param array $args Parameter data array.
 * @return array Display data array 
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
