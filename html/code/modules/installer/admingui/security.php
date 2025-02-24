<?php

/**
 * @package modules\installer
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Installer\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Installer\AdminGui;
use Exception;
use xarTpl;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * installer admin security function
 * @extends MethodClass<AdminGui>
 */
class SecurityMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Installer
     * @package modules\installer\installer
     * @subpackage installer
     * @category Xaraya Web Applications Framework
     * @version 2.4.0
     * @copyright see the html/credits.html file in this release
     * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
     * @link http://xaraya.info/index.php/release/200.html
     * @see AdminGui::security()
     */
    public function __invoke(array $args = [])
    {
        if (!file_exists('install.php')) {
            throw new Exception('Already installed');
        }
        $this->var()->find('install_language', $install_language, 'str::', 'en_US.utf-8');
        xarTpl::setThemeName('installer');
        $data['language']    = $install_language;
        $data['phase'] = 7;
        $data['phase_label'] = $this->ml('Security Considerations');

        return $data;
    }
}
