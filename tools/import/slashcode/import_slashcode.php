<?php
/**
 * File: $Id$
 *
 * Quick & dirty import of Slashcode data into Xaraya sites
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage import
 * @author Richard Cave <rcave@xaraya.com>
 */

// initialize the Xaraya core
include 'includes/xarCore.php';
xarCoreInit(XARCORE_SYSTEM_ALL);

    if(!xarVarFetch('step',     'isset', $step,      NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('startnum', 'isset', $startnum,  NULL, XARVAR_NOT_REQUIRED)) {return;}

// Load table maintenance API
xarDBLoadTableMaintenanceAPI();
    
// pre-fill the module name (if any) for hooks
xarRequestGetInfo();

if (!isset($step)) {
// start the output buffer
ob_start();
}
?>

<h3>Begin your import of Slashcode data</h3>

<?php
$prefix = xarDBGetSystemTablePrefix();

// Get and set the database type
$dbtype  = xarCore_getSystemVar('DB.Type');
xarModSetVar('installer','dbtype',$dbtype);

// Set cached flag similar to install so that privileges are by-passed.
// If we don't do this, then xarSecurityCheck() will not allow us to 
// to finish the import.
xarVarSetCached('installer','installing',1);

if (isset($step)) {
    if ($step == 1 && !isset($startnum)) {
        if(!xarVarFetch('reset',     'isset', $reset,      NULL, XARVAR_NOT_REQUIRED)) {return;}
        if(!xarVarFetch('resetcat',  'isset', $resetcat,   NULL, XARVAR_NOT_REQUIRED)) {return;}
        if(!xarVarFetch('userimport', 'isset', $userimport, NULL, XARVAR_NOT_REQUIRED)) {return;}
        if(!xarVarFetch('storyimport','isset', $storyimport, NULL, XARVAR_NOT_REQUIRED)) {return;}
        if(!xarVarFetch('submissionimport','isset', $submissionimport, NULL, XARVAR_NOT_REQUIRED)) {return;}
        if(!xarVarFetch('commentimport','isset', $commentimport, NULL, XARVAR_NOT_REQUIRED)) {return;}
    } elseif ($step > 1 || isset($startnum)) {
        $reset = xarModGetVar('installer','reset');
        $resetcat = xarModGetVar('installer','resetcat');
        $userimport = xarModGetVar('installer','userimport');
        $storyimport = xarModGetVar('installer','storyimport');
        $submissionimport = xarModGetVar('installer','submissionimport');
        $commentimport = xarModGetVar('installer','commentimport');
    }
}

// Initialize database settings
$dbconn =& xarDBGetConn();
$tables =& xarDBGetTables();

// Set some table variables
$table_users = 'users';
$table_stories = 'stories';
$table_submissions = 'submissions';
$table_comments = 'comments';

// Count number of users
$query = 'SELECT COUNT(uid) FROM ' . $table_users;
$result =& $dbconn->Execute($query);
if (!$result) {
    die("Oops, count of " . $table_users . " failed : " . $dbconn->ErrorMsg());
} 
$usercount = $result->fields[0];
xarModSetVar('installer','usercount',$usercount);
$result->Close();

// Count number of stories
$query = 'SELECT COUNT(stoid) FROM ' . $table_stories;
$result =& $dbconn->Execute($query);
if (!$result) {
    die("Oops, count of " . $table_stories . " failed : " . $dbconn->ErrorMsg());
} 
$storycount = $result->fields[0];
xarModSetVar('installer','storycount',$storycount);
$result->Close();

// Count number of submissions (these are also stories)
$query = 'SELECT COUNT(subid) FROM ' . $table_submissions;
$result =& $dbconn->Execute($query);
if (!$result) {
    die("Oops, count of " . $table_submissions . " failed : " . $dbconn->ErrorMsg());
} 
$submissioncount = $result->fields[0];
xarModSetVar('installer','submissioncount',$submissioncount);
$result->Close();

// Count number of comments
$query = 'SELECT COUNT(cid) FROM ' . $table_comments;
$result =& $dbconn->Execute($query);
if (!$result) {
    die("Oops, count of " . $table_comments . " failed : " . $dbconn->ErrorMsg());
} 
$commentcount = $result->fields[0];
xarModSetVar('installer','commentcount',$commentcount);
$result->Close();


?>
    Requirement for use : The Slashcode data and the Xaraya data HAVE to be in the same database for this script to work and they HAVE to be using a different prefix.  We read the Slashcode data and use the Xaraya API to import the data into Xaraya.  In order to do this we must be reading from the same database.  Easiest solution is to copy your Slashcode data into the same database as your Xaraya installation.
    <p></p>
    <form method="POST" action="import_slashcode.php">
    <table border="0" cellpadding="4">
    <tr><td align="right">Reset corresponding Xaraya data ?</td><td>
    <input type="checkbox" name="reset" checked></td></tr>
    <tr><td align="right">Reset existing Xaraya categories ?</td><td>
    <input type="checkbox" name="resetcat" checked></td></tr>
    <tr><td align="right"><?php echo($usercount);?> users found in the database<br>Number of users to import at a time:</td><td>
    <input type="text" name="userimport" size="10" maxlength="10" value="1000"></td></tr>
    <tr><td align="right"><?php echo($storycount);?> stories found in the database<br>Number of stories to import at a time:</td><td>
    <input type="text" name="storyimport" size="10" maxlength="10" value="500"></td></tr>
    <tr><td align="right"><?php echo($submissioncount);?> submissions found in the database<br>Number of submissions to import at a time:</td><td>
    <input type="text" name="submissionimport" size="10" maxlength="10" value="500"></td></tr>
    <tr><td align="right"><?php echo($commentcount);?> comments found in the database<br>Number of comments to import at a time:</td><td>
    <input type="text" name="commentimport" size="10" maxlength="10" value="500"></td></tr>
    <tr><td colspan=2 align="middle">
    <input type="submit" value=" Import Data "></td></tr>
    </table>
    <input type="hidden" name="step" value="1">
    <input type="hidden" name="module" value="roles">
    </form>
    Recommended usage :<br /><ol>
    <li>Install Xaraya with the 'Community Site' option</li>
    <li>Initialize and activate the following modules :<ul>
    <li>categories</li>
    <li>comments</li>
    <li>hitcount</li>
    <li>ratings (optional)</li>
    <li>articles</li>
    <li>polls (if you want to import those)</li>
    </ul>
    [do not modify the default privileges, hooks etc. yet]
    </li>
    <li><b>Backup your database!</b></li>
    <li>Copy all of the import files found in ./tools/import/slashcode to your Xaraya html directory </li>
    <li>Run import_slashcode.php.  Leave both Reset options checked.</li>
    <li>Evaluate data.  If an error occurs or data is not correct, go back to step #1</li>
    <li>Delete the import_slashcode*.php files</li>
    <li>Crack open a cold beer</li>
</ol>

<?php
    if ($step == 1 && !isset($startnum)) {
        if (!isset($reset)) { $reset = 0; }
        xarModSetVar('installer','reset',$reset);
        if (!isset($resetcat)) { $resetcat = 0; }
        xarModSetVar('installer','resetcat',$resetcat);
        if (!isset($userimport)) {$userimport = 1000;}
        xarModSetVar('installer','userimport',$userimport);
        if (!isset($storyimport)) {$storyimport = 500;}
        xarModSetVar('installer','storyimport',$storyimport);
        if (!isset($submissionimport)) {$submissionimport = 500;}
        xarModSetVar('installer','submissionimport',$submissionimport);
        if (!isset($commentimport)) {$commentimport = 500;}
        xarModSetVar('installer','commentimport',$commentimport);
    }

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

    if (!isset($reset)) {
        $reset = 0;
    }
    if (!isset($resetcat)) {
        $resetcat = 0;
    }

    $importfiles = array(//1 => array('import_slashcode_users.php'),
                         //2 => array('import_slashcode_topicscat.php'),
                         //1 => array('import_slashcode_stories.php'),
                         //1 => array('import_slashcode_submissions.php'),
                         1 => array('import_slashcode_comments.php'),
                         //6 => array('import_slashcode_poll_desc.php',
                          //           'import_slashcode_poll_data.php',
                          //           'import_slashcode_pollcomments.php'),
// TODO: add the rest :-)
                         2 => array('import_slashcode_cleanup.php')
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
