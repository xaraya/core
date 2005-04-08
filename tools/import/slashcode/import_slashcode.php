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

// Get max_execution_time from php.ini
$old_max_execution_time = ini_get('max_execution_time');
xarModSetVar('installer','old_max_execution_time',$old_max_execution_time);
}
?>

<h3>Begin your import of Slashcode data</h3>

<?php
$prefix = xarDBGetSystemTablePrefix();

// Get and set the database type
$dbtype = xarDBGetType();
$dbhost = xarDBGetHost();
$dbname = xarDBGetName();
$dbuser = '';
$dbpass = '';
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
        if(!xarVarFetch('discussionimport','isset', $discussionimport, NULL, XARVAR_NOT_REQUIRED)) {return;}
        if(!xarVarFetch('new_max_execution_time','isset', $new_max_execution_time, NULL, XARVAR_NOT_REQUIRED)) {return;}
    } elseif ($step > 1 || isset($startnum)) {
        $reset = xarModGetVar('installer','reset');
        $resetcat = xarModGetVar('installer','resetcat');
        $userimport = xarModGetVar('installer','userimport');
        $storyimport = xarModGetVar('installer','storyimport');
        $submissionimport = xarModGetVar('installer','submissionimport');
        $commentimport = xarModGetVar('installer','commentimport');
        $discussionimport = xarModGetVar('installer','discussionimport');
        $new_max_execution_time = xarModGetVar('installer','new_max_execution_time');
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
$table_discussions = 'discussions';

// Check the import database configuration
if (!isset($step)) {
    $importdb = array();
    if (!xarVarFetch('importdbtype', 'str', $importdb['databaseType'], NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!empty($importdb['databaseType'])) {
        // get the import database information
        if (!xarVarFetch('importdbhost', 'str', $importdb['databaseHost'], NULL, XARVAR_NOT_REQUIRED)) {return;}
        if (!xarVarFetch('importdbname', 'str', $importdb['databaseName'], NULL, XARVAR_NOT_REQUIRED)) {return;}
        if (!xarVarFetch('importdbuser', 'str', $importdb['userName'], NULL, XARVAR_NOT_REQUIRED)) {return;}
        if (!xarVarFetch('importdbpass', 'str', $importdb['password'], NULL, XARVAR_NOT_REQUIRED)) {return;}
        xarModSetVar('installer','importdb',serialize($importdb));
        xarModSetVar('installer','importdbtype',$dbtype);

    } elseif (isset($importdb['databaseType'])) {
        // reset the import database information
        xarModSetVar('installer','importdb','');
    }
}
$importdbserial = xarModGetVar('installer','importdb');
if (!empty($importdbserial)) {
    $importdb = unserialize($importdbserial);
    $dbimport =& xarDBNewConn($importdb);
    xarModSetVar('installer','importdbtype',$importdb['databaseType']);
} else {
    $dbimport =& $dbconn;
    xarModSetVar('installer','importdbtype',$dbtype);
}

if (empty($step)) {
    $showdbform = false;

    // Count number of users
    //$query = 'SELECT COUNT(uid) FROM ' . $table_users;
    // skip users with invalid password lengths
    $query = 'SELECT COUNT(uid) FROM ' . $table_users . ' WHERE LENGTH(passwd) = 32';
    $result =& $dbimport->Execute($query);
    if (!$result) {
        echo("Oops, count of " . $table_users . " failed : " . $dbimport->ErrorMsg() . '<br/>');
        xarErrorHandled();
        $usercount = 0;
        $showdbform = true;
    } else {
        $usercount = $result->fields[0];
        xarModSetVar('installer','usercount',$usercount);
        $result->Close();
    }

    $totalcount = $usercount;

    // Count number of stories
    $query = 'SELECT COUNT(stoid) FROM ' . $table_stories;
    $result =& $dbimport->Execute($query);
    if (!$result) {
        echo("Oops, count of " . $table_stories . " failed : " . $dbimport->ErrorMsg() . '<br/>');
        xarErrorHandled();
        $storycount = 0;
        $showdbform = true;
    } else {
        $storycount = $result->fields[0];
        xarModSetVar('installer','storycount',$storycount);
        $result->Close();
    }

    if ($totalcount < $storycount) {
        $totalcount = $storycount;
    }

    // Count number of submissions (these are also stories)
    $query = 'SELECT COUNT(subid) FROM ' . $table_submissions;
    $result =& $dbimport->Execute($query);
    if (!$result) {
        echo("Oops, count of " . $table_submissions . " failed : " . $dbimport->ErrorMsg() . '<br/>');
        xarErrorHandled();
        $submissioncount = 0;
        $showdbform = true;
    } else {
        $submissioncount = $result->fields[0];
        xarModSetVar('installer','submissioncount',$submissioncount);
        $result->Close();
    }

    if ($totalcount < $submissioncount) {
        $totalcount = $submissioncount;
    }

    // Count number of comments
    $query = 'SELECT COUNT(cid) FROM ' . $table_comments;
    $result =& $dbimport->Execute($query);
    if (!$result) {
        echo("Oops, count of " . $table_comments . " failed : " . $dbimport->ErrorMsg() . '<br/>');
        xarErrorHandled();
        $commentcount = 0;
        $showdbform = true;
    } else {
        $commentcount = $result->fields[0];
        xarModSetVar('installer','commentcount',$commentcount);
        $result->Close();
    }

    if ($totalcount < $commentcount) {
        $totalcount = $commentcount;
    }

    // Count number of discussions
    $query = 'SELECT COUNT(id) FROM ' . $table_discussions;
    $result =& $dbimport->Execute($query);
    if (!$result) {
        echo("Oops, count of " . $table_discussions . " failed : " . $dbimport->ErrorMsg() . '<br/>');
        xarErrorHandled();
        $discussioncount = 0;
        $showdbform = true;
    } else {
        $discussioncount = $result->fields[0];
        xarModSetVar('installer','discussioncount',$discussioncount);
        $result->Close();
    }

    if ($totalcount < $discussioncount) {
        $totalcount = $discussioncount;
    }

    $estimatedtime = ceil($totalcount / 100);
?>

    <h3>Recommended usage</h3>
    <ol>
        <li>Install Xaraya with the 'Community Site' option</li>
        <li>Initialize and activate the following modules :
        <ul>
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
    if ($showdbform) {
?>
    <form method="POST" action="import_slashcode.php">
    <h3>Slashcode database</h3>
    <p>Note: fill in this form if your Slashcode data is not in the same database as your Xaraya site</p>
    <table border="0" cellpadding="4">
    <tr>
        <td align="right">
            Database Type
        </td>
        <td>
            <select name="importdbtype" id="importdbtype">
                <option value="">same database as Xaraya</option>
                <option value="mysql">MySQL</option>
                <option value="oci8">Oracle</option>
                <option value="postgres">Postgres</option>
            </select>
        </td>
    </tr>
    <tr>
        <td align="right">
            Database Host
        </td>
        <td>
            <input type="text" name="importdbhost" id="importdbhost" value="<?php echo $dbhost; ?>" size="30" />
        </td>
    </tr>
    <tr>
        <td align="right">
            Database Name
        </td>
        <td>
            <input type="text" name="importdbname" id="importdbname" value="<?php echo $dbname; ?>" size="30" />
        </td>
    </tr>
    <tr>
        <td align="right">
            Username
        </td>
        <td>
            <input type="text" name="importdbuser" id="importdbuser" value="" size="30" />
        </td>
    </tr>
    <tr>
        <td align="right">
            Password
        </td>
        <td>
            <input type="password" name="importdbpass" id="importdbpass" value="" size="30" />
        </td>
    </tr>
    <tr>
        <td colspan=2 align="middle">
            <input type="submit" value=" Configure Database ">
        </td>
    </tr>
    </table>
    </form>

<?php
    } else {
    // only show this when we start
?>
    <form method="POST" action="import_slashcode.php">
        <input type="hidden" name="importdbtype" value="" />
        <input type="submit" value=" Reset Database Configuration ">
    </form>
    <p></p>
    <h3>Import settings</h3>
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
    <tr><td align="right"><?php echo($discussioncount);?> discussions found in the database<br>Number of discussion to import at a time:</td><td>
    <input type="text" name="discussionimport" size="10" maxlength="10" value="500"></td></tr>
    <tr><td align="right"><?php echo($estimatedtime);?> seconds estimated to execute import of largest recordset<br>Maximum execute time:</td><td>
    <input type="text" name="new_max_execution_time" size="10" maxlength="10" value="<?php echo($estimatedtime);?>"></td></tr>
    <tr><td colspan=2 align="middle">
    <tr><td colspan=2 align="middle">
    <input type="submit" value=" Import Data "></td></tr>
    </table>
    <input type="hidden" name="step" value="1">
    <input type="hidden" name="module" value="roles">
    </form>

<?php
    } // if ($showdbform)
} // if (empty($step))

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
        if (!isset($discussionimport)) {$discussionimport = 500;}
        xarModSetVar('installer','discussionimport',$discussionimport);
        if (!isset($new_max_execution_time)) {$new_max_execution_time = 300;}
        xarModSetVar('installer','new_max_execution_time',$new_max_execution_time);
        // Set max execution time
        ini_set('maximum_execution_time', $new_max_execution_time);
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

    $importfiles = array(1 => array('import_slashcode_users.php'),
                         2 => array('import_slashcode_topicscat.php'),
                         3 => array('import_slashcode_stories.php'),
                         4 => array('import_slashcode_submissions.php'),
                         5 => array('import_slashcode_poll_questions.php',
                                    'import_slashcode_poll_answers.php'),
                         6 => array('import_slashcode_discussions.php'),
                         7 => array('import_slashcode_comments.php'),
// TODO: add the rest :-)
                         8 => array('import_slashcode_cleanup.php')
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
