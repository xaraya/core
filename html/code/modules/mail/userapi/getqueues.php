<?php

/**
 * @package modules\mail
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Mail\UserApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Mail\UserApi;
use Xaraya\Modules\Mail\AdminApi;
use sys;

sys::import('xaraya.modules.method');

/**
 * mail userapi getqueues function
 * @extends MethodClass<UserApi>
 */
class GetqueuesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @param array<string,mixed> $args array of optional parameters<br/>
     * @see UserApi::getqueues()
     */
    public function __invoke(array $args = [])
    {
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Queues are different from the itemtypes here, in the sense
        // that we want the registered queues, which may or may not be an
        // itemtypes of mail yet. In short, the items of the qDef object

        // Do we have the master ?
        if (!$qdefInfo = $adminapi->getqdef()) {
            // Redirect to the view page, which offers to create one
            $this->ctl()->redirect($this->ctl()->getModuleURL('mail', 'admin', 'view'));
            return true;
        }
        $params = ['modid' => $qdefInfo['moduleid'],'itemtype' => $qdefInfo['itemtype']];
        $queues = $this->mod()->apiFunc('dynamicdata', 'user', 'getitems', $params);

        return $queues;
    }
}
