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
use EmptyParameterException;
use FileNotFoundException;
use sys;

sys::import('xaraya.modules.method');

/**
 * mail adminapi updatemessagestrings function
 * @extends MethodClass<AdminApi>
 */
class UpdatemessagestringsMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     *
     * @author John Cox <niceguyeddie@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string   $args['module'] module directory in var/messaging<br/>
     * string   $args['template'] name of the email type which has apair of -subject and -message files<br/>
     * string   $args['subject'] new subject<br/>
     * string   $args['message'] new message
     * @return bool of strings of file contents read
     * @see AdminApi::updatemessagestrings()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        if (empty($template)) {
            throw new EmptyParameterException('template');
        }

        if (empty($module)) {
            $module = $this->ctl()->getRequest()->getModule();
        }
        if (empty($subject)) {
            $subject = '';
        }
        if (empty($message)) {
            $message = '';
        }

        $messaginghome = sys::varpath() . '/messaging/' . $module;
        if (!file_exists($messaginghome)) {
            throw new DirectoryNotFoundException($messaginghome);
        }

        $filename = $messaginghome . '/' . $template . '-subject.xt';
        if (is_writable($filename)) {
            unlink($filename);
            if (!$handle = fopen($filename, 'a')) {
                throw new FileNotFoundException($filename, 'Can not find or can not open the file: #(1)');
            }
            if (fwrite($handle, $subject) === false) {
                throw new FileNotFoundException($filename, 'Can not find or can not write to the file: #(1)');
            }
            fclose($handle);
        }

        $filename = $messaginghome . '/' . $template . '-message.xt';
        if (is_writable($filename)) {
            unlink($filename);
            if (!$handle = fopen($filename, 'a')) {
                throw new FileNotFoundException($filename, 'Can not find or can not open the file: #(1)');
            }
            if (fwrite($handle, $message) === false) {
                throw new FileNotFoundException($filename, 'Can not find or can not write to the file: #(1)');
            }
            fclose($handle);
        }

        return true;
    }
}
