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
use EmptyParameterException;
use FileNotFoundException;
use sys;

/**
 * mail adminapi getmessagestrings function
 * @extends MethodClass<AdminApi>
 */
class GetmessagestringsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string   $args['template'] name of the email type which has apair of -subject and -message files<br/>
     * string   $args['module'] module directory in var/messaging
     * @return array of strings of file contents read
     * @see AdminApi::getmessagestrings()
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

        $messaginghome = sys::varpath() . "/messaging/" . $module;
        $subjtemplate = $messaginghome . "/" . $template . "-subject.xt";
        if (!file_exists($subjtemplate)) {
            throw new FileNotFoundException($subjtemplate);
        }
        $string = '';
        $fd = fopen($subjtemplate, 'r');
        while (!feof($fd)) {
            $line = fgets($fd, 1024);
            $string .= $line;
        }
        $subject = $string;
        fclose($fd);

        $msgtemplate = $messaginghome . "/" . $template . "-message.xt";
        if (!file_exists($msgtemplate)) {
            throw new FileNotFoundException($msgtemplate);
        }

        $string = '';
        $fd = fopen($msgtemplate, 'r');
        while (!feof($fd)) {
            $line = fgets($fd, 1024);
            $string .= $line;
        }
        $message = $string;
        fclose($fd);

        return ['subject' => $subject, 'message' => $message];
    }
}
