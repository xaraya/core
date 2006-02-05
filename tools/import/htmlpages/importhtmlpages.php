<?php
/**
 * File: $Id: importhtmlpages.php,v 1.1 2003/06/21 15:59:19 jojodee Exp $
 *
 * Quick & dirty import of PN .71x HTMLpages module data into Xaraya test sites
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage import
 * @author jojodee <jojodee@athomeandabout.com>
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

<h3>Quick and dirty import of HTMLpages data from an existing PN .71+ site</h3>

<?php
$prefix = xarDBGetSystemTablePrefix();
if (isset($step)) {
    if ($step == 1 && !isset($startnum)) {
        if (!xarVarFetch('oldprefix', 'str:1:', $oldprefix, '')) return;
        if (!xarVarFetch('reset', 'int:0:1', $reset, 0)) return;
        if (!xarVarFetch('resetcat', 'int:0:1', $resetcat, 0)) return;
        if (!xarVarFetch('imgurl', 'str:1:', $imgurl, '')) return;
    } elseif ($step > 1 || isset($startnum)) {
        $oldprefix = xarModGetVar('installer','oldprefix');
        $reset = xarModGetVar('installer','reset');
        $resetcat = xarModGetVar('installer','resetcat');
        $imgurl = xarModGetVar('installer','imgurl');
    }
}

if (!isset($oldprefix) || $oldprefix == $prefix || !preg_match('/^[a-z0-9_-]+$/i',$oldprefix)) {
?>
    Requirement : you must be using the same database, but a different prefix...
    <p></p>
    <form method="POST" action="importhtmlpages.php">
    <table border="0" cellpadding="4">
    <tr><td align="right">Prefix used in your PN .71+ site</td><td>
    <input type="text" name="oldprefix" value="nuke"></td></tr>
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
    <p><strong>Warning : for PHP 4.2+, this script needs to be run with register_globals OFF (for now)...</strong></p>
    Script assumes:<br /><ol>
        <li>Xaraya installed (with the 'Community Site' option)</li>
        <li>categories installed and activated</li>
        <li>ratings (optional)</li>
        <li>articles installed and activated</li>
    </ul>
   [do not modify the default privileges, hooks etc. yet]
        </li>
       <li>copy the importhtmlpages.php file to your Xaraya html directory and run it. Adapt the prefix of your old PN site if necessary, and leave both Reset options checked.</li>
       <li>All your HTMLpages will be imported into Document type articles, under new category HTMLpages</li>
       <li>Thanks Mike ;-)</li>
   </ol>


<?php
} else {
    if ($step == 1 && !isset($startnum)) {
        xarModSetVar('installer','oldprefix',$oldprefix);
        if (!isset($reset)) { $reset = 0; }
        xarModSetVar('installer','reset',$reset);
        if (!isset($resetcat)) { $resetcat = 0; }
        xarModSetVar('installer','resetcat',$resetcat);
        if (!isset($imgurl)) { $imgurl = ''; }
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
    $tables =& xarDBGetTables();

    if (!isset($reset)) {
        $reset = 0;
    }
    if (!isset($resetcat)) {
        $resetcat = 0;
    }

    if ($step == 1) {
        echo "<strong>1. Create a HTMLPages category</strong><br/>\n";
        echo "Creating root for HTMLpages<br/>\n";
        $sections = xarModAPIFunc('categories', 'admin', 'create', array(
                             'name' => 'HTMLpages',
                             'description' => 'HTMLpages (.7x style)',
                             'parent_id' => 0));
        if ($reset) {
            $settings = unserialize(xarModGetVar('articles', 'settings.2'));
            $settings['number_of_categories'] = 1;
            $settings['cids'] = array($sections);
            $settings['defaultview'] = 'c' . $sections;
            xarModSetVar('articles', 'settings.2', serialize($settings));
            xarModSetVar('articles', 'number_of_categories.2', 1);
            xarModSetVar('articles', 'mastercids.2', $sections);
        }

    xarModSetVar('installer','sections',$sections);
    xarModSetVar('installer','sectionid',serialize($sectionid));
    echo '<a href="importhtmlpages.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="importhtmlpages.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br/>';
    }

    if ($step == 2) {
        $regid = xarModGetIDFromName('articles');
        $sections = xarModGetVar('installer','sections');
        $sectionid = unserialize(xarModGetVar('installer','sectionid'));
        echo "<strong>2. Importing HTMLpages</strong><br/>\n";
        $query = 'SELECT pn_pid, pn_title, pn_content
              FROM ' . $oldprefix . '_htmlpages
              ORDER BY pn_pid ASC';
        $result =& $dbconn->Execute($query);
        if (!$result) {
            die("Oops, select HTMLpages content failed : " . $dbconn->ErrorMsg());
        }
        if (xarModIsAvailable('hitcount') && xarModAPILoad('hitcount','admin')) {
            $docounter = 1;
        }
        while (!$result->EOF) {
            list($artid, $title, $content, $language) = $result->fields;
            $cids = array();
            if (isset($sectionid[$secid])) {
            $cids[] = $sectionid[$secid];
            }
            if (count($cids) == 0) {
                $cids[] = $sections;
            }
            $status = 2;
            $newaid = xarModAPIFunc('articles',
                                'admin',
                                'create',
                                array('title' => $title,
                                      'summary' => '',
                                      'body' => $content,
                                      'notes' => '',
                                      'status' => $status,
                                      'ptid' => 2,
                                      'pubdate' => 0,
                                      'authorid' => _XAR_ID_UNREGISTERED,
                                      'language' => $language,
                                      'cids' => $cids,
                                      'hits' => $counter
                                     )
                               );
            if (!isset($newaid)) {
                echo "Insert HTMLpages ($artid) $title failed : " . xarErrorRender('text') . "<br/>\n";
            } else {
                echo "Inserted HTMLpages ($artid) $title<br/>\n";
            }
            $result->MoveNext();
        }
        $result->Close();
        echo '<a href="importhtmlpages.php">Return to start</a>&nbsp;&nbsp;&nbsp;
            <a href="importhtmlpages.php?step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br/>';
        $dbconn->Execute('OPTIMIZE TABLE ' . $tables['articles']);
        $dbconn->Execute('OPTIMIZE TABLE ' . $tables['categories_linkage']);
        if (!empty($docounter)) {
            $dbconn->Execute('OPTIMIZE TABLE ' . $tables['hitcount']);
        }
    }

// TODO: add the rest :-)

    if ($step == 3) {
        echo "<strong>3. Cleaning up</strong><br/>\n";

    //xarModDelVar('installer','userobjectid');
        xarModDelVar('installer','oldprefix');
        xarModDelVar('installer','reset');
        xarModDelVar('installer','resetcat');
        xarModDelVar('installer','imgurl');
        xarModDelVar('installer','userid');
        xarModDelVar('installer','topics');
        xarModDelVar('installer','topicid');
        xarModDelVar('installer','categories');
        xarModDelVar('installer','catid');
        xarModDelVar('installer','sections');
        xarModDelVar('installer','sectionid');
        echo '<a href="importhtmlpages.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="index.php">Go check out your new HTML Pages documents</a><br/>';
    }
}

if (!isset($step)) {

    // catch the output
    $return = ob_get_contents();
    ob_end_clean();

    xarTplSetPageTitle(xarConfigGetVar('Site.Core.SiteName').' :: '.xarML('Import Site'));

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
