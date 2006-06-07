<?php
/**
 * File: $Id$
 *
 * Quick & dirty import of phpBB data into Xaraya test sites
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage import
 * @author mikespub <mikespub@xaraya.com>
*/

// initialize the Xaraya core
include 'includes/xarCore.php';
xarCoreInit(XARCORE_SYSTEM_ALL);

// avoid issues when caching is enabled - to be cleaned up
include 'includes/xarCache.php';

if(!xarVarFetch('step',     'isset', $step,      NULL, XARVAR_NOT_REQUIRED)) {return;}
if(!xarVarFetch('startnum', 'isset', $startnum,  NULL, XARVAR_NOT_REQUIRED)) {return;}

// pre-fill the module name (if any) for hooks
xarRequestGetInfo();

if (!isset($step)) {
// start the output buffer
ob_start();
}
?>

<h3>Quick and dirty import of test data from an existing phpBB installation</h3>

<?php
$prefix = xarDBGetSystemTablePrefix();
if (isset($step)) {
    if ($step == 1 && !isset($startnum)) {
        if (!xarVarFetch('oldprefix', 'str:1:', $oldprefix, '')) return;
        if (!xarVarFetch('importmodule', 'str:1:', $importmodule, '')) return;
        if (!xarVarFetch('importusers', 'int:0:', $importusers, 0)) return;
    } elseif ($step > 1 || isset($startnum)) {
        $oldprefix = xarModGetVar('installer','oldprefix');
        $importmodule = xarModGetVar('installer','importmodule');
    }
}
if (!isset($oldprefix) || $oldprefix == $prefix || !preg_match('/^[a-z0-9_-]+$/i',$oldprefix)) {
?>
    Requirement : you must be using the same database, but a different prefix...
    <p></p>
    <form method="POST" action="import_phpbb.php">
    <table border="0" cellpadding="4">
    <tr><td align="right">Prefix used for your phpBB tables</td><td>
    <input type="text" name="oldprefix" value="phpbb"></td></tr>
    <tr><td align="right">Import phpBB users</td><td>
    <select name="importusers">
    <option value="0">Don't import users (= test only)</option>
    <option value="1" selected="selected">Create all users</option>
    <option value="2">Try to match usernames, and create otherwise (TODO)</option>
    <option value="3">Do something else...like using xarBB for instance :)</option>
    </select></td></tr>
    <tr><td align="right">Import into</td><td>
    <select name="importmodule">
    <option value="articles">articles</option>
    <option value="xarbb" selected="selected">xarBB</option>
    </select></td></tr>
    <tr><td colspan=2 align="middle">
    <input type="submit" value=" Import Data "></td></tr>
    </table>
    <input type="hidden" name="step" value="1">
    <input type="hidden" name="module" value="roles">
    </form>
    Recommended usage :<br /><ol>
    <li>don't use this on a live site</li>
    <li>install Xaraya and import your original site with import8.php</li>
    <li>copy the *-forums.xd templates over to your modules/articles/xartemplates directory</li>
    <li>copy this script to your Xaraya html directory and try it out...</li>
</ol>

<?php
} else {
    if ($step == 1 && !isset($startnum)) {
        xarModSetVar('installer','oldprefix',$oldprefix);
        xarModSetVar('installer','importmodule',$importmodule);
        if (empty($importusers)) {
            $step = 2;
        }
    }

    $dbconn =& xarDBGetConn();

    if (!xarModAPILoad('roles','admin')) {
        die("Unable to load the users admin API");
    }
    if (!xarModAPILoad('categories','user')) {
        die("Unable to load the categories user API");
    }
    if (!xarModAPILoad('categories','admin')) {
        die("Unable to load the categories admin API");
    }
    if ($importmodule == 'articles' && !xarModAPILoad('articles','admin')) {
        die("Unable to load the articles admin API");
    }
    if ($importmodule == 'xarbb' && !xarModAPILoad('xarbb','admin')) {
        die("Unable to load the xarbb admin API");
    }
    if (!xarModAPILoad('comments','user')) {
        die("Unable to load the comments user API");
    }
    if (!xarModAPILoad('dynamicdata','util')) {
        die("Unable to load the dynamicdata util API");
    }
    if (xarModIsAvailable('polls') && !xarModAPILoad('polls','admin')) {
        die("Unable to load the polls admin API");
    }
    if (xarModIsAvailable('hitcount') && xarModAPILoad('hitcount','admin')) {
        $docounter = 1;
    }
    $tables =& xarDBGetTables();

    $importfiles = array(
                         1 => array('import_phpbb_users.php'),
                         2 => array('import_phpbb_pubtype.php',
                                    'import_phpbb_categories.php',
                                    'import_phpbb_forums.php'),
                         3 => array('import_phpbb_topics.php'),
                         4 => array('import_phpbb_posts.php'),
                         5 => array('import_phpbb_vote_desc.php',
                                    'import_phpbb_vote_results.php'),
                         6 => array('import_phpbb_privmsgs.php'),
// TODO: add the rest (groups, ranks, ...) :-)
                         7 => array('import_phpbb_cleanup.php'),
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

flush();

// done
exit;
 
?>
