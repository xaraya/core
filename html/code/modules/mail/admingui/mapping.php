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
use Xaraya\Modules\Mail\UserApi;
use xarMod;
use xarSecurity;
use sys;

sys::import('xaraya.modules.method');

/**
 * mail admin mapping function
 * @extends MethodClass<AdminGui>
 */
class MappingMethod extends MethodClass
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
     * @see AdminGui::mapping()
     */
    public function __invoke(array $args = [])
    {
        /** @var UserApi $userapi */
        $userapi = $this->userapi();
        // Security
        if (!$this->sec()->checkAccess('AdminMail')) {
            return;
        }

        // Construct the list of queues.
        $queues = $userapi->getitemtypes();
        $data = [];
        foreach ($queues as $id => $props) {
            $data['qlist'][] = ['id' => $id, 'name' => $props['label']];
        }
        return $data;
    }
}
