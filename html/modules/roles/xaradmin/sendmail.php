<?php
/**
 * Send mail
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */

function roles_admin_sendmail()
{
    // Get parameters from whatever input we need
    if (!xarVarFetch('id',     'int:0:', $id, 0)) return;
    if (!xarVarFetch('state',   'int:0:', $state, ROLES_STATE_CURRENT)) return;
    if (!xarVarFetch('message', 'str:1:', $message,'')) return;
    if (!xarVarFetch('subject', 'str:1',  $subject, '')) return;
    if (!xarVarFetch('includesubgroups','int:0:',$includesubgroups,0,XARVAR_NOT_REQUIRED));

    // Confirm authorisation code.
    if (!xarSecConfirmAuthKey()) return;
    // Security check
    if (!xarSecurityCheck('MailRoles')) return;
    // Get user information
    // Get the current query
    sys::import('modules.roles.class.xarQuery');
    $q = new xarQuery();
    $q = unserialize(xarSession::getVar('rolesquery'));

    // only need the id, name and email fields
    $q->clearfields();
    $q->addfields(array('r.id','r.name','r.uname','r.email'));

    // Open a connection and run the query
    $q->run();

    foreach ($q->output() as $user) {
        $users[$user['id']] = array('id'      => $user['id'],
                                    'name'     => $user['name'],
                                    'email'    => $user['email'],
                                    'username' => $user['uname']
        );
    }

    // Check if we also want to send to subgroups
    // In this case we'll just pick out the descendants in the same state
    // Note the nice use of the array keys to overwrite users we already have
    if ($id != 0 && ($includesubgroups == 1)) {
        $parentgroup = xarRoles::get($id);
        $descendants = $parentgroup->getDescendants($state);

        while (list($key, $user) = each($descendants)) {
            $users[$user->getID()] = array('id' => $user->getID(),
                'name'     => $user->getName(),
                'email'    => $user->getEmail(),
                'username' => $user->getUser()
                );
        }
    }

    // To prevent the template comments from being sent with the mail
    // messages, we turn it off temporarily
    $themecomments = xarModVars::get('themes','ShowTemplates');
    xarModVars::set('themes','ShowTemplates',0);

    // Add root tage and compile the subject and message
    $subject  = xarTplCompileString('<xar:template xmlns:xar="http://xaraya.com/2004/blocklayout">'.$subject.'</xar:template>');
    $message  = xarTplCompileString('<xar:template xmlns:xar="http://xaraya.com/2004/blocklayout">'.$message.'</xar:template>');

    // Define the variables automatically available to all templates
    $data = array(
        'sitename'   => xarModVars::get('themes', 'SiteName'),
        'siteslogan' => xarModVars::get('themes', 'SiteSlogan'),
        'siteadmin'  => xarModVars::get('mail', 'adminname'),
        'siteurl'    => xarServerGetBaseURL(),
        'myname'     => xarUserGetVar('name'),
        'myuname'    => xarUserGetVar('uname'),
        'myuid'      => xarUserGetVar('id'),
    );

    // now send the mails
    foreach ($users as $user) {
        //Get the common search and replace values
        $data['recipientid']      = $user['id'];
        $data['recipientname']     = $user['name'];
        $data['recipientusername'] = $user['username'];
        $data['recipientemail']    = $user['email'];

        // Get the output through BL
        $mailsubject = xarTplString($subject, $data);
        $mailmessage = xarTplString($message, $data);

        if (!xarModAPIFunc('mail', 'admin', 'sendmail',
            array('info'    => $user['email'],
                  'name'    => $user['name'],
                  'subject' => $mailsubject,
                  'message' => $mailmessage))) return;
    }
    // If it was on, turn it back on
    xarModVars::set('themes','ShowTemplates',$themecomments);

    xarResponseRedirect(xarModURL('roles', 'admin', 'createmail'));
    return true;
}
?>
