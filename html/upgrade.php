<?php
/**
 * Core Upgrade File
 *
 * package upgrade
 * @copyright (C) 2002-2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Upgrade
 * @author mikespub <mikespub@xaraya.com>
*/

// Show all errors by default.
// This may be modified in xarCore.php, but gives us a good default.
error_reporting(E_ALL);

// initialize the Xaraya core
include 'includes/xarCore.php';
xarCoreInit(XARCORE_SYSTEM_ALL);
// Use the installer page template in Xaraya_Classic
xarTplSetThemeName('Xaraya_Classic');

if(!xarVarFetch('step','int', $step, NULL, XARVAR_DONT_SET)) {return;}

// pre-fill the module name (if any) for hooks
xarRequestGetInfo();
//Load Table Maintainance API
xarDBLoadTableMaintenanceAPI();

// The System.Core.VersionNum contains the currently stored version number
// this may be different from the define in xarCore.php
$xarProduct = xarConfigGetVar('System.Core.VersionId');
$xarVersion = xarConfigGetVar('System.Core.VersionNum');
$xarRelease = xarConfigGetVar('System.Core.VersionSub');

$title = xarML('Upgrade');

if (empty($step)) {
    $descr = xarML('Preparing to upgrade from previous #(1) Version #(2) (release #(3)) to #(4) version #(5)  (release #(6))',$xarProduct,$xarVersion,$xarRelease, XARCORE_VERSION_ID, XARCORE_VERSION_NUM, XARCORE_VERSION_SUB);
    // start the output buffer
    ob_start();
?>

<div class="xar-mod-head"><span class="xar-mod-title"><?php echo $title; ?></span></div>
<div class="xar-mod-body"><p><h3><?php echo $descr; ?></h3></p>
  <p>
    <xar:mlstring>
        Before you run the upgrade, make sure the existing site is working. If you try to upgrade
        a non-working site, unexpected results may occur.
    </xar:mlstring>
  </p>
  <div style="margin: auto;">
    <form method="POST" action="upgrade.php">
    <p><input type="submit" value="Upgrade Core Tables">

    <input type="hidden" name="step" value="1"></p>
    </form>
  </div>
</div>

<?php
} else {

    // Fini
    $in_process = xarML('Checking and Correcting');
    $complete = xarML('Upgrades Complete');

    // start the output buffer
    ob_start();
?>

<div class="xar-mod-head"><span class="xar-mod-title"><?php echo $title; ?></span></div>
<div class="xar-mod-body">
  <h2><?php echo $in_process; ?></h2>
  <div style="margin: auto;">
<?php
    $sprefix=xarDBGetSiteTablePrefix();

    // Now we can check whether people are logged in
    // TODO: allow the user to over-ride this message if need be - many problems
    // can be solved by running the upgrade more than once.
    if (XARCORE_VERSION_NUM == $xarVersion) {
        echo xarML('You have already upgraded to #(1). The upgrade script only needs to run once.', $xarVersion);
        echo '</div></div>';
        // catch the output
        //CatchOutput();
        //return;
    }

    // Check the installed security instances table for hard coded prefix bug and the bug fix bug :).
    echo "<h5>Checking Security Instances Table</h5>";
    $sprefix=xarDBGetSiteTablePrefix();

    echo "Table Prefix is : ".$sprefix."<br /><br />";
    echo "Checking hard coded table prefixes in security_instances table for categories, articles, ratings and hitcount modules.<br /><br />";
    $instancestable = $sprefix."_security_instances";
    $privilegestable = $sprefix."_privileges";
    $modulestable=$sprefix.'_modules';
    $categorytable=$sprefix.'_categories';
    $blockinstancetable=$sprefix.'_block_instances';
    $blocktypestable=$sprefix.'_block_types';
    $hitcounttable =$sprefix.'_hitcount';
    $ratingstable=$sprefix.'_ratings';

    // upgrades for the base module (since it is a core module, and they cannot be upgraded in the normal way)
    // - theme tags for JavaScript
    if (xarModIsAvailable('base')) {
        // Add theme tags that do not yet exist.
        // Leave the attributes open for now, until we know how it's going to work.
        $module_base_update_count = 0;

        // Include a JavaScript file in a page,
        $base_update_theme_tag = 'base-include-javascript';
        if (!xarTplGetTagObjectFromName($base_update_theme_tag)) {
            xarTplRegisterTag(
                'base', $base_update_theme_tag, array(),
                'base_javascriptapi_handlemodulejavascript'
            );
            $module_base_update_count += 1;
            echo "Base module: added theme tag '$base_update_theme_tag'.<br />";
        }
        // Render JavaScript in a page
        $base_update_theme_tag = 'base-render-javascript';
        if (!xarTplGetTagObjectFromName($base_update_theme_tag)) {
            xarTplRegisterTag(
                'base', $base_update_theme_tag, array(),
                'base_javascriptapi_handlerenderjavascript'
            );
            $module_base_update_count += 1;
            echo "Base module: added theme tag '$base_update_theme_tag'.<br />";
        }

        if ($module_base_update_count == 0) {
            echo "Base module does not require updating.<br />";
        }
    } else {
        echo "Base module not available - no upgrade carried out.<br />";
    } // endif modavailable('base')

    $dbconn =& xarDBGetConn();

    // replace DynamicData component 'Type' by 'Field'
    echo "Updating security instance for DynamicData.<br />";
    $query = "UPDATE $instancestable
              SET xar_component='Field'
              WHERE xar_module='dynamicdata' AND xar_component='Type'";
    $result =& $dbconn->Execute($query);

    echo "Updating privileges for DynamicData.<br />";
    $query = "UPDATE $privilegestable
              SET xar_component='Field'
              WHERE xar_module='dynamicdata' AND xar_component='Type'";
    $result =& $dbconn->Execute($query);



    //now check modules instances - only affected if their site prefix is other than 'xar'
    if ($sprefix == 'xar') { // check ratings, hitcount, articles and categories
        echo "Categories, Articles, Hitcount and Ratings security_instances do not require updating.<br />";
        echo "Updates are only required if your site prefix is not the default 'xar'.<br />";
    } else {
        //check categories instances - two bugs - hardcoded prefix bug and also instancetable2 column hardcoded prefix
        if (xarModIsAvailable('categories')) {
            $categoriesupdate=false;
            $categoriesinstances[]= array(array ('ccomponent'  => 'Category',
                                               'cheader'     => 'Category Name:',
                                               'cquery'      => 'SELECT DISTINCT xar_name FROM '.$categorytable.'',
                                               'ctable2'     => $categorytable),
                                        array ('ccomponent'  => 'Category',
                                               'cheader'     => 'Category ID:',
                                               'cquery'      => 'SELECT DISTINCT xar_cid FROM '.$categorytable.'',
                                               'ctable2'     => $categorytable));

            foreach($categoriesinstances as $categoriesinstance)
            {
                  foreach ($categoriesinstance as $instance) {
                      $dbconn =& xarDBGetConn();
                      $query = "SELECT xar_iid,xar_header,xar_query, xar_instancetable2
                                FROM $instancestable
                                WHERE xar_module= 'categories' AND xar_component = '{$instance['ccomponent']}' AND xar_header='{$instance['cheader']}'";
                      $result =&$dbconn->Execute($query);
                      list($iid, $header, $xarquery, $instancetable2) = $result->fields;
                      if (($instance['cquery'] != $xarquery) || ($instance['ctable2'] != $instancetable2)) {
                         echo "Attempting to update categories instance  with component ".$instance['ccomponent']. " and header ".$instance['cheader'];
                          $categoriesupdate=true;
                          $query="UPDATE $instancestable SET xar_query= '{$instance['cquery']}', xar_instancetable2 = '$instance[ctable2]'
                                  WHERE xar_module='categories' AND xar_component = '{$instance['ccomponent']}' AND xar_header= '{$instance['cheader']}'";
                          $result =& $dbconn->Execute($query);
                           if (!$result) {
                              echo "...update failed!</font><br/>\r\n";
                          } else {
                            echo "...done!</font><br/>\r\n";
                          }
                      }
                  }
            }//end foreach

            //now do the last one as a separate instance - to get it to work properly
            $categoryinstance ='SELECT DISTINCT instances.xar_title FROM '.$blockinstancetable.' as instances LEFT JOIN '.$blocktypestable.' as btypes ON  btypes.xar_id = instances.xar_type_id WHERE xar_module = \'categories\'';

            $dbconn =& xarDBGetConn();
                      $query = "SELECT xar_iid, xar_header, xar_query
                                FROM $instancestable
                                WHERE xar_module= 'categories' AND xar_component = 'Block' AND xar_header='Category Block Title:'";
                      $result =&$dbconn->Execute($query);

            list($iid, $header, $xarquery) = $result->fields;
            if ($categoryinstance != $xarquery)
            {
                   $categoriesupdate=true;
                   echo "Attempting to update categories instance  with component Block and header Category Block Title: ";

                   $query="UPDATE $instancestable SET xar_query= 'SELECT DISTINCT instances.xar_title FROM $blockinstancetable as instances LEFT JOIN $blocktypestable as btypes ON  btypes.xar_id = instances.xar_type_id WHERE xar_module = \'categories\''
                           WHERE xar_module='categories' AND xar_component = 'Block' AND xar_header= 'Category Block Title:'";
                   $result =& $dbconn->Execute($query);

                   if (!$result) {
                       echo "...update failed!</font><br/>\r\n";
                   } else {
                       echo "...done!</font><br/>\r\n";
                   }
            }
            if (!$categoriesupdate) {
              echo "Categories security_instance entries do not require updating.<br />";
            }
        } else {
          echo "Categories module not available - no checking of categories instances carried out.<br />";
        } // endif modavailable

        //check hitcount instances
        if (xarModIsAvailable('hitcount'))
        {
            $hitcountupdate=false;
            $hitcountinstances[]=array(array ('ccomponent'  => 'Item',
                                            'cheader'     => 'Module Name:',
                                            'cquery'      => 'SELECT DISTINCT '.$modulestable.'.xar_name FROM '.$hitcounttable.' LEFT JOIN '.$modulestable.' ON '.$hitcounttable.'.xar_moduleid = '.$modulestable.'.xar_regid'),
                                     array ('ccomponent'  => 'Item',
                                            'cheader'     => 'Item Type:',
                                            'cquery'      => 'SELECT DISTINCT xar_itemtype FROM '.$hitcounttable.''),
                                     array ('ccomponent'  => 'Item',
                                            'cheader'     => 'Item ID:',
                                            'cquery'      => 'SELECT DISTINCT xar_itemtype FROM '.$hitcounttable.''));

            foreach($hitcountinstances as $hitcountinstance){
                foreach ($hitcountinstance as $instance) {
                    $dbconn =& xarDBGetConn();
                    $query = "SELECT xar_iid, xar_header, xar_query
                            FROM $instancestable
                            WHERE xar_module= 'hitcount' AND xar_component = '{$instance['ccomponent']}' AND xar_header='{$instance['cheader']}'";
                    $result =&$dbconn->Execute($query);

                    list($iid, $header, $xarquery) = $result->fields;


                    if (($instance['cquery'])==($xarquery)) {
                        // nothing
                    } else {
                        $hitcountupdate=true;
                        echo "Attempting to update hitcount instance  with component ".$instance['ccomponent']. " and header ".$instance['cheader'];

                        $query="UPDATE $instancestable SET xar_query= '{$instance['cquery']}'
                              WHERE xar_module='hitcount' AND xar_component = '{$instance['ccomponent']}' AND xar_header= '{$instance['cheader']}'";
                        $result =& $dbconn->Execute($query);

                        if (!$result) {
                            echo "...update failed!</font><br/>\r\n";
                        } else {
                            echo "...done!</font><br/>\r\n";
                        }
                    }
                }
            }//end foreach
            if (!$hitcountupdate) {
                echo "Hit Count security_instance entries do not require updating.<br />";
            }
        } else {
            echo "Hit Count module not available - no checking of hit count instances carried out.<br />";
        } // endif modavailable

        //check rating instances
        if (xarModIsAvailable('ratings')) {
            $ratingsupdate=false;
            $ratinginstances[]=array(array ('ccomponent'  => 'Item',
                                          'cheader'     => 'Module Name:',
                                          'cquery'      => 'SELECT DISTINCT '.$modulestable.'.xar_name FROM '.$ratingstable.' LEFT JOIN '.$modulestable.' ON '.$ratingstable.'.xar_moduleid = '.$modulestable.'.xar_regid'),
                                   array ('ccomponent'  => 'Item',
                                          'cheader'     => 'Item Type:',
                                          'cquery'      => 'SELECT DISTINCT xar_itemtype FROM '.$ratingstable.''),
                                   array ('ccomponent'  => 'Item',
                                          'cheader'     => 'Item ID:',
                                          'cquery'      => 'SELECT DISTINCT xar_itemtype FROM '.$ratingstable.''),
                                   array ('ccomponent'  => 'Template',
                                          'cheader'     => 'Module Name:',
                                          'cquery'      => 'SELECT DISTINCT '.$modulestable.'.xar_name FROM '.$ratingstable.' LEFT JOIN '.$modulestable.' ON '.$ratingstable.'.xar_moduleid = '.$modulestable.'.xar_regid'),
                                   array ('ccomponent'  => 'Template',
                                          'cheader'     => 'Item Type:',
                                          'cquery'      => 'SELECT DISTINCT xar_itemtype FROM '.$ratingstable.''),
                                   array ('ccomponent'  => 'Template',
                                          'cheader'     => 'Item ID:',
                                          'cquery'      => 'SELECT DISTINCT xar_itemtype FROM '.$ratingstable.''));

              foreach($ratinginstances as $ratingsinstance){
                  foreach ($ratingsinstance as $instance) {

                      $dbconn =& xarDBGetConn();
                      $query = "SELECT xar_iid, xar_header, xar_query
                                FROM $instancestable
                                WHERE xar_module= 'ratings' AND xar_component = '{$instance['ccomponent']}' AND xar_header='{$instance['cheader']}'";
                      $result =&$dbconn->Execute($query);

                      list($iid, $header, $xarquery) = $result->fields;

                      if ($instance['cquery'] != $xarquery) {
                          $ratingsupdate = true;
                          echo "Attempting to update ratings instance  with component ".$instance['ccomponent']. " and header ".$instance['cheader'];

                           $query="UPDATE $instancestable SET xar_query= '{$instance['cquery']}'
                                  WHERE xar_module='ratings' AND xar_component = '{$instance['ccomponent']}' AND xar_header= '{$instance['cheader']}'";
                          $result =& $dbconn->Execute($query);
                          if (!$result) {
                              echo "...update failed!</font><br/>\r\n";
                           } else {
                              echo "...done!</font><br/>\r\n";
                          }
                      }
                  }//end foreach

              }//end foreach

            if (!$ratingsupdate) {
                echo "Ratings security_instance entries do not require updating.<br />";
            }//endif updatetrue

        } else {
            echo "Ratings module not available - no checking of ratings instances carried out.<br />";
        } // endif modavailable

        //check articles instances
        if (xarModIsAvailable('articles')) {
            $articlesupdate=false;
            $articlesinstance ='SELECT DISTINCT instances.xar_title FROM '.$blockinstancetable.' as instances LEFT JOIN '.$blocktypestable.' as btypes ON btypes.xar_id = instances.xar_type_id WHERE xar_module=\'articles\'';

            $dbconn =& xarDBGetConn();
                  $query = "SELECT xar_iid, xar_header, xar_query
                            FROM $instancestable
                            WHERE xar_module= 'articles' AND xar_component = 'Block' AND xar_header='Article Block Title:'";
                  $result =&$dbconn->Execute($query);

            list($iid, $header, $xarquery) = $result->fields;
            if ($articlesinstance != $xarquery) {
                   $articlesupdate=true;
                   echo "Attempting to update articles instance  with component Block and header Article Block Title";

                   $query="UPDATE $instancestable SET xar_query= 'SELECT DISTINCT instances.xar_title FROM $blockinstancetable as instances LEFT JOIN $blocktypestable as btypes ON btypes.xar_id = instances.xar_type_id WHERE xar_module=\'articles\''
                           WHERE xar_module='articles' AND xar_component = 'Block' AND xar_header= 'Article Block Title:'";
                   $result =& $dbconn->Execute($query);
                   if (!$result) {
                       echo "...update failed!</font><br/>\r\n";
                   } else {
                       echo "...done!</font><br/>\r\n";
                   }
            }
            if (!$articlesupdate) {
                echo "Articles security_instance entry does not require updating.<br />";
            }
        } else {
            echo "Articles module not available - no checking of articles instance carried out.<br />";
        } // endif modavailable
    }

    //check roles instances
    $rolesupdate=false;
    $rolesinstance ='SELECT DISTINCT xar_name FROM ' . xarDBGetSystemTablePrefix() . '_roles';
    $systemPrefix = xarDBGetSystemTablePrefix();
    $roleMembersTable    = $systemPrefix . '_rolemembers';
    $dbconn =& xarDBGetConn();

    // Do the Parent instance
    $query = "SELECT xar_iid, xar_header, xar_query
                FROM $instancestable
                WHERE xar_module= 'roles' AND xar_component = 'Relation' AND xar_header='Parent:'";
    $result =&$dbconn->Execute($query);

    list($iid, $header, $xarquery) = $result->fields;
    if ($rolesinstance != $xarquery) {
        $rolesupdate=true;
        echo "Attempting to update roles instance with component Relation and header Parent:.<br />";

        $instances = array(array('header' => 'Parent:',
                                 'query' => $rolesinstance,
                                 'limit' => 20));
        xarDefineInstance('roles','Relation',$instances,0,$roleMembersTable,'xar_uid','xar_parentid','Instances of the roles module, including multilevel nesting');
    }
    if (!$rolesupdate) {
       echo "Roles security_instance entry Relation/Parent does not require updating.<br />";
    }

    // Do the Child instance
    $query = "SELECT xar_iid, xar_header, xar_query
            FROM $instancestable
            WHERE xar_module= 'roles' AND xar_component = 'Relation' AND xar_header='Child:'";
    $result =&$dbconn->Execute($query);

    list($iid, $header, $xarquery) = $result->fields;
    if ($rolesinstance != $xarquery) {
        $rolesupdate=true;
        echo "Attempting to update roles instance with component Relation and header Child:.<br />";

        $instances = array(array('header' => 'Child:',
                                 'query' => $rolesinstance,
                                 'limit' => 20));
        xarDefineInstance('roles','Relation',$instances,0,$roleMembersTable,'xar_uid','xar_parentid','Instances of the roles module, including multilevel nesting');
    }
    if (!$rolesupdate) {
       echo "Roles security_instance entry Relation/Child does not require updating.<br />";
    }

    // Upgrade will check to make sure that upgrades in the past have worked, and if not, correct them now.
    $sitePrefix = xarDBGetSiteTablePrefix();
    echo "<h5>Checking Table Structure</h5>";
    $dbconn =& xarDBGetConn();
    // create and populate the security levels table
    $table_name['security_levels'] = $sitePrefix . '_security_levels';

    $upgrade['security_levels'] = xarModAPIFunc('installer',
                                                'admin',
                                                'CheckTableExists',
                                                array('table_name' => $table_name['security_levels']));
    if (!$upgrade['security_levels']) {
        echo "$table_name[security_levels] table does not exist, attempting to create... ";
        $leveltable = $table_name['security_levels'];
        $query = xarDBCreateTable($table_name['security_levels'],
                 array('xar_lid'  => array('type'       => 'integer',
                                          'null'        => false,
                                          'default'     => '0',
                                          'increment'   => true,
                                          'primary_key' => true),
                       'xar_level' => array('type'      => 'integer',
                                          'null'        => false,
                                          'default'     => '0'),
                       'xar_leveltext' => array('type'=> 'varchar',
                                          'size'        => 255,
                                          'null'        => false,
                                          'default'     => ''),
                       'xar_sdescription' => array('type'=> 'varchar',
                                          'size'        => 255,
                                          'null'        => false,
                                          'default'     => ''),
                       'xar_ldescription' => array('type'=> 'varchar',
                                          'size'        => 255,
                                          'null'        => false,
                                          'default'     => '')));
        $result = $dbconn->Execute($query);
        if (!$result){
            echo "failed</font><br/>\r\n";
        } else {
            echo "done!</font><br/>\r\n";
        }

        echo "Attempting to set index and fill $table_name[security_levels]... ";

        $sitePrefix = xarDBGetSiteTablePrefix();
        $index = array('name'      => 'i_'.$sitePrefix.'_security_levels_level',
                       'fields'    => array('xar_level'),
                       'unique'    => FALSE);
        $query = xarDBCreateIndex($leveltable,$index);
        $result = @$dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, -1, 'ACCESS_INVALID', 'Access Invalid', '')";
        $result =& $dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, 0, 'ACCESS_NONE', 'No Access', '')";
        $result =& $dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, 100, 'ACCESS_OVERVIEW', 'Overview Access', '')";
        $result =& $dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, 200, 'ACCESS_READ', 'Read Access', '')";
        $result =& $dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, 300, 'ACCESS_COMMENT', 'Comment Access', '')";
        $result =& $dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, 400, 'ACCESS_MODERATE', 'Moderate Access', '')";
        $result =& $dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, 500, 'ACCESS_EDIT', 'Edit Access', '')";
        $result =& $dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, 600, 'ACCESS_ADD', 'Add Access', '')";
        $result =& $dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, 700, 'ACCESS_DELETE', 'Delete Access', '')";
        $result =& $dbconn->Execute($query);

        $nextId = $dbconn->GenId($leveltable);
        $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
                  VALUES ($nextId, 800, 'ACCESS_ADMIN', 'Admin Access', '')";
        $result =& $dbconn->Execute($query);

        if (!$result){
            echo "failed</font><br/>\r\n";
        } else {
            echo "done!</font><br/>\r\n";
        }
    } else {
        echo "$table_name[security_levels] already exists, moving to next check. <br />";
    }

    // Drop the admin_wc table and the hooks for the admin panels.
    $table_name['admin_wc'] = $sitePrefix . '_admin_wc';

    $upgrade['waiting_content'] = xarModAPIFunc('installer',
                                                'admin',
                                                'CheckTableExists',
                                                array('table_name' => $table_name['admin_wc']));
    if ($upgrade['waiting_content']) {
        echo "$table_name[admin_wc] table still exists, attempting to drop... ";
            xarModRegisterHook('item', 'waitingcontent', 'GUI',
                               'articles', 'admin', 'waitingcontent');

            // Generate the SQL to drop the table using the API
            $query = xarDBDropTable($table_name['admin_wc']);
            $result =& $dbconn->Execute($query);
            if (!$result){
                echo "failed</font><br/>\r\n";
            } else {
                echo "done!</font><br/>\r\n";
            }
    } else {
        echo "$table_name[admin_wc] has been dropped previously, moving to next check. <br />";
    }

    // Drop the security_privsets table
    $table_name['security_privsets'] = $sitePrefix . '_security_privsets';

    $upgrade['security_privsets'] = xarModAPIFunc('installer',
                                                'admin',
                                                'CheckTableExists',
                                                array('table_name' => $table_name['security_privsets']));
    if ($upgrade['security_privsets']) {
        echo "$table_name[security_privsets] table still exists, attempting to drop... ";
        // Generate the SQL to drop the table using the API
        $query = xarDBDropTable($table_name['security_privsets']);
        $result =& $dbconn->Execute($query);
        if (!$result){
            echo "failed</font><br/>\r\n";
        } else {
            echo "done!</font><br/>\r\n";
        }
    } else {
        echo "$table_name[security_privsets] has been dropped previously, moving to next check. <br />";
    }

    // Dynamic Data Change to prop type.
    $dynproptable = xarDBGetSiteTablePrefix() . '_dynamic_properties';

    $query = "SELECT xar_prop_type
              FROM $dynproptable
              WHERE xar_prop_name='default'
              AND xar_prop_objectid=2";
    // Check for db errors
    $result =& $dbconn->Execute($query);

    list($prop_type) = $result->fields;
    $result->Close();

    if ($prop_type != 3){
        echo "Dynamic Data table 'default' property with objectid 2 is not set to property type 3, attempting to change... ";
        // Generate the SQL to drop the table using the API
        $query = "UPDATE $dynproptable
                     SET xar_prop_type=3
                   WHERE xar_prop_objectid=2
                     AND xar_prop_name='default'";
        // Check for db errors
        $result =& $dbconn->Execute($query);
        if (!$result){
            echo "failed</font><br/>\r\n";
        } else {
            echo "done!</font><br/>\r\n";
        }
    } else {
        echo "Dynamic Data table 'default' property with objectid 2 has correct property type of 3, moving to next check. <br />";
    }

    // ****************************
    // * Changes to blocks tables *
    // ****************************

    {
        // Bugs 1581/1586/1838: Update the blocks table definitions.
        // Use the data dictionary to do the checking and altering.
        echo "<h5>Checking Block Table Definitions</h5>";
        $dbconn =& xarDBGetConn();
        $datadict =& xarDBNewDataDict($dbconn, 'CREATE');

        // Upgrade the xar_block_instances table.
        $blockinstancestable = xarDBGetSiteTablePrefix() . '_block_instances';
        // Get column definitions for block instances table.
        $columns = $datadict->getColumns($blockinstancestable);
        // Do we have a xar_name column?
        $blocks_column_found = false;
        foreach($columns as $column) {
            if ($column->name == 'xar_name') {
                $blocks_column_found = true;
                break;
            }
        }
        // Upgrade the table (xar_block_instances) if the name column is not found.
        if (!$blocks_column_found) {
            // Create the column.
            $result = $datadict->addColumn($blockinstancestable, 'xar_name C(100) Null');
            // Update the name column with unique values.
            $query = "UPDATE $blockinstancestable"
                . " SET xar_name = " . $dbconn->Concat("'block_'", 'xar_id')
                . " WHERE xar_name IS NULL";
            $dbconn->Execute($query);
            // Now make it mandatory, and add a unique index.
            $result = $datadict->alterColumn($blockinstancestable, 'xar_name C(100) NotNull');
            $result = $datadict->createIndex(
                'i_'.xarDBGetSiteTablePrefix().'_block_instances_u2',
                $blockinstancestable,
                'xar_name',
                array('UNIQUE')
            );
            echo "Added column xar_name to table $blockinstancestable<br/>";
        } else {
            echo "Table $blockinstancestable is up-to-date<br/>";
        }

        // Upgrade the xar_block_group_instances table.
        $blockgroupinstancestable = xarDBGetSiteTablePrefix() . '_block_group_instances';
        // Get column definitions for block instances table.
        $columns = $datadict->getColumns($blockgroupinstancestable);
        // Do we have a xar_template column?
        $blocks_column_found = false;
        foreach($columns as $column) {
            if ($column->name == 'xar_template') {
                $blocks_column_found = true;
                break;
            }
        }
        if (!$blocks_column_found) {
            // Create the column.
            $result = $datadict->addColumn($blockgroupinstancestable, 'xar_template C(100) Null');
            echo "Added column xar_template to table $blockgroupinstancestable<br/>";
        } else {
            echo "Table $blockgroupinstancestable is up-to-date<br/>";
        }

        // Upgrade the xar_block_types table.
        $blocktypestable = xarDBGetSiteTablePrefix() . '_block_types';
        // Get column definitions for block instances table.
        $columns = $datadict->getColumns($blocktypestable);

        // Do we have a xar_template column?
        $blocks_column_found = false;
        foreach($columns as $column) {
            if ($column->name == 'xar_info') {
                $blocks_column_found = true;
                break;
            }
        }

        if (!$blocks_column_found) {
            // Create the column.
            $result = $datadict->addColumn($blocktypestable, 'xar_info X(2000) Null');
            echo "Added column xar_info to table $blocktypestable<br/>";
        } else {
            echo "Table $blocktypestable already has a xar_info column<br/>";
        }

        // Ensure the module and type columns are the correct length.
        $data = 'xar_type C(64) NotNull DEFAULT \'\',
        xar_module C(64) NotNull DEFAULT \'\'';
        $result = $datadict->changeTable($blocktypestable, $data);
        echo "Table $blocktypestable xar_module and xar_type columns are up-to-date<br/>";

        // Drop index i_xar_block_types and create unique compound index
        // i_xar_block_types2 on xar_module and xar_type.
        $indexes = $datadict->getIndexes($blocktypestable);
        $indexname = 'i_' . xarDBGetSiteTablePrefix() . '_block_types';
        if (isset($indexes[$indexname])) {
            $result = $datadict->dropIndex($indexname, $blocktypestable);
            echo "Dropped index $indexname from table $blocktypestable<br/>";
        }
        $indexname .= '2';
        if (!isset($indexes[$indexname])) {
            $result = $datadict->createIndex($indexname, $blocktypestable, 'xar_module,xar_type', array('UNIQUE'));
            echo "Created unique index $indexname on table $blocktypestable<br/>";
        }
    }

    // Add the syndicate block type and syndicate block for RSS display.
    echo "<h5>Checking Installed Blocks</h5>";

    $upgrade['syndicate'] = xarModAPIFunc(
        'blocks', 'admin', 'block_type_exists',
        array(
            'modName'      => 'themes',
            'blockType'    => 'syndicate'
        )
    );
    if ($upgrade['syndicate']) {
        echo "Syndicate block exists, attempting to remove... ";
        $blockGroupsTable = xarDBGetSiteTablePrefix() . '_block_groups';
        // Register blocks
        if (!xarModAPIFunc('blocks',
                           'admin',
                           'unregister_block_type',
                           array('modName'  => 'themes',
                                 'blockType'=> 'syndicate'))) return;

        $query = "SELECT    xar_id as id
                  FROM      $blockGroupsTable
                  WHERE     xar_name = 'syndicate'";
        // Check for db errors
        $result =& $dbconn->Execute($query);
        if (!$result) return;

        // Freak if we don't get one and only one result
        if ($result->getRecordCount() != 1) {
            throw new BadParameterException(null,"Group 'syndicate' not found.");
        }
        list ($syndicateBlockGroup) = $result->fields;
        $result = xarModAPIFunc('blocks', 'admin', 'delete_group', array('gid' => $syndicateBlockGroup));

        if (!$result){
            echo "failed</font><br/>\r\n";
        } else {
            echo "done!</font><br/>\r\n";
        }
    } else {
        echo "Syndicate block type does not exist, moving to next check. <br />";
    }

    // Set any empty modvars.
    echo "<h5>Checking Module and Config Variables</h5>";
    /* Bug 2204 - the mod var roles - admin is more than likely set in 99.9 percent installs
                  since it was introduced around the beginning of 2004. Let's check it's set,
                  and use that, else check for a new name. If the new name in that rare case
                  is not Admin, then we'll have to display message to check and set as such first.
    */
    $realadmin = xarModGetVar('roles','admin');

    if (!isset($realadmin) || empty($realadmin)) {
        $admin = xarUFindRole('Admin');
        if (!isset($admin)) $admin = xarFindRole('Admin');
        if (!isset($admin)) {
            echo "<div><h2 style=\"color:red; font-weigh:bold;\">WARNING!</h2>Your installation has a missing roles variable.<br />";
            echo "Please change your administrator username to 'Admin' and re-run upgrade.php<br />
                  You can change it back once your site is upgraded.<br />";

            echo "<br /><br />REMEMBER! Don't forget to re-run upgrade.php<br /><br />";
            echo "</div>";
            CatchOutput();
            return;
        }
    } else {

        $thisadmin= xarUserGetVar('uname', $realadmin);
         $admin = xarUFindRole($thisadmin);
    }


    $role = xarFindRole('Everybody');

    /* Bug 2204 - this var is not reliable for admin name
       if (!isset($admin)) $admin = xarFindRole(xarModGetVar('mail','adminname'));
    */
    $modvars[] = array(array('name'    =>  'hidecore',
                             'module'  =>  'themes',
                             'set'     =>  0),
                       array('name'    =>  'selstyle',
                             'module'  =>  'themes',
                             'set'     =>  'plain'),
                       array('name'    =>  'rssxml',
                             'module'  =>  'themes',
                             'set'     =>  '<?xml version="1.0" encoding="utf-8"?>'),
                       array('name'    =>  'selfilter',
                             'module'  =>  'themes',
                             'set'     =>  'XARMOD_STATE_ANY'),
                       array('name'    =>  'selsort',
                             'module'  =>  'themes',
                             'set'     =>  'namedesc'),
                       array('name'    =>  'SiteTitleSeparator',
                             'module'  =>  'themes',
                             'set'     =>  ' :: '),
                       array('name'    =>  'SiteTitleOrder',
                             'module'  =>  'themes',
                             'set'     =>  'default'),
                       array('name'    =>  'SiteFooter',
                             'module'  =>  'themes',
                             'set'     =>  '<a href="http://www.xaraya.com"><img src="modules/base/xarimages/xaraya.gif" alt="Powered by Xaraya" class="xar-noborder" /></a>'),
                       array('name'    =>  'everybody',
                             'module'  =>  'roles',
                             'set'     =>  $role->getID()),
                       array('name'    =>  'allowregistration',
                             'module'  =>  'roles',
                             'set'     =>  1),
                       array('name'    =>  'ShowPHPCommentBlockInTemplates',
                             'module'  =>  'themes',
                             'set'     =>  0),
                       array('name'    =>  'ShowTemplates',
                             'module'  =>  'themes',
                             'set'     =>  0),
                       array('name'    =>  'CollapsedBranches',
                             'module'  =>  'comments',
                             'set'     =>  serialize(array())),
                       array('name'    =>  'expertlist',
                             'module'  =>  'modules',
                             'set'     =>  0),
                       array('name'    =>  'lockdata',
                             'module'  =>  'roles',
                             'set'     =>  serialize(array('roles' => array( array('uid' => 4,
                                                  'name' => 'Administrators',
                                                  'notify' => TRUE)
                                           ),
                                          'message' => '',
                                          'locked' => 0,
                                          'notifymsg' => ''))),
                       array('name'    =>  'askwelcomeemail',
                             'module'  =>  'roles',
                             'set'     =>  1),
                       array('name'    =>  'askvalidationemail',
                             'module'  =>  'roles',
                             'set'     =>  1),
                       array('name'    =>  'askdeactivationemail',
                             'module'  =>  'roles',
                             'set'     =>  1),
                       array('name'    =>  'askpendingemail',
                             'module'  =>  'roles',
                             'set'     =>  1),
                       array('name'    =>  'askpasswordemail',
                             'module'  =>  'roles',
                             'set'     =>  1),
                       array('name'    =>  'admin',
                             'module'  =>  'roles',
                             'set'     =>  $admin->getID()),
                       array('name'    =>  'uniqueemail',
                             'module'  =>  'roles',
                             'set'     =>  true),
                       array('name'    =>  'rolesdisplay',
                             'module'  =>  'roles',
                             'set'     =>  'tabbed'),
                       array('name'    =>  'showrealms',
                             'module'  =>  'privileges',
                             'set'     =>  0),
                       array('name'    =>  'inheritdeny',
                             'module'  =>  'privileges',
                             'set'     =>  true),
                       array('name'    =>  'tester',
                             'module'  =>  'privileges',
                             'set'     =>  0),
                       array('name'    =>  'test',
                             'module'  =>  'privileges',
                             'set'     =>  false),
                       array('name'    =>  'testdeny',
                             'module'  =>  'privileges',
                             'set'     =>  false),
                       array('name'    =>  'testmask',
                             'module'  =>  'privileges',
                             'set'     =>  'All'),
                       array('name'    =>  'realmvalue',
                             'module'  =>  'privileges',
                             'set'     =>  'none'),
                       array('name'    =>  'realmcomparison',
                             'module'  =>  'privileges',
                             'set'     =>  'exact'),
                       array('name'    =>  'suppresssending',
                             'module'  =>  'mail',
                             'set'     =>  'false'),
                       array('name'    =>  'redirectsending',
                             'module'  =>  'mail',
                             'set'     =>  'exact'),
                       array('name'    =>  'redirectaddress',
                             'module'  =>  'mail',
                             'set'     =>  ''),
                          );

    foreach($modvars as $modvar){
        foreach($modvar as $var){
            $currentvar = xarModGetVar("$var[module]", "$var[name]");
            if (isset($currentvar)){
                if (isset($var['override'])) {
                    xarModSetVar($var['module'], $var['name'], $var['set']);
                    echo "$var[module] -> $var[name] has been overridden, proceeding to next check<br />";
                }
                else echo "$var[module] -> $var[name] is set, proceeding to next check<br />";
            } else {
                xarModSetVar($var['module'], $var['name'], $var['set']);
                echo "$var[module] -> $var[name] empty, attempting to set.... done!<br />";
            }
        }
    }

