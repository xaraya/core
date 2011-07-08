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

/* Map a msg structure to a queue
 *
 * This shows the mapping screen for mapping
 * messages onto a queue. The mapping is done
 * based on (simple) rules.
 * @return array data for the template display
*/
function mail_admin_mapping(Array $args=array())
{
     // Security
    if (!xarSecurityCheck('AdminMail')) return;
     
    // Construct the list of queues.
    $queues = xarMod::apiFunc('mail','user','getitemtypes');
    $data = array(); 
    foreach($queues as $id => $props) {
        $data['qlist'][] = array('id' => $id, 'name' => $props['label']);
    }
    return $data;
}
?>
