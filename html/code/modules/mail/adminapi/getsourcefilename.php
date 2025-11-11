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
use sys;
use DirectoryNotFoundException;
use FileNotFoundException;

/**
 * mail adminapi getsourcefilename function
 * @extends MethodClass<AdminApi>
 */
class GetsourcefilenameMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Psspl : Added API function to determine the template sourcefile to use
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string   $args['modName']      Module name doing the request *   <br/>
     * string   $args['modtype']      The base name for the template user|admin<br/>
     * string   $args['templateName'] The name for the template to use if any<br/>
     * string   $args['mailtype']     The type of mail html|text <br/>
     * string   $args['messagepart']  The part of the message tobe sent subject|body
     * @return string
     * @see AdminApi::getsourcefilename()
     */
    public function __invoke(array $args = [])
    {
        extract($args);

        // Template search order:
        // 1. var/messaging/{module}/{type}-{template Name}-{message part}-{mail type}.xt
        // 2. var/messaging/{module}/{type}-{template Name}-{message part}.xt
        // 3. var/messaging/{template Name}-{message part}-{mail type}.xt
        // 4. var/messaging/{template Name}-{message part}.xt
        // 5. complain (later on)

        $tplMessagingDir = sys::varpath() . "/messaging/$modName";
        if (!file_exists($tplMessagingDir)) {
            throw new DirectoryNotFoundException($tplMessagingDir);
        }

        unset($sourceFileName);

        $this->log()->info("TPL: 1. $tplMessagingDir/$modType-$templateName-$messagepart-$mailType.xt");
        $this->log()->info("TPL: 2. $tplMessagingDir/$modType-$templateName-$messagepart.xt");

        if (!empty($templateName)
            && file_exists($sourceFileName = "$tplMessagingDir/$modType-$templateName-$messagepart-$mailType.xt")) {

        } elseif (!empty($templateName)
            && file_exists($sourceFileName = "$tplMessagingDir/$modType-$templateName-$messagepart.xt")) {

        } elseif (!empty($templateName)
            && file_exists($sourceFileName = "$tplMessagingDir/$templateName-$messagepart-$mailType.xt")) {

        } elseif (!empty($templateName)
            && file_exists($sourceFileName = "$tplMessagingDir/$templateName-$messagepart.xt")) {

        } else {
            throw new FileNotFoundException($this->ml('No template was found corresponding to #(1) #(2)', $templateName, $messagepart));
        }
        $sourceFileName = str_replace('//', '/', $sourceFileName);
        // assert(isset($sourceFileName), /* The source file for the template has no value in xar::tpl()->module */);
        return $sourceFileName;
    }
}
