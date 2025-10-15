<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Modules\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Modules\AdminGui;
use Xaraya\Modules\Modules\AdminApi;
use Exception;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules admin updatehooks function
 * @extends MethodClass<AdminGui>
 */
class UpdatehooksMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Update hooks by hook module
     * @author Xaraya Development Team
     * @see AdminGui::updatehooks()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!$this->sec()->checkAccess('ManageModules')) {
            return;
        }

        if (!$this->sec()->confirmAuthKey()) {
            //return $this->ctl()->badRequest('bad_author');
        }
        // Curhook contains module name
        $this->var()->check('curhook', $curhook, 'str:1:');

        $regId = $this->mod()->getRegID($curhook);
        if (!isset($curhook) || !isset($regId)) {
            $msg = $this->ml('Invalid hook');
            throw new Exception($msg);
        }

        $this->var()->find('subjects', $subjects, 'array', null);



        $data = [];
        // Only update if the module is active.
        $modinfo = $this->mod()->getInfo($regId);
        if (!empty($modinfo) && $this->mod()->isAvailable($modinfo['name'])) {
            $data['regid'] = $regId;
            if (!empty($subjects)) {
                $data['subjects'] = $subjects;
            }
            if (!$adminapi->updatehooks($data)) {
                return;
            }
        }

        $this->var()->find('return_url', $return_url, 'isset', '');
        if (!empty($return_url)) {
            $this->ctl()->redirect($return_url);
        } else {
            $this->ctl()->redirect($this->ctl()->getModuleURL(
                'modules',
                'admin',
                'hooks',
                ['hook' => $curhook]
            ));
        }
        return true;
    }
}
