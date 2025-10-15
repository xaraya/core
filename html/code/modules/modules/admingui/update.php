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
use sys;

sys::import('xaraya.modules.method');

/**
 * modules admin update function
 * @extends MethodClass<AdminGui>
 */
class UpdateMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Update a module
     * @author Xaraya Development Team
     * @param array<mixed> $args
     * @var int id the module's registered id
     * @var string newdisplayname the new display name
     * @var string newdescription the new description
     * @return mixed true on success, error message on failure
     * @see AdminGui::update()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Security
        if (!$this->sec()->checkAccess('EditModules')) {
            return;
        }

        if (!$this->sec()->confirmAuthKey()) {
            return $this->ctl()->badRequest('bad_author');
        }

        // Get parameters
        $this->var()->check('id', $regId, 'id');
        // CHECKME: what's this?
        $this->var()->find('newdisplayname', $newDisplayName, 'str::');

        // update hooks...
        $this->var()->find('observers', $observers, 'array', []);

        if (!$adminapi->update([
            'regid' => $regId,
            'displayname' => $newDisplayName,
            'observers' => $observers,
        ])) {
            return;
        }

        $this->var()->check('return_url', $return_url);
        if (!empty($return_url)) {
            $this->ctl()->redirect($return_url);
        } else {
            $this->ctl()->redirect($this->ctl()->getModuleURL('modules', 'admin', 'modify', ['id' => $regId]));
        }

        return true;
    }
}
