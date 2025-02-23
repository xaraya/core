<?php

/**
 * @package modules\mail
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Mail\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Mail\AdminApi;
use DirectoryNotFoundException;
use xarController;
use sys;

sys::import('xaraya.modules.method');

/**
 * mail adminapi getmessagetemplates function
 * @extends MethodClass<AdminApi>
 */
class GetmessagetemplatesMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string   $args['module'] module directory in var/messaging
     * @return array of template names and labels
     * @see AdminApi::getmessagetemplates()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        if (empty($module)) {
            $module = $this->ctl()->getRequest()->getModule();
        }

        $messaginghome = sys::varpath() . "/messaging/" . $module;
        if (!file_exists($messaginghome)) {
            throw new DirectoryNotFoundException($messaginghome);
        }

        $dd = opendir($messaginghome);
        $templates = [];
        while (($filename = readdir($dd)) !== false) {
            if (!is_dir($messaginghome . "/" . $filename)) {
                $pos = strpos($filename, '-message.xt');
                if (!($pos === false)) {
                    $templatename = substr($filename, 0, $pos);
                    $templatelabel = ucfirst($templatename);
                    $templates[] = ['key' => $templatename, 'value' => $templatelabel];
                }
            }
        }
        closedir($dd);

        return $templates;
    }
}
