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
use DataObjectFactory;
use Queue;
use xarController;
use xarMod;
use xarSec;
use xarSecurity;
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
        if (!xarSecurity::check('AdminMail')) {
            return;
        }

        $data = [];

        // Do we have the master ?
        if (!$qdefInfo = $adminapi->getqdef()) {
            // Redirect to the view page, which offers to create one
            xarController::redirect(xarController::URL('mail', 'admin', 'view'), null, $this->getContext());
            return true;
        }
        // Retrieve the queues
        $queues = $userapi->getqueues();
        $measures = [];
        $data['qtypes'] = $userapi->getqueuetypes();
        foreach ($queues as $index => $qInfo) {
            // Get some info on the Q
            $qName = 'q_' . $qInfo['name'];
            $qStore = DataObjectFactory::getObjectInfo(['name' => $qName]);
            if (!isset($qStore)) {
                // Not there, we know enough
                $queues[$index]['status'] = 'problematic';
                $queues[$index]['count'] = 0;
                $queues[$index]['msg'] = xarML('The storage object of this queue cannot be found ( #(1) )', $qName);
                $measures[$qInfo['name']][] = ['action' => 'createq', 'text' => xarML('Create storage and link to queue')];
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
                    $queues[$index]['msg'] = xarML('Queue is not activated');
                    $measures[$qInfo['name']][] = ['action' => 'activate', 'text' => xarML('Activate the queue')];
                } else {
                    // Queue is active
                    $queues[$index]['status'] = 'active';
                    $measures[$qInfo['name']][] = ['action' => 'deactivate', 'text' => xarML('Deactivate the queue')];

                    $queues[$index]['msg'] = 'No msg yet';
                }

            }
        }
        $data['authid'] = xarSec::genAuthKey();
        $data['queues'] = $queues;
        $data['measures'] = $measures;
        return $data;
    }
}
