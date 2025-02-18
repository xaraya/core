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
use xarMod;
use xarSec;
use xarSecurity;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * mail admin view function
 * @extends MethodClass<AdminGui>
 */
class ViewMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Queue management for mail module
     * @package modules\mail
     * @subpackage mail
     * @category Xaraya Web Applications Framework
     * @version 2.4.0
     * @copyright see the html/credits.html file in this release
     * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
     * @link http://xaraya.info/index.php/release/771.html
     * @author Marcel van der Boom <marcel@xaraya.com>
     * @see AdminGui::view()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!xarSecurity::check('AdminMail')) {
            return;
        }

        // Retrieve the object which holds our queue definition
        if (!$qdefInfo = $adminapi->getqdef()) {
            return $this->OfferCreate(null, $this->getContext());
        } else {
            $data['qdef'] = $qdefInfo;
            if (!xarVar::fetch('itemid', 'int:1:', $data['itemid'], 0, xarVar::NOT_REQUIRED)) {
                return;
            }
            return $data;
        }
    }

    /**
     * @package modules\mail
     * @subpackage mail
     * @category Xaraya Web Applications Framework
     * @version 2.4.0
     * @copyright see the html/credits.html file in this release
     * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
     * @link http://xaraya.info/index.php/release/771.html
     *
     * @return array<mixed>|string data for the template display
     *
     * @author Marcel van der Boom <marcel@xaraya.com>
     */
    protected function OfferCreate($qDef = null, $context = null)
    {
        $data = [];
        $data['authid'] = xarSec::genAuthKey();
        $data['qdef_name'] = isset($qDef) ? $qDef : 'mailqueues';
        $data['qdef_method'] = 1;
        $data['qdef_create'] = array(array('id' => 1,'name' => xarML('Create new object with name')));
        $data['qdef_choose'] = array(array('id' => 2,'name' => xarML('Use an existing object')));
        $data['context'] = $context;
        return xarTpl::module('mail','admin','queue-newdef',$data);
    }
}
