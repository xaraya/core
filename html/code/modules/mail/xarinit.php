<?php
/**
 * Initialise the mail module
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
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

    // Installation complete; check for upgrades
    return mail_upgrade('2.0.0');
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
 * Upgrade this module from an old version
 *
 * @param oldVersion
 * @returns bool
 * @todo create separate xar_mail_queue someday
 * @todo allow mail gateway functionality
 */

function mail_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch ($oldversion) {
        case '2.0.0':
      break;
    }
    return true;
}

/**
 * Delete this module
 *
 * @return bool
 */
function mail_delete()
{
  //this module cannot be removed
  return false;
}

?>
