<?php
/**
 * File: $Id$
 *
 * Quick & dirty import of PN .71x data into Xaraya test sites
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

    if(!xarVarFetch('step',     'isset', $step,      NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('startnum', 'isset', $startnum,  NULL, XARVAR_NOT_REQUIRED)) {return;}


// pre-fill the module name (if any) for hooks
xarRequestGetInfo();

if (!isset($step)) {
// start the output buffer
ob_start();
}
?>

<h3>Begin your import of PHP-Nuke 6.5 data</h3>

<?php
$prefix = xarDBGetSystemTablePrefix();

// Get and set the database type
$dbtype  = xarCore_getSystemVar('DB.Type');
xarModSetVar('installer','dbtype',$dbtype);

if (isset($step)) {
    if ($step == 1 && !isset($startnum)) {
    if(!xarVarFetch('oldprefix', 'isset', $oldprefix,  NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('reset',     'isset', $reset,      NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('resetcat',  'isset', $resetcat,   NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('imgurl',    'isset', $imgurl,     NULL, XARVAR_NOT_REQUIRED)) {return;}

    } elseif ($step > 1 || isset($startnum)) {
        $oldprefix = xarModGetVar('installer','oldprefix');
        $reset = xarModGetVar('installer','reset');
        $resetcat = xarModGetVar('installer','resetcat');
        $imgurl = xarModGetVar('installer','imgurl');
    }
}
if (!isset($oldprefix) || $oldprefix == $prefix || !preg_match('/^[a-z0-9_-]+$/i',$oldprefix)) {
?>
    Requirement for use : The PHP-Nuke data and the Xaraya data HAVE to be in the same database for this script to work and they HAVE to be using a different prefix.  We read the PHP-Nuke data and use the Xaraya API to import the data into Xaraya.  In order to do this we must be reading from the same database.  Easiest solution is to copy your PHP-Nuke data into the same database as your Xaraya installation.
    <p></p>
    <form method="POST" action="import_nuke.php">
    <table border="0" cellpadding="4">
    <tr><td align="right">Prefix used in your Nuke 6.5 site</td><td>
    <input type="text" name="oldprefix" value="nuke"></td></tr>
    <tr><td align="right">URL of the /images directory on your Nuke 6.5 site</td><td>
    <input type="text" name="imgurl" value="/images"></td></tr>
    <tr><td align="right">Reset corresponding Xaraya data ?</td><td>
    <input type="checkbox" name="reset" checked></td></tr>
    <tr><td align="right">Reset existing Xaraya categories ?</td><td>
    <input type="checkbox" name="resetcat" checked></td></tr>
    <tr><td colspan=2 align="middle">
    <input type="submit" value=" Import Data "></td></tr>
    </table>
    <input type="hidden" name="step" value="1">
    <input type="hidden" name="module" value="roles">
    </form>
    Recommended usage :<br /><ol>
    <li>install Xaraya with the 'Community Site' option</li>
    <li>initialize and activate the following modules :<ul>
<li>categories</li>
<li>comments</li>
<li>hitcount</li>
<li>ratings (optional)</li>
<li>articles</li>
<li>polls (if you want to import those)</li>
</ul>
[do not modify the default privileges, hooks etc. yet]
</li>
    <li>copy the import_nuke.php file to your Xaraya html directory and run it. Adapt the prefix and images directory of your old PHP-Nuke site if necessary, and leave both Reset options checked.</li>
    <li>???</li>
    <li>profit ;-)</li>
</ol>

<?php
} else {
    if ($step == 1 && !isset($startnum)) {
        xarModSetVar('installer','oldprefix',$oldprefix);
        if (!isset($reset)) { $reset = 0; }
        xarModSetVar('installer','reset',$reset);
        if (!isset($resetcat)) { $resetcat = 0; }
        xarModSetVar('installer','resetcat',$resetcat);
        if (!isset($imgurl)) { $imgurl = 0; }
        xarModSetVar('installer','imgurl',$imgurl);
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
    if (!xarModAPILoad('articles','admin')) {
        die("Unable to load the articles admin API");
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

    if (!isset($reset)) {
        $reset = 0;
    }
    if (!isset($resetcat)) {
        $resetcat = 0;
    }

    $importfiles = array(
                         1 => array('import_nuke_users.php'),
                         //2 => array('import_pn_topics.php','import_pn_stories_cat.php'),
                         2 => array('import_nuke_topicscat.php'),
                         3 => array('import_nuke_stories.php'),
                         4 => array('import_nuke_queue.php'),
                         5 => array('import_nuke_sections.php'),
                         6 => array('import_nuke_seccont.php'),
                         7 => array('import_nuke_faqcategories.php'),
                         8 => array('import_nuke_faqanswer.php'),
                         9 => array('import_nuke_comments.php'),
                         10 => array('import_nuke_links_categories.php'),
                         11 => array('import_nuke_links_links.php'),
                    // TODO: split into separate steps if you have many of those :)
                         12 => array('import_nuke_poll_desc.php',
                                     'import_nuke_poll_data.php',
                                     'import_nuke_pollcomments.php'),
// TODO: add the rest :-)
                         13 => array('import_nuke_cleanup.php'),
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

// Kill the debugger
xarCore_disposeDebugger();

// done
exit;
 
?>