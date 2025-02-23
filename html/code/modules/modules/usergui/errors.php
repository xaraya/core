<?php

/**
 * @package modules\modules
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Modules\UserGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Modules\UserGui;
use xarSecurity;
use sys;

sys::import('xaraya.modules.method');

/**
 * modules user errors function
 * @extends MethodClass<UserGui>
 */
class ErrorsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Entry point for error messages
     * @author Marc Lutolf <mfl@netspan.ch>
     * @see UserGui::errors()
     */
    public function __invoke(array $args = [])
    {
        if (!$this->sec()->checkAccess('EditModules')) {
            return;
        }
        $data['layout'] = 'general';
        $data['message'] = urldecode($args['message']);
        return $data;
    }
}
