<?php

/**
 * @package modules\roles
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Roles\AdminGui;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Roles\AdminGui;
use Query;
use xarController;
use xarMod;
use xarModVars;
use xarRoles;
use xarSec;
use xarSecurity;
use xarServer;
use xarSession;
use xarTpl;
use xarUser;
use xarVar;
use sys;

sys::import('xaraya.modules.method');

/**
 * roles admin sendmail function
 * @extends MethodClass<AdminGui>
 */
class SendmailMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Send mail
     * @package modules\roles
     * @subpackage roles
     * @category Xaraya Web Applications Framework
     * @version 2.4.0
     * @copyright see the html/credits.html file in this release
     * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
     * @link http://xaraya.info/index.php/release/27.html
     * @see AdminGui::sendmail()
     */
    public function __invoke(array $args = [])
    {
        // Security
        if (!$this->sec()->checkAccess('MailRoles')) {
            return;
        }

        // Get parameters from whatever input we need
        $this->var()->find('id', $id, 'int:0:', 0);
        $this->var()->find('state', $state, 'int:0:', xarRoles::ROLES_STATE_CURRENT);
        $this->var()->find('message', $message, 'str:1:', '');
        $this->var()->find('subject', $subject, 'str:1', '');
        $this->var()->find('includesubgroups', $includesubgroups, 'int:0:', 0);

        // Confirm authorisation code.
        if (!$this->sec()->confirmAuthKey()) {
            return $this->ctl()->badRequest('bad_author');
        }
        // Get user information
        // Get the current query
        sys::import('xaraya.structures.query');
        $q = new Query();
        $q = unserialize((string) $this->session()->getVar('rolesquery'));

        // only need the id, name and email fields
        $q->clearfields();
        $q->addfields(['r.id','r.name','r.uname','r.email']);

        // Open a connection and run the query
        $q->run();

        foreach ($q->output() as $user) {
            $users[$user['id']] = ['id'      => $user['id'],
                'name'     => $user['name'],
                'email'    => $user['email'],
                'username' => $user['uname'],
            ];
        }

        // Check if we also want to send to subgroups
        // In this case we'll just pick out the descendants in the same state
        // Note the nice use of the array keys to overwrite users we already have
        if ($id != 0 && ($includesubgroups == 1)) {
            $parentgroup = xarRoles::get($id);
            $descendants = $parentgroup->getDescendants($state);

            foreach ($descendants as $key => $user) {
                $users[$user->getID()] = ['id' => $user->getID(),
                    'name'     => $user->getName(),
                    'email'    => $user->getEmail(),
                    'username' => $user->getUser(),
                ];
            }
        }

        // To prevent the template comments from being sent with the mail
        // messages, we turn it off temporarily
        $themecomments = $this->mod('themes')->getVar('ShowTemplates');
        $this->mod('themes')->setVar('ShowTemplates', 0);

        // Add root tage and compile the subject and message
        $subject  = xarTpl::compileString('<xar:template xmlns:xar="http://xaraya.com/2004/blocklayout">' . $subject . '</xar:template>');
        $message  = xarTpl::compileString('<xar:template xmlns:xar="http://xaraya.com/2004/blocklayout">' . $message . '</xar:template>');

        // Define the variables automatically available to all templates
        // LEGACY
        $data = [
            'sitename'   => $this->mod('themes')->getVar('SiteName'),
            'siteslogan' => $this->mod('themes')->getVar('SiteSlogan'),
            'siteadmin'  => $this->mod('mail')->getVar('adminname'),
            'adminmail'  => $this->mod('mail')->getVar('adminmail'),
            'siteurl'    => $this->ctl()->getBaseURL(),
            'myname'     => $this->user()->getName(),
            'myuname'    => $this->user()->getUser(),
            'myuid'      => $this->user()->getId(),
        ];

        // now send the mails
        foreach ($users as $user) {
            //Get the common search and replace values
            $data['recipientid']      = $user['id'];
            $data['recipientname']     = $user['name'];
            $data['recipientusername'] = $user['username'];
            $data['recipientemail']    = $user['email'];

            // Get the output through BL
            $mailsubject = xarTpl::string($subject, $data);
            $mailmessage = xarTpl::string($message, $data);

            if (!$this->mod()->apiFunc(
                'mail',
                'admin',
                'sendmail',
                ['info'    => $user['email'],
                    'name'    => $user['name'],
                    'subject' => $mailsubject,
                    'message' => $mailmessage]
            )) {
                return;
            }
        }
        // If it was on, turn it back on
        $this->mod('themes')->setVar('ShowTemplates', $themecomments);

        $this->ctl()->redirect($this->ctl()->getModuleURL('roles', 'admin', 'createmail'));
        return true;
    }
}
