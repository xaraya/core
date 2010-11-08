<?php
/**
 * Import a property type
 *
 * @package modules
 * @subpackage dynamicdata module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 * @return array empty array for the template display
 */
function dynamicdata_admin_importpropertytypes ($args)
{
    
    $args['flush'] = 'false';
    $success = xarMod::apiFunc('dynamicdata','admin','importpropertytypes', $args);
    
    return array();
}
?>
