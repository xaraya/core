<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles\AdminApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Roles\AdminApi;
use EmptyParameterException;
use FileNotFoundException;
use sys;

/**
 * roles adminapi getmessageincludestring function
 * @extends MethodClass<AdminApi>
 */
class GetmessageincludestringMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string   $args['template'] name of the template without .xt extension<br/>
     * string   $args['module'] module directory in var/messaging
     * @return string of file contents read
     * @see AdminApi::getmessageincludestring()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        if (!isset($template)) {
            throw new EmptyParameterException('template');
        }

        if (!isset($module)) {
            $module = $this->ctl()->getRequest()->getModule();
        }

        // Get the template that defines the substitution vars
        $messaginghome = sys::varpath() . "/messaging/" . $module;
        $msgtemplate = $messaginghome . "/includes/" . $template . ".xt";
        if (!file_exists($msgtemplate)) {
            throw new FileNotFoundException($msgtemplate);
        }

        $string = '';
        $fd = fopen($msgtemplate, 'r');
        while (!feof($fd)) {
            $line = fgets($fd, 1024);
            $string .= $line;
        }
        fclose($fd);
        return $string;
    }
}
