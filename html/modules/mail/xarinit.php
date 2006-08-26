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
 * @param none $
 * @return true on success or void or false on failure
 * @throws 'DATABASE_ERROR'
 * @todo nothing
 */
function mail_init()
{
// TODO: create separate xar_mail_queue table here someday
    xarModSetVar('mail', 'server', 'mail');
    xarModSetVar('mail', 'replyto', '0');
    xarModSetVar('mail', 'wordwrap', '78');
    xarModSetVar('mail', 'priority', '3');
    xarModSetVar('mail', 'smtpPort', '25');
    xarModSetVar('mail', 'smtpHost', 'Your SMTP Host');
    xarModSetVar('mail', 'encoding', '8bit');
    xarModSetVar('mail', 'html', false);  

    // when a module item is created
    if (!xarModRegisterHook('item', 'create', 'API',
            'mail', 'admin', 'hookmailcreate')) {
        return false;
    }
    // when a module item is deleted
    if (!xarModRegisterHook('item', 'delete', 'API',
            'mail', 'admin', 'hookmaildelete')) {
        return false;
    }
    // when a module item is changed
    if (!xarModRegisterHook('item', 'update', 'API',
            'mail', 'admin', 'hookmailchange')) {
        return false;
    }

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
 * @param none $
 * @returns bool
 * @throws DATABASE_ERROR
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
 * @throws no exceptions
 * @todo create separate xar_mail_queue someday
 * @todo allow mail gateway functionality
 */
function mail_upgrade($oldVersion)
{
    switch($oldVersion) {
    case '0.1':
    case '0.1.0':
        // clean up double hook registrations
        xarModUnregisterHook('item', 'update', 'API', 'mail', 'admin', 'hookmailchange');
        xarModRegisterHook('item', 'update', 'API', 'mail', 'admin', 'hookmailchange');
        $hookedmodules = xarModAPIFunc('modules', 'admin', 'gethookedmodules',
                                       array('hookModName' => 'mail'));
        if (isset($hookedmodules) && is_array($hookedmodules)) {
            foreach ($hookedmodules as $modname => $value) {
                foreach ($value as $itemtype => $val) {
                    xarModAPIFunc('modules','admin','enablehooks',
                                  array('hookModName' => 'mail',
                                        'callerModName' => $modname,
                                        'callerItemType' => $itemtype));
                }
            }
        }

    case '0.1.1':
        xarModSetVar('mail', 'ShowTemplates', false);
        xarModSetVar('mail', 'suppresssending', false);
        xarModSetVar('mail', 'redirectsending', false);
        xarModSetVar('mail', 'redirectaddress', '');

    case '0.1.2': //current version
        break;
    }
    return true;
}

/**
 * Delete the mail module
 *
 * @author John Cox <niceguyeddie@xaraya.com>
 * @access public
 * @param no $ parameters
 * @return true on success or false on failure
 * @todo restore the default behaviour prior to 1.0 release
 */
function mail_delete()
{
// TODO: delete separate xar_mail_queue table here someday
    xarModDelVar('mail', 'server');
    xarModDelVar('mail', 'replyto');
    xarModDelVar('mail', 'wordwrap');
    xarModDelVar('mail', 'priority');
    xarModDelVar('mail', 'smtpPort');
    xarModDelVar('mail', 'smtpHost');
    xarModDelVar('mail', 'encoding');
    xarModDelVar('mail', 'ShowTemplates');  
    xarModDelVar('mail', 'ShowTemplates');
    xarModDelVar('mail', 'suppresssending');
    xarModDelVar('mail', 'redirectsending');
    xarModDelVar('mail', 'redirectaddress');
    // Remove Masks and Instances
    xarRemoveMasks('mail');
    xarRemoveInstances('mail');

    return true;
}

?>