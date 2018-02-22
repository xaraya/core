<?php
/**
 * @package modules\mail
 * @subpackage mail
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/771.html
 */

/**
 * @param array    $args array of optional parameters<br/>
 */
function mail_userapi_getqueues(Array $args=array())
{
    // Queues are different from the itemtypes here, in the sense
    // that we want the registered queues, which may or may not be an
    // itemtypes of mail yet. In short, the items of the qDef object

    // Do we have the master ?
    if(!$qdefInfo = xarMod::apiFunc('mail','admin','getqdef')) {
        // Redirect to the view page, which offers to create one
        xarController::redirect(xarModUrl('mail','admin','view'));
        return true;
    }
    $params = array('modid' => $qdefInfo['moduleid'],'itemtype' => $qdefInfo['itemtype']);
    $queues = xarMod::apiFunc('dynamicdata','user','getitems',$params);

    return $queues;
}
?>