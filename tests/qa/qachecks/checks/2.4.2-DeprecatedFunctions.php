<?php
/**
 * File: $Id$
 *
 * @package qachecks
 * @author Roger Keays <r.keays@ninthave.net>
 * 05 May 2004
 */


class DeprecatedFunctions extends QACheckRegexp
{
    var $id = '2.4.1a';
    var $name = "Deprecated Functions";
    var $fatal = true;
    var $filetype = 'all';
    var $regexps = array(
        '/xarExceptionSet/',         // (now xarErrorSet)
        '/xarExceptionMajor/',       // (now xarCurrentErrorType)
        '/xarExceptionID/',          // (now xarCurrentErrorId)
        '/xarExceptionValue/',       // (now xarCurrentError)
        '/xarExceptionFree/',        // (now xarErrorFree)
        '/xarExceptionHandled/',     // (now xarErrorHandled)
        '/xarExceptionRender/',      // (now xarErrorRender)
        '/xarTplAddJavaScriptCode/', // (now xarTplAddJavaScript)
        '/xarUserGetAll/',
        '/xarUserGetVars/',
        '/xarVarCleanUntrusted/',
        '/xarVarCleanFromInput/',
        '/<xar:ternary>/',
        '/xarTpl_headJavaScript/',
        '/xarTpl_bodyJavaScript/',
        '/xarBlockTypeExists/',
        '/xarUserGetLang/',
        '/xarUser_getThemeName/',
        '/xarSecAddSchema/',
        '/xarGetStatusMsg/'
    );
}

/* add to the list of checks when imported */
if (isset($checks)) {
    $checks[] = new DeprecatedFunctions();
}
?>
