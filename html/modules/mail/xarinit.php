<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: John Cox via phpMailer Team
// Purpose of file:  Initialisation functions for the Mail Hook
// ----------------------------------------------------------------------

/**
 * initialise the mail module
 */
function mail_init()
{
    xarModSetVar('mail', 'server', 'mail');
    //xarModSetVar('mail', 'adminname', 'Your Name');
    //xarModSetVar('mail', 'adminmail', 'Your Email');
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
 * @param none
 * @returns bool
 * @raise DATABASE_ERROR
 */
function mail_activate()
{


    return true;
}

/**
 * upgrade the mail module from an old version
 */
function mail_upgrade($oldversion)
{
    return false;
}

/**
 * delete the mail module
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