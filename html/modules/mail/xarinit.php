<?php
/**
 * File: $Id: s.xaradmin.php 1.28 03/02/08 17:38:40-05:00 John.Cox@mcnabb. $
 * 
 * Mail System
 * 
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * @subpackage mail module
 * @author John Cox <admin@dinerminor.com> 
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
    xarModSetVar('mail', 'server', 'mail');
    xarModSetVar('mail', 'replyto', '0');
    xarModSetVar('mail', 'wordwrap', '50');
    xarModSetVar('mail', 'priority', '3');
    xarModSetVar('mail', 'smtpPort', '25');
    xarModSetVar('mail', 'smtpHost', 'Your SMTP Host'); 
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

    return true;
} 

/**
 * Activate the mail module
 * 
 * @access public 
 * @param none $ 
 * @returns bool
 * @raise DATABASE_ERROR
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
 * @todo nothing
 */
function mail_upgrade($oldversion)
{
    return false;
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
    xarModDelVar('mail', 'server');
    xarModDelVar('mail', 'adminname');
    xarModDelVar('mail', 'adminmail');
    xarModDelVar('mail', 'wordwrap');
    xarModDelVar('mail', 'priority');
    xarModDelVar('mail', 'replyto');
    xarModDelVar('mail', 'replytoname');
    xarModDelVar('mail', 'replytoemail'); 
    // Remove Masks and Instances
    xarRemoveMasks('mail');
    xarRemoveInstances('mail');

    return true;
} 

?>