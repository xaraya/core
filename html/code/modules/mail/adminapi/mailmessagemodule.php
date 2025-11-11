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

/**
 * mail adminapi mailmessagemodule function
 * @extends MethodClass<AdminApi>
 */
class MailmessagemoduleMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Psspl : Added API function to get the template file output for the mail message.
     * @access public
     * @param array<string,mixed> $args array of optional parameters<br/>
     * string   $args['modName'] the module name<br/>
     * string   $args['modType']      user|admin<br/>
     * string   $args['funcName']     module function to template  <br/>
     * string   $args['templateName'] string the specific template to call<br/>
     * string   $args['tplData']      arguments for the template<br/>
     * string   $args['mailtype']     The type of mail html|text
     * @return string xar::tpl()->file($sourceFileName, $tplData)
     * @see AdminApi::mailmessagemodule()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();
        // Get the right source filename
        $params = ['modName' => $modName,
            'modType' => $modType,
            'templateName' => $templateName,
            'mailType' => $mailType,
            'messagepart' => 'message'];
        $sourceFileName = $adminapi->getsourcefilename($params);
        return $this->tpl()->file($sourceFileName, $tplData);
    }
}
