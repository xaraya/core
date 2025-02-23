<?php

/**
 * @package modules\privileges
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Privileges\UserGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Privileges\UserGui;
use xarController;
use xarUser;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * privileges user errors function
 * @extends MethodClass<UserGui>
 */
class ErrorsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @return array|bool|void data for the template display
     * @see UserGui::errors()
     */
    public function __invoke(array $args = [])
    {
        $data = [];
        xarVar::fetch('layout', 'isset', $data['layout'], 'default', xarVar::DONT_SET);
        xarVar::fetch('redirecturl', 'isset', $data['redirecturl'], 'local_halt', xarVar::DONT_SET);
        if (!xarUser::isLoggedIn()) {
            return $data;
        } else {
            if ($data['redirecturl'] == 'local_halt') {
                return $data;
            } else {
                xarController::redirect($data['redirecturl'], null, $this->getContext());
                return true;
            }
        }
    }
}
