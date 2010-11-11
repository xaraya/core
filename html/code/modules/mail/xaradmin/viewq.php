<?php
/**
 * View the current mail queue
 *
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
 * View the current mail queue (if any)
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  array data for the template display
*/
function mail_admin_viewq(Array $args=array())
{
    extract($args);
    if (!xarVarFetch('action','str', $action, '')) return;

    if (!xarSecurityCheck('AdminMail')) return;

    $data = array();
    if (!empty($action)) {
        // Confirm authorisation code
        if (!xarSecConfirmAuthKey()) {
            return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
        }        

        switch ($action)
        {
            case 'process':
                $data['log'] = xarMod::apiFunc('mail','scheduler','sendmail');
                if (!isset($data['log'])) return;
                break;

            case 'view':
                if (!xarVarFetch('id','str', $id, '')) return;
                if (!empty($id)) {
                    // retrieve the mail data
                    $maildata = xarModVars::get('mail',$id);
                    if (!empty($maildata)) {
                        $data['id'] = $id;
                        $data['mail'] = unserialize($maildata);
                    }
                }
                break;

            case 'delete':
                if (!xarVarFetch('id','str', $id, '')) return;
                if (!empty($id)) {
                    // get the waiting queue
                    $serialqueue = xarModVars::get('mail','queue');
                    if (!empty($serialqueue)) {
                        $queue = unserialize($serialqueue);
                    } else {
                        $queue = array();
                    }
                    // delete the mail data
                    xarModVars::delete('mail',$id);
                    // remove the selected mail from the queue
                    if (isset($queue[$id])) {
                        unset($queue[$id]);
                    }
                    // update the waiting queue
                    $serialqueue = serialize($queue);
                    xarModVars::set('mail','queue',$serialqueue);

                    xarController::redirect(xarModURL('mail', 'admin', 'viewq'));
                    return true;
                }
                break;

            default:
                break;
        }
    }

// TODO: use separate xar_mail_queue table here someday
    // get the waiting queue
    $serialqueue = xarModVars::get('mail','queue');
    if (!empty($serialqueue)) {
        $queue = unserialize($serialqueue);
    } else {
        $queue = array();
    }
    // sort mail queue in ascending order of 'no earlier than' delivery
    asort($queue, SORT_NUMERIC);

    $data['items'] = $queue;
    // TODO: add a pager (once it exists in BL)
    $data['pager'] = '';
    $data['authid'] = xarSecGenAuthKey();

    // return the template variables defined in this template
    return $data;

}

?>