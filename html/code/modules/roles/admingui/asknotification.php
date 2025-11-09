<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Roles\AdminGui;
use Xaraya\Modules\Roles\AdminApi;
use DataObject;
use xarRoles;
use xarTpl;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles admin asknotification function
 * @extends MethodClass<AdminGui>
 */
class AsknotificationMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Update users from roles_admin_showusers
     * @package modules\roles
     * @subpackage roles
     * @category Xaraya Web Applications Framework
     * @version 2.4.0
     * @copyright see the html/credits.html file in this release
     * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
     * @link http://xaraya.info/index.php/release/27.html
     * @see AdminGui::asknotification()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!$this->sec()->checkAccess('EditRoles')) {
            return;
        }

        $data = [];
        // Get parameters
        $this->var()->find('phase', $data['phase'], 'str:0:', 'display');
        $this->var()->find('mailtype', $data['mailtype'], 'str:0:', 'blank');
        $this->var()->find('id', $id);
        //Maybe some kind of return url will make this function available for other modules
        $this->var()->find('state', $data['state'], 'int:0:', xarRoles::ROLES_STATE_CURRENT);
        $this->var()->find('groupid', $data['groupid'], 'int:0:', 0);
        //optional value
        $this->var()->find('pass', $data['pass'], 'str:0:', null);
        $this->var()->find('ip', $data['ip'], 'str:0:', null);
        switch ($data['phase']) {
            case 'display':
                $data['pass'] = $this->session()->getVar('tmppass');
                $this->session()->delVar('tmppass');
                if ($data['mailtype'] == 'blank') {
                    $data['subject'] = '';
                    $data['message'] = '';
                } else {
                    $strings = $adminapi->getmessagestrings(['template' => $data['mailtype']]);
                    if (!isset($strings)) {
                        return;
                    }

                    $data['subject'] = $strings['subject'];
                    $data['message'] = $strings['message'];
                }
                //Display the notification form
                $this->var()->find('subject', $data['subject'], 'str:1:', $data['subject']);
                $this->var()->find('message', $data['message'], 'str:1:', $data['message']);
                $data['authid'] = $this->sec()->genAuthKey();
                $data['id'] = base64_encode(serialize($id));

                // dynamic properties (if any)
                $data['properties'] = null;
                if ($this->mod()->isAvailable('dynamicdata')) {
                    // get the DataObject defined for this module (and itemtype, if relevant)
                    /** @var DataObject $object */
                    $object = $this->data()->getObject(['module' => 'roles']);
                    if (isset($object) && !empty($object->objectid)) {
                        // get the Dynamic Properties of this object
                        $data['properties'] = &$object->getProperties();
                    }
                }
                return $data;

            case 'notify':
                // Confirm authorisation code
                if (!$this->sec()->confirmAuthKey()) {
                    return $this->ctl()->badRequest('bad_author');
                }
                $this->var()->find('subject', $data['subject'], 'str:1:', null);
                $this->var()->find('message', $data['message'], 'str:1:', null);

                // Need to convert %%var%% to #$var# so that we can compile the template
                $data['message'] = preg_replace("/%%(.+)%%/", "#$\\1#", $data['message']);
                $data['subject'] = preg_replace("/%%(.+)%%/", "#$\\1#", $data['subject']);

                // Compile Template before sending it to senduseremail()
                $data['message'] = $this->tpl()->compileString($data['message']);
                $data['subject'] = $this->tpl()->compileString($data['subject']);

                //Send notification
                $id = unserialize(base64_decode($id));
                if (!$adminapi->senduseremail([ 'id' => $id, 'mailtype' => $data['mailtype'], 'subject' => $data['subject'], 'message' => $data['message'], 'pass' => $data['pass'], 'ip' => $data['ip']])) {
                    return $this->tpl()->module('roles', 'user', 'errors', ['layout' => 'mail_failed']);
                }
                $this->ctl()->redirect($this->ctl()->getModuleURL(
                    'roles',
                    'admin',
                    'showusers',
                    ['id' => $data['groupid'], 'state' => $data['state']]
                ));
                return true;
        }
    }
}
