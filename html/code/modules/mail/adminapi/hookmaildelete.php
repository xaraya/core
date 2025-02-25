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
use BadParameterException;
use EmptyParameterException;
use IDNotFoundException;
use xarMod;
use xarModVars;
use xarSecurity;
use xarServer;
use xarTpl;
use xarUser;
use sys;

sys::import('xaraya.modules.method');

/**
 * mail adminapi hookmaildelete function
 * @extends MethodClass<AdminApi>
 */
class HookmaildeleteMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * This is a hook function that is called to send mail on deletion of an item
     * @author John Cox <niceguyeddie@xaraya.com>
     * @param array<string,mixed> $args array of optional parameters<br/>
     * integer  $args['modid'] is the module that is sending mail.<br/>
     * integer  $args['objectid'] is the item deleted.
     * @see AdminApi::hookmaildelete()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var AdminApi $adminapi */
        $adminapi = $this->adminapi();

        if (!isset($objectid)) {
            throw new EmptyParameterException('objectid');
        }
        if (!is_numeric($objectid)) {
            throw new BadParameterException(['objectid',$objectid], 'Parameter #(1) ["#(2)"] is not numeric');
        }

        if (!isset($extrainfo) || !is_array($extrainfo)) {
            $extrainfo = [];
        }

        // When called via hooks, modname wil be empty, but we get it from the
        // extrainfo or the current module
        if (empty($modname)) {
            if (!empty($extrainfo['module'])) {
                $modname = $extrainfo['module'];
            } else {
                $modname = $this->mod()->getName();
            }
        }

        $modid = $this->mod()->getRegID($modname);
        if (empty($modid)) {
            throw new IDNotFoundException("modid for $modname");
        }

        if (!isset($itemtype) || !is_numeric($itemtype)) {
            if (isset($extrainfo['itemtype']) && is_numeric($extrainfo['itemtype'])) {
                $itemtype = $extrainfo['itemtype'];
            } else {
                $itemtype = 0;
            }
        }

        // Security Check
        //TODO: if we add to the hook to allow sending of mail to OTHER recipients than the admin
        // we will have to include the following security check and make sure the appropriate privileges are assigned
        //    if (!xarSecurity::check('ManageMail', 0, 'All', "$modname::$objectid", 'mail')) return;

        // Set up variables
        $wordwrap = $this->mod()->getVar('wordwrap');
        $priority = $this->mod()->getVar('priority');
        $encoding = $this->mod()->getVar('encoding');
        if (empty($encoding)) {
            $encoding = '8bit';
            $this->mod()->setVar('encoding', $encoding);
        }
        $from = $this->mod()->getVar('adminmail');
        $fromname = $this->mod()->getVar('adminname');

        // Get the templates for this message
        $strings = $adminapi->getmessagestrings(['module' => 'mail',
            'template' => 'deletehook']);

        $subject = $strings['subject'];
        $message = $strings['message'];

        // Add root tage and compile the subject and message
        $subject  = xarTpl::compileString('<xar:template xmlns:xar="http://xaraya.com/2004/blocklayout">' . $subject . '</xar:template>');
        $message  = xarTpl::compileString('<xar:template xmlns:xar="http://xaraya.com/2004/blocklayout">' . $message . '</xar:template>');

        // Define the variables automatically available to all templates
        // LEGACY
        $data = [
            'sitename'   => $this->mod('themes')->getVar('SiteName'),
            'siteslogan' => $this->mod('themes')->getVar('SiteSlogan'),
            'siteadmin'  => $this->mod()->getVar('adminname'),
            'adminmail'  => $this->mod()->getVar('adminmail'),
            'siteurl'    => $this->ctl()->getBaseURL(),
            'myname'     => $this->user()->getName(),
            'myuname'    => $this->user()->getUser(),
            'myuid'      => $this->user()->getId(),
        ];

        // Substitute the dynamic vars in the template
        $data = array_merge($data, $extrainfo);
        $data['modulename'] = $modname;
        $data['objectid'] = $objectid;
        $subject = xarTpl::string($subject, $data);
        $message = xarTpl::string($message, $data);

        // TODO How to do this with BL? Create yet another template? Don't think so.
        // Send a formatted html message to the mail module for use if the admin has the html turned on.
        $htmlmessage = $message;

        // Set mail args array
        $mailargs = ['info' => $from, // set info to $from
            'subject' => $subject,
            'message' => $message,
            'htmlmessage' => $htmlmessage,
            'name' => $fromname, // set name to $fromname
            'priority' => $priority,
            'encoding' => $encoding,
            'wordwrap' => $wordwrap,
            'from' => $from,
            'fromname' => $fromname];
        // Check if HTML mail has been configured by the admin
        if ((bool) $this->mod()->getVar('html')) {
            $adminapi->sendhtmlmail($mailargs);
        } else {
            $adminapi->sendmail($mailargs);
        }
        // life goes on, and so do hook calls :)
        return $extrainfo;
    }
}
