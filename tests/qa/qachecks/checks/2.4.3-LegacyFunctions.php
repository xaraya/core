<?php
/**
 * File: $Id$
 *
 * Legacy Functions... used to support older CMS's
 *
 * @package qachecks
 * @author Roger Keays <r.keays@ninthave.net>
 * 05 May 2004
 */


class LegacyFunctions extends QACheckRegexp
{
    var $id = '2.4.3';
    var $name = "Legacy Functions (from other CMS's)";
    var $fatal = true;
    var $filetype = 'all';
    var $regexps = array(
        '/pnGetBaseURI/',
        '/pnGetBaseURL/',
        '/pnRedirect/',
        '/pnIsRedirected/',
        '/pnLocalReferer/',
        '/pnUserLoggedIn/',
        '/pnUserGetVars/',
        '/pnUserDelVar/',
        '/pnUserGetAll/',
        '/pnModGetName/',
        '/pnModAvailable/',
        '/pnModRegisterHook/',
        '/pnModUnregisterHook/',
        '/pnGetStatusMsg/',
        '/pnBlock_show/',
        '/pnBlock_groupShow/',
        '/accesslevelname/',
        '/accesslevelnames/',
        '/pnMail/',
        '/pnVarValidate/',
        '/pnModGetUserMods/',
        '/pnModGetAdminMods/',
        '/pnConfigDelVar/',
        '/pnBlockGetInfo/',
        '/pnBlockLoad/',
        '/pnBlockLoadAll/',
        '/pnBlockShow/',
        '/pnBlockVarsFromContent/',
        '/pnBlockVarsToContent/',
        '/pnConfigGetVar/',
        '/pnConfigSetVar/',
        '/pnDBGetConn/',
        '/pnDBGetTables/',
        '/pnDBInit/',
        '/pnInit/',
        '/pnModAPIFunc/',
        '/pnModAPILoad/',
        '/pnModCallHooks/',
        '/pnModDBInfoLoad/',
        '/pnModDelVar/',
        '/pnModFunc/',
        '/pnModGetIDFromName/',
        '/pnModGetInfo/',
        '/pnModGetVar/',
        '/pnModLoad/',
        '/pnModSetVar/',
        '/pnModURL/',
        '/pnSecAddSchema/',
        '/pnSecAuthAction/',
        '/pnSecConfirmAuthKey/',
        '/pnSecGenAuthKey/',
        '/pnSecGetAuthInfo/',
        '/pnSecGetLevel/',
        '/pnSecureInput/',
        '/pnSessionDelVar/',
        '/pnSessionGetVar/',
        '/pnSessionSetVar/',
        '/pnUserGetLang/',
        '/pnUserGetVar/',
        '/pnUserLogIn/',
        '/pnUserLogOut/',
        '/pnUserSetVar/',
        '/pnVarCensor/',
        '/pnVarCleanFromInput/',
        '/pnVarPrepForDisplay/',
        '/pnVarPrepForOS/',
        '/pnVarPrepForStore/',
        '/pnVarPrepHTMLDisplay/',
        '/pnUserGetTheme/',
        '/pnThemeLoad/',
        '/opentable/',
        '/closetable/'
    );
/*
from html/includes/pnHTML.php
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
class pnHTML:
    pnHTML
    GetOutputMode
    SetOutputMode
    GetInputMode
    SetInputMode
    UploadMode
    GetOutput
    PrintPage
    StartPage
    EndPage
    Pager
    ConfirmAction
    Text
    Title
    BoldText
    Linebreak
    URL
    TableStart
    TableRowStart
    TableColStart
    TableColEnd
    TableRowEnd
    TableEnd
    TableAddRow
    FormStart
    FormEnd
    FormSubmit
    FormText
    FormTextArea
    FormHidden
    FormSelectMultiple
    FormCheckbox
    FormFile 
    );
*/
}

/* add to the list of checks when imported */
if (isset($checks)) {
    $checks[] = new LegacyFunctions();
}
?>
