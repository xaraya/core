<?php
/**
 * @package modules
 * @subpackage mail module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/771.html
 */
/**
 * @param array    $args array of optional parameters<br/>
 */


function mail_adminapi_createq(Array $args=array())
{
    // Security Check
    if (!xarSecurityCheck('AdminMail')) return;

    extract($args);

    // Create a new queue storage object from the xml definition
    $xmlDef = file_get_contents(sys::code() . 'modules/mail/xardata/qdatadef.xml');
    $qdataObjectId = xarMod::apiFunc('dynamicdata','util','import',array('objectname' => 'q_'.$name, 'xml' => $xmlDef));
    if(!isset($qdataObjectId)) return;

    // Get the itemtypes of the mail module
    $itemtypes = xarMod::apiFunc('mail','user','getitemtypes');
    // Get the max value from the keys and add one
    ksort($itemtypes); end($itemtypes);
    $newItemtype = key($itemtypes) +1;
    if($newItemtype==0) $newItemtype++; // prevent the 0 value
    // Create a new itemtype by creating a new object in dd
    $params = array('objectid' => $qdataObjectId, 'itemtype' => $newItemtype);
    $itemid = DataObjectMaster::updateObject($params);
    
    return true;
}
?>