<?php

/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Base\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Base\AdminGui;
use xarSecurity;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * base admin sysinfo function
 * @extends MethodClass<AdminGui>
 */
class SysinfoMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Display some system information
     * This information can be used for support / debugging
     * @return array|void of info from phpinfo()
     * @see AdminGui::sysinfo()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!xarSecurity::check('AdminBase')) {
            return;
        }

        xarVar::fetch('what', 'int:-1:127', $what, INFO_GENERAL, xarVar::NOT_REQUIRED);
        $data['what'] = $what;
        ob_start();
        phpinfo($what);
        $val_phpinfo = ob_get_contents();
        ob_end_clean();
        // get a substring of the php info to get rid of the html, head, title, etc.
        // Credit to Jason Judge.
        // Remove the header and footer.
        $val_phpinfo = preg_replace(
            ['/^.*<body[^>]*>/is', '/<\/body[^>]*>.*$/is'],
            '',
            $val_phpinfo,
            1
        );
        // Remove pixel table widths.
        $val_phpinfo = preg_replace(
            '/width="[0-9]+"/i',
            'width="80%"',
            $val_phpinfo
        );
        $data['phpinfo'] = $val_phpinfo;
        return $data;
    }
}
