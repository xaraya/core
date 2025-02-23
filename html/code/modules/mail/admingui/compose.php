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
use xarModVars;
use xarSec;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * mail admin compose function
 * @extends MethodClass<AdminGui>
 */
class ComposeMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Test the email settings
     * @author John Cox <niceguyeddie@xaraya.com>
     * @access public
     * @return array|void data for the template display
     * @see AdminGui::compose()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('ManageMail')) {
            return;
        }

        // Generate a one-time authorisation code for this operation
        $data['authid']         = $this->sec()->genAuthKey();

        // Get the admin email address
        $data['email']   = $this->mod()->getVar('adminmail');
        $data['name']    = $this->mod()->getVar('adminname');

        $this->var()->find('confirm', $confirm, 'int', 0);

        $data['message'] = '';
        if ($confirm) {
            $data['message'] = $this->ml('Message sent');
        }
        // everything else happens in the template for now
        return $data;
    }
}
