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
 * @author Carl P. Corliss <rabbitt@xaraya.com>
 * @author apakuni <apakuni@xaraya.com>
 * @author mikespub <mikespub@xaraya.com>
 */

    // initialize the Xaraya core
    include 'includes/xarCore.php';
    xarCoreInit(XARCORE_SYSTEM_ALL);

    // pre-fill the module name (if any) for hooks
    xarRequestGetInfo();

    if (!isset($step)) {
        // start the output buffer
        ob_start();
    }
    if (!isset($_POST['doit'])) {

?>
<h3>Quick and dirty import of test data from an existing MoveableType site</h3>
    Requirement: you <b>must</b> be using the same database.
    <br /><br />
    What this imports:<br />
    <pre style="font-family: Courier;">
        1. authors     -- authors --> roles
        2. blogs       -- blogs --> root categories
        3. categories  -- categories --> sub-categories of their owning blog
        4. entries     -- entries --> articles with user/category retention
        5. comments    -- comments --> comments with user/article retention

                          This script also tries to make a guess at who the author
                          might be for each comment - ie., if you have a role with the
                          username of 'joeyb' and realname of 'Joey Butta', and a comment
                          has the author name of either 'joeyb' or 'Joey Butta', then that
                          comment will be associated with that particular user.
    </pre>
    Recommended usage:<br />
    <ol>
        <li>install Xaraya with the 'Community Site' option</li><br />
        <li>initialize and activate the following modules :
            <ul>
                <li>categories</li>
                <li>comments</li>
                <li>articles</li>
            </ul>
            [do not modify the default privileges, hooks etc. yet]
        </li><br />
        <li>copy the import_mt.php file to your Xaraya html directory and run it.</li><br />
        <li>COPY <strong>modules/articles/xartemplates/user-summary-news.xd</strong><br />TO <strong>modules/articles/xartemplates/user-summary-blog.xd</strong></li><br />
        <li>COPY <strong>modules/articles/xartemplates/user-display-news.xd</strong><br />TO <strong>modules/articles/xartemplates/user-display-blog.xd</strong></li><br />
        <li>Have PHUN :)</li>
    </ol>
    <form method="POST" action="import_mt.php">
        <input type="submit" name="doit" value="Let's Do it!">
    </form>

    <strong style="color: red;">Note</strong>: This can take a while depending on the amount of data being
    imported - please be patient and <strong><em>please</em></strong> avoid clicking on the "Let's Do It!" button more than once!

<?php
    } else {
        list($dbconn) = xarDBGetConn();

        if (!xarModAPILoad('roles','admin')) {
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
        $tables =& xarDBGetTables();

        if (!isset($reset)) {
            $reset = 0;
        }
        if (!isset($resetcat)) {
            $resetcat = 0;
        }

        include_once('import_mt_mysql.php');

    }

    // catch the output
    $return = ob_get_contents();
    ob_end_clean();

    xarTplSetPageTitle(xarConfigGetVar('Site.Core.SiteName').' :: '.xarML('Import Site'));

    //xarTplSetThemeName('Xaraya_Classic');
    //xarTplSetPageTemplateName('admin');

    // render the page
    echo xarTpl_renderPage($return);

    // Close the session
    xarSession_close();

    //$dbconn->Close();

    flush();

    // Kill the debugger
    xarCore_disposeDebugger();

    // done
    exit;

?>
