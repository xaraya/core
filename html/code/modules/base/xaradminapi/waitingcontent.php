<?php
/**
 * Call the waiting content hook
 *
 * @package modules
 * @subpackage base module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * call the waiting content hook
 *
 * @author  John Cox <admin@dinerminor.com>
 * @access  public
 * @return  boolean true on success, false on failure
*/
function base_adminapi_waitingcontent()
{

    // Hooks (we specify that we want the ones for adminpanels here)
    $output = array();
    $output = xarModCallHooks('item', 'waitingcontent', '', array('module' => 'base'));

    if (empty($output)) {
        $message = xarML('Waiting Content has not been configured');
    }

    if (empty($message)) {
        $message = '';
    } 

    return array('output'   => $output,
                 'message'  => $message);
}

?>