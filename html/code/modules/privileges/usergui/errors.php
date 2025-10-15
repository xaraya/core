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
        $this->var()->check('layout', $data['layout'], 'isset', 'default');
        $this->var()->check('redirecturl', $data['redirecturl'], 'isset', 'local_halt');
        if (!$this->user()->isLoggedIn()) {
            return $data;
        } else {
            if ($data['redirecturl'] == 'local_halt') {
                return $data;
            } else {
                $this->ctl()->redirect($data['redirecturl']);
                return true;
            }
        }
    }
}
