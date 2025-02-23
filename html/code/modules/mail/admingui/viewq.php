<?php

/**
 * @package modules\mail
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Mail\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Mail\AdminGui;
use xarController;
use xarMod;
use xarModVars;
use xarSec;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * mail admin viewq function
 * @extends MethodClass<AdminGui>
 */
class ViewqMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * View the current mail queue (if any)
     * @author John Cox <niceguyeddie@xaraya.com>
     * @access public
     * @return array|string|void data for the template display
     * @see AdminGui::viewq()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('AdminMail')) {
            return;
        }

        extract($args);
        $this->var()->check('action', $action, 'str', '');

        $data = [];
        if (!empty($action)) {
            // Confirm authorisation code
            if (!$this->sec()->confirmAuthKey()) {
                return $this->ctl()->badRequest('bad_author');
            }

            switch ($action) {
                case 'process':
                    $data['log'] = $this->mod()->apiFunc('mail', 'scheduler', 'sendmail');
                    if (!isset($data['log'])) {
                        return;
                    }
                    break;

                case 'view':
                    $this->var()->find('id', $id, 'str', '');
                    if (!empty($id)) {
                        // retrieve the mail data
                        $maildata = xarModVars::get('mail', $id);
                        if (!empty($maildata)) {
                            $data['id'] = $id;
                            $data['mail'] = unserialize($maildata);
                        }
                    }
                    break;

                case 'delete':
                    $this->var()->find('id', $id, 'str', '');
                    if (!empty($id)) {
                        // get the waiting queue
                        $serialqueue = xarModVars::get('mail', 'queue');
                        if (!empty($serialqueue)) {
                            $queue = unserialize($serialqueue);
                        } else {
                            $queue = [];
                        }
                        // delete the mail data
                        xarModVars::delete('mail', $id);
                        // remove the selected mail from the queue
                        if (isset($queue[$id])) {
                            unset($queue[$id]);
                        }
                        // update the waiting queue
                        $serialqueue = serialize($queue);
                        xarModVars::set('mail', 'queue', $serialqueue);

                        $this->ctl()->redirect($this->ctl()->getModuleURL('mail', 'admin', 'viewq'));
                        return true;
                    }
                    break;

                default:
                    break;
            }
        }

        // TODO: use separate xar_mail_queue table here someday
        // get the waiting queue
        $serialqueue = xarModVars::get('mail', 'queue');
        if (!empty($serialqueue)) {
            $queue = unserialize($serialqueue);
        } else {
            $queue = [];
        }
        // sort mail queue in ascending order of 'no earlier than' delivery
        asort($queue, SORT_NUMERIC);

        $data['items'] = $queue;
        // TODO: add a pager (once it exists in BL)
        $data['pager'] = '';
        $data['authid'] = $this->sec()->genAuthKey();

        // return the template variables defined in this template
        return $data;

    }
}
