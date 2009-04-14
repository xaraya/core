<?php
/**
 * Initialise the mail module
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Mail System
 * @link http://xaraya.com/index.php/release/771.html
 */

/**
 * Initialise the mail module
 *
 * @author John Cox <niceguyeddie@xaraya.com>
 * @access public
 * @return true on success or void or false on failure
**/
function mail_init()
{
    xarModVars::set('mail', 'server', 'mail');
    xarModVars::set('mail', 'replyto', '0');
    xarModVars::set('mail', 'wordwrap', '78');
    xarModVars::set('mail', 'priority', '3');
    xarModVars::set('mail', 'smtpPort', '25');
    xarModVars::set('mail', 'smtpHost', 'Your SMTP Host');
    xarModVars::set('mail', 'encoding', '8bit');
    xarModVars::set('mail', 'html', false);

    xarModRegisterHook('item', 'create', 'API', 'mail', 'admin', 'hookmailcreate');
    xarModRegisterHook('item', 'delete', 'API', 'mail', 'admin', 'hookmaildelete');
    xarModRegisterHook('item', 'update', 'API', 'mail', 'admin', 'hookmailchange');

    xarRegisterMask('EditMail','All','mail','All','All','ACCESS_EDIT');
    xarRegisterMask('AddMail','All','mail','All','All','ACCESS_ADD');
    xarRegisterMask('DeleteMail', 'All','mail','All','All','ACCESS_DELETE');
    xarRegisterMask('AdminMail','All','mail','All','All','ACCESS_ADMIN');

    /* This init function brings authsystem to version 0.01 run the upgrades for the rest of the initialisation */
    return mail_upgrade('0.1');
}

/**
 * Activate the mail module
 *
 * @access public
 * @return bool
 */
function mail_activate()
{
    return true;
}

/**
 * Upgrade the mail module from an old version
 *
 * @author John Cox <niceguyeddie@xaraya.com>
 * @access public
 * @param  $oldVersion
 * @return true on success or false on failure
 * @todo create separate xar_mail_queue someday
 * @todo allow mail gateway functionality
 */
function mail_upgrade($oldVersion)
{
    // Upgrade dependent on old version number
    switch ($oldversion) {
        case '2.0':
        case '2.1':
      break;
    }
    return true;
}

/**
 * Delete the mail module
 *
 * @author John Cox <niceguyeddie@xaraya.com>
 * @access public
 * @return true on success or false on failure
 * @todo restore the default behaviour prior to 1.0 release
 */
function mail_delete()
{
// TODO: delete separate xar_mail_queue table here someday
    xarModVars::delete('mail', 'server');
    xarModVars::delete('mail', 'replyto');
    xarModVars::delete('mail', 'wordwrap');
    xarModVars::delete('mail', 'priority');
    xarModVars::delete('mail', 'smtpPort');
    xarModVars::delete('mail', 'smtpHost');
    xarModVars::delete('mail', 'encoding');
    xarModVars::delete('mail', 'ShowTemplates');
    xarModVars::delete('mail', 'ShowTemplates');
    xarModVars::delete('mail', 'suppresssending');
    xarModVars::delete('mail', 'redirectsending');
    xarModVars::delete('mail', 'redirectaddress');
    // Remove Masks and Instances
    xarRemoveMasks('mail');
    xarRemoveInstances('mail');

    return true;
}

?>