// TODO: save modified email templates from module variables to var/messages !

    // Delete any empty modvars.
    $delmodvars[] = array(array('name'    =>  'showtacs',
                                'module'  =>  'roles'),
                          array('name'    =>  'confirmationtitle',
                                'module'  =>  'roles'),
                          array('name'    =>  'confirmationemail',
                                'module'  =>  'roles'),
                          array('name'    =>  'remindertitle',
                                'module'  =>  'roles'),
                          array('name'    =>  'reminderemail',
                                'module'  =>  'roles'),
                          array('name'    =>  'validationtitle',
                                'module'  =>  'roles'),
                          array('name'    =>  'validationemail',
                                'module'  =>  'roles'),
                          array('name'    =>  'deactivationtitle',
                                'module'  =>  'roles'),
                          array('name'    =>  'deactivationemail',
                                'module'  =>  'roles'),
                          array('name'    =>  'pendingtitle',
                                'module'  =>  'roles'),
                          array('name'    =>  'pendingemail',
                                'module'  =>  'roles'),
                          array('name'    =>  'passwordtitle',
                                'module'  =>  'roles'),
                          array('name'    =>  'passwordemail',
                                'module'  =>  'roles'),
                         );

    foreach($delmodvars as $delmodvar){
        foreach($delmodvar as $var){
            $currentvar = xarModGetVar("$var[module]", "$var[name]");
            if (!isset($currentvar)){
                echo "$var[module] -> $var[name] is deleted, proceeding to next check<br />";
            } else {
                xarModDelVar($var['module'], $var['name']);
                echo "$var[module] -> $var[name] has value, attempting to delete.... done!<br />";
            }
        }
    }

    // Set Config Vars
    $roleanon = xarFindRole('Anonymous');
    $configvars[] = array(array('name'    =>  'Site.User.AnonymousUID',
                                'set'     =>  $roleanon->getID()),
                          array('name'    =>  'System.Core.VersionNum',
                                'set'     =>  XARCORE_VERSION_NUM));

    foreach($configvars as $configvar){
        foreach($configvar as $var){
            $currentvar = xarConfigGetVar("$var[name]");
            if ($currentvar == $var['set']){
                echo "$var[name] is set, proceeding to next check<br />";
            } else {
                xarConfigSetVar($var['name'], $var['set']);
                echo "$var[name] incorrect, attempting to set.... done!<br />";
            }
        }
    }

    $timezone = xarConfigGetVar('Site.Core.TimeZone');
    if (!isset($timezone) || substr($timezone,0,2) == 'US') {
        xarConfigSetVar('Site.Core.TimeZone', '');
        echo "Site.Core.TimeZone incorrect, attempting to set.... done!<br />";
    }
    $offset = xarConfigGetVar('Site.MLS.DefaultTimeOffset');
    if (!isset($offset)) {
        xarConfigSetVar('Site.MLS.DefaultTimeOffset', 0);
        echo "Site.MLS.DefaultTimeOffset incorrect, attempting to set.... done!<br />";
    }
    $cookiename = xarConfigGetVar('Site.Session.CookieName');
    if (!isset($cookiename)) {
        xarConfigSetVar('Site.Session.CookieName', '');
        echo "Site.Session.CookieName incorrect, attempting to set.... done!<br />";
    }
    $cookiepath = xarConfigGetVar('Site.Session.CookiePath');
    if (!isset($cookiepath)) {
        xarConfigSetVar('Site.Session.CookiePath', '');
        echo "Site.Session.CookiePath incorrect, attempting to set.... done!<br />";
    }
    $cookiedomain = xarConfigGetVar('Site.Session.CookieDomain');
    if (!isset($cookiedomain)) {
        xarConfigSetVar('Site.Session.CookieDomain', '');
        echo "Site.Session.CookieDomain incorrect, attempting to set.... done!<br />";
    }
    $referercheck = xarConfigGetVar('Site.Session.RefererCheck');
    if (!isset($referercheck)) {
        xarConfigSetVar('Site.Session.RefererCheck', '');
        echo "Site.Session.RefererCheck incorrect, attempting to set.... done!<br />";
    }

    // Check the installed roles
    echo "<h5>Checking Role Structure</h5>";

    $upgrade['myself'] = xarModAPIFunc('roles',
                                       'user',
                                       'get',
                                       array('uname' => 'myself'));
    if (!$upgrade['myself']) {
        echo "Myself role does not exist, attempting to create... ";
        //This creates the new Myself role and makes it a child of Everybody
        $result = xarMakeUser('Myself','myself','myself@xaraya.com','password');
        $result .= xarMakeRoleMemberByName('Myself','Everybody');
        if (!$result){
            echo "failed</font><br/>\r\n";
        } else {
            echo "done!</font><br/>\r\n";
        }
    } else {
        echo "Myself role has been created previously, moving to next check. <br />";
    }

    $upgrade['roles_masks'] = xarMaskExists('AttachRole',$module='roles');
    if (!$upgrade['roles_masks']) {
        echo "AttachRole, RemoveRole masks do not exist, attempting to create... done! <br />";
        xarRegisterMask('AttachRole','All','roles','Relation','All','ACCESS_ADD');
        xarRegisterMask('RemoveRole','All','roles','Relation','All','ACCESS_DELETE');
    } else {
        echo "AttachRole, RemoveRole masks have been created previously, moving to next check. <br />";
    }

    // Check the installed privs and masks.
    echo "<h5>Checking Privilege Structure</h5>";

    $upgrade['article_masks'] = xarMaskExists('ReadArticlesBlock',$module='articles');
    if (!$upgrade['article_masks']) {
        echo "Articles Masks do not exist, attempting to create... done! <br />";
            // Remove Masks and Instances
            xarRemoveMasks('articles');
            xarRemoveInstances('articles');
            $instances = array(
                               array('header' => 'external', // this keyword indicates an external "wizard"
                                     'query'  => xarModURL('articles', 'admin', 'privileges'),
                                     'limit'  => 0
                                    )
                            );
            xarDefineInstance('articles', 'Article', $instances);
            $xartable =& xarDBGetTables();
            $query = "SELECT DISTINCT instances.xar_title FROM $xartable[block_instances] as instances LEFT JOIN $xartable[block_types] as btypes ON btypes.xar_id = instances.xar_type_id WHERE xar_module = 'articles'";
            $instances = array(
                                array('header' => 'Article Block Title:',
                                        'query' => $query,
                                        'limit' => 20
                                    )
                            );
            xarDefineInstance('articles','Block',$instances);

            xarRegisterMask('ViewArticles','All','articles','Article','All','ACCESS_OVERVIEW');
            xarRegisterMask('ReadArticles','All','articles','Article','All','ACCESS_READ');
            xarRegisterMask('SubmitArticles','All','articles','Article','All','ACCESS_COMMENT');
            xarRegisterMask('EditArticles','All','articles','Article','All','ACCESS_EDIT');
            xarRegisterMask('DeleteArticles','All','articles','Article','All','ACCESS_DELETE');
            xarRegisterMask('AdminArticles','All','articles','Article','All','ACCESS_ADMIN');
            xarRegisterMask('ReadArticlesBlock','All','articles','Block','All','ACCESS_READ');
    } else {
        echo "Articles Masks have been created previously, moving to next check. <br />";
    }

    $upgrade['category_masks'] = xarMaskExists('ViewCategoryLink',$module='categories');
    if (!$upgrade['category_masks']) {
        echo "Category Masks do not exist, attempting to create... done! <br />";
            // Remove Masks and Instances
        $instances = array(
                           array('header' => 'external', // this keyword indicates an external "wizard"
                                 'query'  => xarModURL('categories', 'admin', 'privileges'),
                                 'limit'  => 0
                                )
                          );
        xarDefineInstance('categories', 'Link', $instances);
        xarRegisterMask('ViewCategoryLink','All','categories','Link','All:All:All:All','ACCESS_OVERVIEW');
        xarRegisterMask('SubmitCategoryLink','All','categories','Link','All:All:All:All','ACCESS_COMMENT');
        xarRegisterMask('EditCategoryLink','All','categories','Link','All:All:All:All','ACCESS_EDIT');
        xarRegisterMask('DeleteCategoryLink','All','categories','Link','All:All:All:All','ACCESS_DELETE');
        xarRegisterMask('AdminCategories','All','categories','Category','All:All','ACCESS_ADMIN');
    } else {
        echo "Category Masks have been created previously, moving to next check. <br />";
    }

    $upgrade['priv_masks'] = xarMaskExists('AssignPrivilege',$module='privileges');
    if (!$upgrade['priv_masks']) {
        echo "Some Privileges Masks do not exist, attempting to create... done! <br />";

        // create a couple of new masks
        xarRegisterMask('ViewPanel','All','adminpanels','All','All','ACCESS_OVERVIEW');
        xarRegisterMask('AssignPrivilege','All','privileges','All','All','ACCESS_ADD');
        xarRegisterMask('DeassignPrivilege','All','privileges','All','All','ACCESS_DELETE');
    } else {
        echo "Privileges Masks have been created previously, moving to next check. <br />";
    }

    $upgrade['priv_masks'] = xarMaskExists('pnLegacyMask',$module='All');
    if (!$upgrade['priv_masks']) {
        echo "pnLegacy Masks do not exist, attempting to create... done! <br />";

        // create a couple of new masks
        xarRegisterMask('pnLegacyMask','All','All','All','All','ACCESS_NONE');
    } else {
        echo "pnLegacy Masks have been created previously, moving to next check. <br />";
    }

    $upgrade['priv_masks'] = xarMaskExists('ViewPrivileges','privileges','Realm');
    if (!$upgrade['priv_masks']) {
        echo "Privileges realm Masks do not exist, attempting to create... done! <br />";

        // create a couple of new masks
        xarRegisterMask('ViewPrivileges','All','privileges','Realm','All','ACCESS_OVERVIEW');
        xarRegisterMask('ReadPrivilege','All','privileges','Realm','All','ACCESS_READ');
        xarRegisterMask('EditPrivilege','All','privileges','Realm','All','ACCESS_EDIT');
        xarRegisterMask('AddPrivilegem','All','privileges','Realm','All','ACCESS_ADD');
        xarRegisterMask('DeletePrivilege','All','privileges','Realm','All','ACCESS_DELETE');
    } else {
        echo "Privileges realm masks have been created previously, moving to next check. <br />";
    }

    $upgrade['priv_locks'] = xarPrivExists('GeneralLock');
    if (!$upgrade['priv_locks']) {
        echo "Privileges Locks do not exist, attempting to create... done! <br />";

        // This creates the new lock privileges and assigns them to the relevant roles
        xarRegisterPrivilege('GeneralLock','All','empty','All','All','ACCESS_NONE',xarML('A container privilege for denying access to certain roles'));
        xarRegisterPrivilege('LockMyself','All','roles','Roles','Myself','ACCESS_NONE',xarML('Deny access to Myself role'));
        xarRegisterPrivilege('LockEverybody','All','roles','Roles','Everybody','ACCESS_NONE',xarML('Deny access to Everybody role'));
        xarRegisterPrivilege('LockAnonymous','All','roles','Roles','Anonymous','ACCESS_NONE',xarML('Deny access to Anonymous role'));
        xarRegisterPrivilege('LockAdministrators','All','roles','Roles','Administrators','ACCESS_NONE',xarML('Deny access to Administrators role'));
        xarRegisterPrivilege('LockAdministration','All','privileges','Privileges','Administration','ACCESS_NONE',xarML('Deny access to Administration privilege'));
        xarRegisterPrivilege('LockGeneralLock','All','privileges','Privileges','GeneralLock','ACCESS_NONE',xarML('Deny access to GeneralLock privilege'));
        xarMakePrivilegeRoot('GeneralLock');
        xarMakePrivilegeMember('LockMyself','GeneralLock');
        xarMakePrivilegeMember('LockEverybody','GeneralLock');
        xarMakePrivilegeMember('LockAnonymous','GeneralLock');
        xarMakePrivilegeMember('LockAdministrators','GeneralLock');
        xarMakePrivilegeMember('LockAdministration','GeneralLock');
        xarMakePrivilegeMember('LockGeneralLock','GeneralLock');
        xarAssignPrivilege('Administration','Administrators');
        xarAssignPrivilege('GeneralLock','Everybody');
        xarAssignPrivilege('GeneralLock','Administrators');
        xarAssignPrivilege('GeneralLock','Users');

    } else {
        echo "Privileges Locks have been created previously, moving to next check. <br />";
    }

    $upgrade['priv_masks'] = xarMaskExists('AdminPrivilege',$module='privileges');
    if (!$upgrade['priv_masks']) {
        echo "Some Privileges Masks do not exist, attempting to create... done! <br />";

        // create a couple of new masks
        xarRegisterMask('AdminPrivilege','All','privileges','All','All','ACCESS_ADMIN');
    } else {
        echo "0.9.11 Privileges Masks have been created previously, moving to next check. <br />";
    }

    //Move this mask from privileges module
    xarUnregisterMask('AssignRole');

    // Check the installed privs and masks.
    echo "<h5>Checking Time / Date Structure</h5>";

    include 'includes/xarDate.php';
    $dbconn =& xarDBGetConn();
    $sitePrefix = xarDBGetSiteTablePrefix();
    $rolestable = $sitePrefix . '_roles';

    $query = " SELECT xar_uid, xar_date_reg FROM $rolestable";
    $result = &$dbconn->Execute($query);
    if (!$result) return;

    while (!$result->EOF) {
        list($uid,$datereg) = $result->fields;
        $thisdate = new xarDate();
        if(!is_numeric($datereg)) {
            $thisdate->DBtoTS($datereg);
            $datereg = $thisdate->getTimestamp();
            $query = "UPDATE $rolestable SET xar_date_reg = $datereg WHERE xar_uid = $uid";
            if(!$dbconn->Execute($query)) return;
        }
        $result->MoveNext();
    }

    echo "Time / Date structure verified in Roles. <br /> ";

    // Check the installed privs and masks.
    echo "<h5>Update Xaraya Installer theme name</h5>";
    $dbconn =& xarDBGetConn();
    $sitePrefix = xarDBGetSiteTablePrefix();
    $themestable = $sitePrefix . '_themes';
    $query = "SELECT xar_id FROM $themestable WHERE xar_name = 'Xaraya Installer'";
    $result =& $dbconn->Execute($query);
    if ($result->EOF){
        echo "Theme name update not required.<br/>\r\n";
    } else {
        $query2 = "UPDATE $themestable SET xar_name = 'Xaraya_Installer' WHERE xar_name = 'Xaraya Installer'";
        // Check for db errors
        $result2 =& $dbconn->Execute($query2);
        if (!$result2){
            echo "Theme name update failed<br/>\r\n";
        } else {
            echo "Theme name updated.<br/>\r\n";
        }
    }

    // Bug 1716 module states table
    {
        $module_states_table = $sitePrefix . '_module_states';
        echo "<h5>Upgrade $module_states_table table</h5>";

        // TODO: use transactions to ensure atomicity?
        // The changes for bug 1716:
        // - add xar_id as primary key
        // - make index on xar_regid unique

        $dbconn =& xarDBGetConn();
        $datadict =& xarDBNewDataDict($dbconn, 'CREATE');

        // Upgrade the module states table.
        // Get column definitions for module states table.
        $columns = $datadict->getColumns($module_states_table);
        // Do we have a xar_id column?
        $modules_column_found = false;
        foreach($columns as $column) {
            if ($column->name == 'xar_id') {
                $modules_column_found = true;
                break;
            }
        }
        // Upgrade the table (xar_module_states) if the name column is not found.
        if (!$modules_column_found) {
            // Create the column.
            $result = $datadict->addColumn($module_states_table, 'xar_id I AUTO PRIMARY');
            if ($result) {
                echo "Added column xar_id to table $module_states_table<br/>";
            } else {
                echo "Failed to add column xar_id to table $module_states_table<br/>";
            }

            // Bug #1971 - Have to use GenId to create values for xar_id on
            // existing rows or the create unique index will fail
            // TODO: check this: can PGSQL do this? Can it create a primary key on a table
            // with existing rows, when the primary key is, by definition, NOT NULL?
            // MySQL will automatically prefill the column with autoincrement values, but I
            // doubt PGSQL will.
            $query = "SELECT xar_regid, xar_state
                      FROM $module_states_table
                      WHERE xar_id IS NULL";
            $result = &$dbconn->Execute($query);
            if ($result) {
                // Get items from result array
                while (!$result->EOF) {
                    list ($regid, $state) = $result->fields;
                    $seqId = $dbconn->GenId($module_states_table);
                    $query = "UPDATE $module_states_table
                              SET xar_id = $seqId
                              WHERE xar_regid = $regid
                              AND xar_state = $state";
                    $updresult = &$dbconn->Execute($query);
                    if (!$updresult) {
                        echo "FAILED to update the $module_states_table table ID column<br/>";
                    }

                    $result->MoveNext();
                }
                // Close result set
                $result->Close();
            }

        } else {
            echo "Table $module_states_table does not require updating<br/>";
        }

        // Drop index i_xar_module_states_regid and create unique index
        // i_xar_module_states_regid2 on xar_regid.
        // By renaming the index, we know that it has been changed.
        $indexes = $datadict->getIndexes($module_states_table);
        $indexname = 'i_' . xarDBGetSiteTablePrefix() . '_module_states_regid';
        if (isset($indexes[$indexname])) {
            $result = $datadict->dropIndex($indexname, $module_states_table);
            if ($result) {
                echo "Dropped non-unique index $indexname from table $module_states_table<br/>";
            } else {
                echo "Failed to drop non-unique index $indexname from table $module_states_table<br/>";
            }
        }

        $indexname .= '2';
        if (!isset($indexes[$indexname])) {
            // We need to remove duplicate regids before creating a unique index on that column.
            $query = "select min(xar_id), xar_regid from $module_states_table group by xar_regid having count(xar_regid) > 1";
            $result = &$dbconn->Execute($query);
            if ($result) {
                // Get items from result array
                while (!$result->EOF) {
                    list ($xar_min_id, $xar_regid) = $result->fields;
                    $query2 = "delete from $module_states_table where xar_id <> $xar_min_id and xar_regid = $xar_regid";
                    $result2 = &$dbconn->Execute($query2);
                    $result2->close();
                    echo "Deleted duplicate module state rows (xar_regid=$xar_regid, leaving xar_id=$xar_min_id)<br/>";

                    $result->MoveNext();
                }
            }

            // Create the unique index.
            $result = $datadict->createIndex($indexname, $module_states_table, 'xar_regid', array('UNIQUE'));
            if ($result) {
                echo "Created unique index $indexname on $module_states_table.regid<br/>";
            } else {
                echo "Failed to create unique index $indexname on $module_states_table.regid<br/>";
            }
        }
    }

    // If output caching if enabled, check to see if the table xar_cache_blocks exists.
    // If it does not exist, disable output caching so that xarcachemanager can be upgraded.
    echo "<h5>Checking for and adding the xarCache block cache table</h5>";
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $cacheblockstable = xarDBGetSiteTablePrefix() . '_cache_blocks';
    $datadict =& xarDBNewDataDict($dbconn, 'ALTERTABLE');
    $flds = "
        xar_bid             I           NotNull DEFAULT 0,
        xar_nocache         L           NotNull DEFAULT 0,
        xar_page            L           NotNull DEFAULT 0,
        xar_user            L           NotNull DEFAULT 0,
        xar_expire          I           Null
    ";
    // Create or alter the table as necessary.
    $result = $datadict->changeTable($cacheblockstable, $flds);
    if (!$result) {return;}
    // Create a unique key on the xar_bid collumn
    $result = $datadict->createIndex('i_' . xarDBGetSiteTablePrefix() . '_cache_blocks_1',
                                     $cacheblockstable,
                                     'xar_bid',
                                     array('UNIQUE'));
    echo "...done.<br/>";

  /*$varCacheDir = xarCoreGetVarDirPath() . '/cache';
    if (file_exists($varCacheDir . '/output/cache.touch')) {
        echo "Output caching enabled, checking for required table...<br/>";
        $dbconn =& xarDBGetConn();
        $datadict =& xarDBNewDataDict($dbconn, 'CREATE');
        $blockscachetable = xarDBGetSiteTablePrefix() . '_cache_blocks';
        $tables = $datadict->getTables();
        // look for the required table
        if (array_search($blockscachetable, $tables) == false) {
            echo "The required " . $blockscachetable . " table is not available.<br/>";
            echo "Disabling output caching...<br/>";
            if (unlink($varCacheDir . '/output/cache.touch')) {
                echo "...done.<br/>";
            } else {
                echo "...Failed to remove \"" . $varCacheDir . "/output/cache.touch\".  Please remove this file by hand. <br/>";
            }
        } else {
            echo "Required table is present.<br/>";
        }
    } else {
        echo "Output caching is not enabled.<br/>";
    } */
    // Done with xarCache state check

    // Bug 1798 - Rename davedap module to phpldapmodule
    $regId = 1651;

    // Get module information from the database
    $dbModule = xarModAPIFunc('modules',
                              'admin',
                              'getdbmodules',
                              array('regId' => $regId));
    // Get module information from the filesystem
    $fileModule = xarModAPIFunc('modules',
                                'admin',
                                'getfilemodules',
                                array('regId' => $regId));

    if ((!isset($dbModule)) || (!isset($fileModule))){
        echo "FAILED to update the davedap module to phpldapadmin module<br/>";
    } else if (!isset($dbModule['name'])) {
        echo "Module davedap/phpldapadmin does not exist in database -- rename not necessary<br/>";
    } else if (!isset($fileModule['name'])) {
        echo "Module davedap/phpldapadmin does not exist in the /modules directory -- rename not necessary<br/>";
    } elseif (($dbModule['name'] == 'davedap') && ($fileModule['name'] == 'phpldapadmin')) {
        // Update modules table with new module name
        echo "<h5>Rename davedap module to phpldapadmin module in database.</h5>";
        $query = "UPDATE " . $sitePrefix . "_modules
                  SET xar_name = 'phpldapadmin',
                      xar_directory = 'phpldapadmin'
                  WHERE xar_regid = " . $regId;
        $result = &$dbconn->Execute($query);
        if (!$result) {
            echo "FAILED to update the davedap module to phpldapadmin module<br/>";
        } else {
            echo "Successfully renamed davedap module to phpldapadmin module in database<br/>";
        }
    } // End bug 1798

    // Bug 630, let's throw the reminder back up after upgrade.
    if (!xarModAPIFunc('blocks', 'user', 'get', array('name' => 'reminder'))) {
        $varshtml['html_content'] = 'Please delete install.php and upgrade.php from your webroot.';
        $varshtml['expire'] = time() + 7*24*60*60; // 7 days

        $htmlBlockType = xarModAPIFunc(
            'blocks', 'user', 'getblocktype',
            array('module' => 'base', 'type' => 'html')
        );

        if (empty($htmlBlockType) && xarCurrentErrorType() != XAR_NO_EXCEPTION) {
            return;
        }

        // Get the first available group ID, and assume that will be
        // visible to the administrator.
        $allgroups = xarModAPIFunc(
            'blocks', 'user', 'getallgroups',
            array('order' => 'id')
        );
        $topgroup = array_shift($allgroups);

        if (!xarModAPIFunc(
            'blocks', 'admin', 'create_instance',
            array(
                'title'    => 'Reminder',
                'name'     => 'reminder',
                'content'  => $varshtml,
                'type'     => $htmlBlockType['tid'],
                'groups'   => array(array('gid' => $topgroup['gid'])),
                'state'    => 2))) {
            return;
        }
    } // End bug 630

    // after 0911, make sure CSS class lib is deployed and css tags are registered
    echo "<h5>Making sure CSS tags are registered</h5>";
    if(!xarModAPIFunc('themes', 'css', 'registercsstags')) {
        echo "FAILED to register CSS tags<br/>";
    } else {
        echo "CSS tags registered successfully, css subsystem is ready to be deployed.<br/>";
    }

    // Bug 3164, store locale in ModUSerVar
    xarModSetVar('roles', 'locale', '');

  echo "<h5>Checking <strong>include/properties</strong> directory for moved DD properties</h5>";
    //From 1.0.0rc2 propsinplace was merged and dd propertie began to move to respective modules
    //Check they don't still exisit in the includes directory  bug 4371
  echo "<div style=\"text-align:left;\">";
    // set the array of properties that have moved
    $ddmoved=array(
        array('Dynamic_AIM_Property.php',1,'Roles'),
        array('Dynamic_Affero_Property.php',1,'Roles'),
        array('Dynamic_Array_Property.php',1,'Base'),
        array('Dynamic_Categories_Property.php',0,'Categories'),
        array('Dynamic_CheckboxList_Property.php',1,'Base'),
        array('Dynamic_CheckboxMask_Property.php',1,'Base'),
        array('Dynamic_Checkbox_Property.php',1,'Base'),
        array('Dynamic_Combo_Property.php',1,'Base'),
        array('Dynamic_CommentsNumberOf_Property.php',0,'Comments'),
        array('Dynamic_Comments_Property.php',0,'Comments'),
        array('Dynamic_CountryList_Property.php',1,'Base'),
        array('Dynamic_DateFormat_Property.php',1,'Base'),
        array('Dynamic_Email_Property.php',1,'Roles'),
        array('Dynamic_ExtendedDate_Property.php',1,'Base'),
        array('Dynamic_FileUpload_Property.php',1,'Roles'),
        array('Dynamic_FloatBox_Property.php',1,'Roles'),
        array('Dynamic_HTMLArea_Property.php',0,'HTMLArea'),
        array('Dynamic_HTMLPage_Property.php',1,'Base'),
        array('Dynamic_HitCount_Property.php',0,'HitCount'),
        array('Dynamic_ICQ_Property.php',1,'Roles'),
        array('Dynamic_ImageList_Property.php',1,'Roles'),
        array('Dynamic_Image_Property.php',1,'Roles'),
        array('Dynamic_LanguageList_Property.php',1,'Base'),
        array('Dynamic_LogLevel_Property.php',0,'Logconfig'),
        array('Dynamic_MSN_Property.php',1,'Roles'),
        array('Dynamic_MultiSelect_Property.php',1,'Base'),
        array('Dynamic_NumberBox_Property.php',1,'Base'),
        array('Dynamic_NumberList_Property.php',1,'Base'),
        array('Dynamic_PassBox_Property.php',1,'Base'),
        array('Dynamic_PayPalCart_Property.php',0,'Paypalsetup'),
        array('Dynamic_PayPalDonate_Property.php',0,'Paypalsetup'),
        array('Dynamic_PayPalNow_Property.php',0,'Paypalsetup'),
        array('Dynamic_PayPalSubscription_Property.php',0,'Paypalsetup'),
        array('Dynamic_RadioButtons_Property.php',1,'Base'),
        array('Dynamic_Rating_Property.php',0,'Ratings'),
        array('Dynamic_Select_Property.php',0,'Base'),
        array('Dynamic_SendToFriend_Property.php',0,'Recommend'),
        array('Dynamic_StateList_Property.php',1,'Base'),
        array('Dynamic_StaticText_Property.php',1,'Base'),
        array('Dynamic_Status_Property.php',0,'Articles'),
        array('Dynamic_TextArea_Property.php',1,'Base'),
        array('Dynamic_TextBox_Property.php',1,'Base'),
        array('Dynamic_TextUpload_Property.php',1,'Base'),
        array('Dynamic_TinyMCE_Property.php',0,'TinyMCE'),
        array('Dynamic_URLIcon_Property.php',1,'Base'),
        array('Dynamic_URLTitle_Property.php',1,'Base'),
        array('Dynamic_URL_Property.php',1,'Roles'),
        array('Dynamic_Upload_Property.php',0,'Uploads'),
        array('Dynamic_Yahoo_Property.php',1,'Roles'),
        array('Dynamic_Calendar_Property.php',1,'Base'),
        array('Dynamic_TColorPicker_Property.php',1,'Base'),
        array('Dynamic_TimeZone_Property.php',1,'Base'),
        array('Dynamic_Module_Property.php',1,'Modules'),
        array('Dynamic_GroupList_Property.php',1,'Roles'),
        array('Dynamic_UserList_Property.php',1,'Roles'),
        array('Dynamic_Username_Property.php',1,'Roles'),
        array('Dynamic_DataSource_Property.php',1,'DynamicData'),
        array('Dynamic_FieldStatus_Property.php',1,'DynamicData'),
        array('Dynamic_FieldType_Property.php',1,'DynamicData'),
        array('Dynamic_Hidden_Property.php',1,'Base'),
        array('Dynamic_ItemID_Property.php',1,'DynamicData'),
        array('Dynamic_ItemType_Property.php',1,'DynamicData'),
        array('Dynamic_Object_Property.php',1,'DynamicData'),
        array('Dynamic_SubForm_Property.php',1,'DynamicData'),
        array('Dynamic_Validation_Property.php',1,'DynamicData')
    );
    //set the array to hold properties that have not moved and should do!
    $ddtomove=array();

    //Check the files in the includes/properties dir against the initial array
    $oldpropdir='includes/properties';
    $var = is_dir($oldpropdir);
    $handle=opendir($oldpropdir);
    $skip_array = array('.','..','SCCS','index.htm','index.html');

    if ($var) {
             while (false !== ($file = readdir($handle))) {
                  // check the  dd file array and add to the ddtomove array if the file exists
                  if (!in_array($file,$skip_array))  {

                     foreach ($ddmoved as $key=>$propname) {
                          if ($file == $ddmoved[$key][0]){
                            $ddtomove[]=$ddmoved[$key];
                           }
                    }
                  }
            }
            closedir($handle);
    }
    if (is_array($ddtomove) && !empty($ddtomove[0])){

        echo "<h2 style=\"font:size:large;color:red; font-weigh:bold;\">WARNING!</h2>The following DD property files exist in your Xaraya <strong>includes/properties</strong> directory.<br />";
        echo "Please delete each of the following and ONLY the following from your <strong>includes/properties</strong> directory as they have now been moved to the relevant module in core, or the 3rd party module concerned.<br /><br />";
        echo "Once you have removed the duplicated property files from <strong>includes/properties</strong> please re-run upgrade.php.<br /><br />";

        foreach ($ddtomove as $ddkey=>$ddpropname) {
             if ($ddtomove[$ddkey][1] == 1) {
                echo "<strong>".$ddtomove[$ddkey][0]."</strong> exits. Please remove it from includes/properties.<br /><br />";
             }else{
                echo "<strong>".$ddtomove[$ddkey][0]."</strong> is a ".$ddtomove[$ddkey][2]." module property. Please remove it from includes/properties. IF you have ".$ddtomove[$ddkey][2]." installed, check you have the property in the <strong>".strtolower($ddtomove[$ddkey][2])."/xarproperties</strong> directory else upgrade your ".$ddtomove[$ddkey][2]." module.<br /><br />";
             }
        }

        echo "<br /><br />REMEMBER! Run upgrade.php again when you delete the above properties from the includes/properties directory.<br /><br />";
        echo "</div>";

        unset($ddtomove);

        CatchOutput();
        return;
     }else{
        echo "</div>";
        echo "<br />Done! All properties have been checked and verified for location!<br /><br />";
    }



    // More or less generic stuff
    echo "<h5>Generic upgrade activities</h5>";
    // Propsinplace scenario, flush the property cache, so on upgrade all proptypes
    // are properly set in the database.
    echo "Flushing the property cache";
    if(!xarModAPIFunc('dynamicdata','admin','importpropertytypes', array('flush' => true))) {
        echo "WARNING: Flushing property cache failed";
    }

?>
<div class="xar-mod-body"><h2><?php echo $complete; ?></h2><br />
Thank you, the upgrades are complete. It is recommended you go to the
<a href="<?php echo xarModUrl('modules','admin','list'); ?>">admin section of the modules module</a>
to upgrade the modules which have a new version.
</div>
</div>
</div>

<?php
}

CatchOutput();
// done
exit;

/**
 * Helper function to render the output as we have it so far
 *
 */
function CatchOutput()
{
    $out = ob_get_contents();
    ob_end_clean();
    xarTplSetPageTitle(xarML('Upgrade Xaraya'));
    echo xarTpl_renderPage($out,NULL, 'installer');
}
?>
