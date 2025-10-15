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
use Xaraya\Modules\Mail\AdminApi;
use Xaraya\Modules\Mail\UserApi;
use Queue;
use sys;

sys::import('xaraya.modules.method');

/**
 * mail admin qstatus function
 * @extends MethodClass<AdminGui>
 */
class QstatusMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @package modules\mail
     * @subpackage mail
     * @category Xaraya Web Applications Framework
     * @version 2.4.0
     * @copyright see the html/credits.html file in this release
     * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
     * @link http://xaraya.info/index.php/release/771.html
     * @see AdminGui::qstatus()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Security
        if (!$this->sec()->checkAccess('AdminMail')) {
            return;
        }

        $data = [];

        // Do we have the master ?
        if (!$qdefInfo = $adminapi->getqdef()) {
            // Redirect to the view page, which offers to create one
            $this->ctl()->redirect($this->ctl()->getModuleURL('mail', 'admin', 'view'));
            return true;
        }
        // Retrieve the queues
        $queues = $userapi->getqueues();
        $measures = [];
        $data['qtypes'] = $userapi->getqueuetypes();
        foreach ($queues as $index => $qInfo) {
            // Get some info on the Q
            $qName = 'q_' . $qInfo['name'];
            $qStore = $this->data()->getObjectInfo(['name' => $qName]);
            if (!isset($qStore)) {
                // Not there, we know enough
                $queues[$index]['status'] = 'problematic';
                $queues[$index]['count'] = 0;
                $queues[$index]['msg'] = $this->ml('The storage object of this queue cannot be found ( #(1) )', $qName);
                $measures[$qInfo['name']][] = ['action' => 'createq', 'text' => $this->ml('Create storage and link to queue')];
            } else {
                // We have some qInfo, retrieve details
                // We have an object, so we can count the items in it.
                sys::import('xaraya.structures.sequences.queue');
                $q = new Queue('dd', ['name' => $qName]);
                $queues[$index]['count'] = $q->size;
                // Determine status
                if (!$userapi->qisactive($qInfo)) {
                    // Queue is inactive
                    $queues[$index]['status'] = 'inactive';
                    $queues[$index]['msg'] = $this->ml('Queue is not activated');
                    $measures[$qInfo['name']][] = ['action' => 'activate', 'text' => $this->ml('Activate the queue')];
                } else {
                    // Queue is active
                    $queues[$index]['status'] = 'active';
                    $measures[$qInfo['name']][] = ['action' => 'deactivate', 'text' => $this->ml('Deactivate the queue')];

                    $queues[$index]['msg'] = 'No msg yet';
                }

            }
        }
        $data['authid'] = $this->sec()->genAuthKey();
        $data['queues'] = $queues;
        $data['measures'] = $measures;
        return $data;
    }
}
