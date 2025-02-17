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
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * installer admin phase2 function
 * @extends MethodClass<AdminGui>
 */
class Phase2Method extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Phase 2: Accept License Page
     * @access private
     * @return array data for the template display
     * @see AdminGui::phase2()
     */
    public function __invoke(array $args = [])
    {
        $data = [];
        if (!file_exists('install.php')) {
            throw new Exception('Already installed');
        }
        xarVar::fetch('install_language', 'str::', $install_language, 'en_US.utf-8', xarVar::NOT_REQUIRED);
        xarVar::fetch('retry', 'int:1', $data['retry'], null, xarVar::NOT_REQUIRED);

        $data['language'] = $install_language;
        $data['phase'] = 2;
        $data['phase_label'] = xarML('Step Two');

        return $data;
    }
}
