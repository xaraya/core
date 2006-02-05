<?php
/*
 * File: $Id$
 *
 * Quick & dirty import of xForum data into Xaraya test sites
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage import
 * @ based on phpBB import by author mikespub <mikespub@xaraya.com>
 * @ author jojodee <jojodee@xaraya.com>
 */
/**
 * Note : this file is part of import_xForum.php and cannot be run separately
 */

// initialize the Xaraya core
include 'includes/xarCore.php';
xarCoreInit(XARCORE_SYSTEM_ALL);

if(!xarVarFetch('step',     'isset', $step,      NULL, XARVAR_NOT_REQUIRED)) {return;}
if(!xarVarFetch('startnum', 'isset', $startnum,  NULL, XARVAR_NOT_REQUIRED)) {return;}

// pre-fill the module name (if any) for hooks
xarRequestGetInfo();

if (!isset($step)) {
// start the output buffer
ob_start();
}
?>

<h3>Quick and dirty import from an existing PN xForum install to xarBB</h3>

<?php
$prefix = xarDBGetSystemTablePrefix();
if (isset($step)) {
    if ($step == 1 && !isset($startnum)) {
        if (!xarVarFetch('oldprefix', 'str:1:', $oldprefix, '')) return;
        if (!xarVarFetch('importusers', 'int:0:', $importusers, 0)) return;
    } elseif ($step > 1 || isset($startnum)) {
        $oldprefix = xarModGetVar('installer','oldprefix');
    }
}
if (!isset($oldprefix) || $oldprefix == $prefix || !preg_match('/^[a-z0-9_-]+$/i',$oldprefix)) {
?>
    <b>REQUIREMENTS :</b> Please READ CAREFULLY.
    <ol>
    <li>Make sure you have INSTALLED xarBB.</li>
    <li>If using recent Bitkeeper code, make sure you update BOTH import script AND xarBB from Bitkeeper
    <li>Your xForum tables must be in the SAME database as your current Xaraya installation
        but with a different table prefix - copy over your xForum tables if necessary to your Xaraya Database with a new table prefix.</li>
    <li>Due to differences between Xaraya xarBB data tables and PN xForum data tables, and limitations in this script - there will be some incomplete updating  - you will have to update through your database interface or Xaraya GUI where possible.</li>
    <li>Improvements if and when someone has time :)</li>
    </ol>
    <form method="POST" action="import_xforum.php">
    <table border="0" cellpadding="4">
    <tr><td align="right">Prefix of your xForum tables:</td>
    <td><input type="text" name="oldprefix" value="xar"></td></tr>
    <tr><td align="right">Import xForum users</td>
    <td><select name="importusers">
    <option value="0">Assume all users already exist in Xaraya</option>
    <option value="1">Create all users</option>
    <option value="2">Try and match users, else create one (TO DO)</option>
    </select></td></tr>
    <tr><td colspan=2 align="middle">
    <input type="submit" value=" Import Data "></td></tr>
    </table>
    <input type="hidden" name="step" value="1">
    <input type="hidden" name="module" value="roles">
    </form>
    <h1>Recommended usage :</h1>
    <ol>
    <li>DO NOT use this on a live site</li>
    <li>Copy this script to your Xaraya html directory and try it out...</li>
</ol>

<?php
} else {
    if ($step == 1 && !isset($startnum)) {
        xarModSetVar('installer','oldprefix',$oldprefix);
        if ($importusers >1) {
            $step = 2;
        }
    }

    list($dbconn) = xarDBGetConn();

    if (!xarModAPILoad('roles','admin')) {
        die("Unable to load the users admin API");
    }
    if (!xarModAPILoad('categories','user')) {
        die("Unable to load the categories user API");
    }
    if (!xarModAPILoad('categories','admin')) {
        die("Unable to load the categories admin API");
    }
    if (!xarModAPILoad('articles','admin')) {
        die("Unable to load the articles admin API");
    }
    if (!xarModAPILoad('comments','user')) {
        die("Unable to load the comments user API");
    }
    if (!xarModAPILoad('dynamicdata','util')) {
        die("Unable to load the dynamicdata util API");
    }
//    if (xarModIsAvailable('polls') && !xarModAPILoad('polls','admin')) {
//        die("Unable to load the polls admin API");
//    }
    if (xarModIsAvailable('hitcount') && xarModAPILoad('hitcount','admin')) {
        $docounter = 1;
    }
    $tables = xarDBGetTables();

    $importfiles = array(
                         1 => array('import_xforum_users.php'),
                         2 => array('import_xforum_categories.php',
                                    'import_xforum_forums.php'),
                         3 => array('import_xforum_topics.php'),
                         4 => array('import_xforum_posts.php'),
//                         5 => array('import_xforum_vote_desc.php',
  //                                  'import_xforum_vote_results.php'),
// TODO: add the rest (private messages, groups, ranks, ...) :-)
                         5 => array('import_xforum_cleanup.php'),
                        );

    if (isset($importfiles[$step]) && count($importfiles[$step]) > 0) {
        foreach ($importfiles[$step] as $file) {
            if (!is_file($file)) {
                echo "File $file not found - skipping step $step.<br/>\n";
                $step++;
                break;
            }
            include($file);
        }
    }

}

?>

<?php
if (!isset($step)) {

// catch the output
$return = ob_get_contents();
ob_end_clean();

xarTplSetPageTitle(xarConfigGetVar('Site.Core.SiteName').' :: '.xarML('Import Site'));

//xarTplSetThemeName('Xaraya_Classic');
//xarTplSetPageTemplateName('admin');

// render the page
echo xarTpl_renderPage($return);
}

// Close the session
xarSession_close();

//$dbconn->Close();

flush();

// done
exit;

?>
