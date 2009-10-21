<?php
/**
 * Import a property type
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
function dynamicdata_admin_importpropertytypes ($args)
{
    
    $args['flush'] = 'false';
    $success = xarMod::apiFunc('dynamicdata','admin','importpropertytypes', $args);
    
    return array();
}
?>
