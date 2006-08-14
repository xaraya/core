<?php
/**
 * Display a single block in the module space
 *
 * @package modules
 * @subpackage blocks
 * @return array 
 * @author Marcel van der Boom <mrb@hsdev.com>
 * @param  string $name name of the block to render
 */
function blocks_user_display($args)
{
    extract($args);
    
    // Get all the available blocks
    $benum = 'enum';  $data = array();
    foreach(xarModAPIfunc('blocks', 'user', 'getall') as $bid => $binfo) 
    {
        $benum .= ':'.$binfo['name'];
    }
    if(!xarVarFetch('name',$benum,$name)) return;

    // Template issues a wrapped xar:block tag.
    $data['name'] = $name;
    return $data;
}
?>
