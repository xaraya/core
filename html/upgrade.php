<?php
/**
 * File: $Id$
 *
 * Core Database Upgrade File
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage html
 * @author mikespub <mikespub@xaraya.com>
*/

// initialize the Xaraya core
include 'includes/xarCore.php';
xarCoreInit(XARCORE_SYSTEM_ALL);

// Use the installer theme
xarTplSetThemeName('installer');

if(!xarVarFetch('step','int', $step, NULL, XARVAR_DONT_SET)) {return;}

// pre-fill the module name (if any) for hooks
xarRequestGetInfo();
//Load Table Maintainance API
xarDBLoadTableMaintenanceAPI();

// The System.Core.VersionNum contains the currently stored version number
// this may be different from the define in xarCore.php
$xarVersion = xarConfigGetVar('System.Core.VersionNum');

$title = xarML('Upgrade');

if (empty($step)) {
    $descr = xarML('Preparing to upgrade from previous Xaraya Version #(1) to version #(2)',$xarVersion, XARCORE_VERSION_NUM);
    // start the output buffer
    ob_start();
?>

<div class="xar-mod-head"><span class="xar-mod-title"><?php echo $title; ?></span></div>
<div class="xar-mod-body"><h2><?php echo $descr; ?></h2><br />
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
    // First see if the authsystem module has been upgraded.
    echo "<h5>Making sure the system is ready for authenticating</h5>";

    // UNSECURE CODE, RETHINK THIS
    // If authsystem module is upgraded
    $as_dbinfo = xarModGetInfo(42);
    $as_fileinfo = xarMod_getFileInfo('authsystem');
    if($as_dbinfo['version'] != $as_fileinfo['version']) {
        echo xarML("Authentication system needs an upgrade") . "<br/>";
        echo xarML("Upgrading authsystem from version #(1) to version #(2)",$as_dbinfo['version'],$as_fileinfo['version']) . "<br/>";
        // authsystem has no init function, so we can just patch in the new version number in the database.
        $sql = "UPDATE " . $sprefix . "_modules SET xar_version='".  $as_fileinfo['version'] . "' WHERE xar_regid='42'";
        $dbconn =& xarDBGetConn();
        if(!$dbconn->Execute($sql)) {
            echo xarML("FAILED upgrading the authentication system. I cannot continue, I'm sorry.");
            echo "</div></div>";
            CatchOutput();
            return;
        } else {
            echo xarML("Upgrade of authentication system successfull") . "<br/>";
        }
    } else {
        echo xarML("System capable to authenticate, no upgrade needed");
        echo "<br/>";
    }

    // Now we can check whether people are logged in
    if(!xarSecurityCheck('AdminPanel')){
        echo xarML('You must be logged in with admin permissions to upgrade Xaraya.');
        echo '</div></div>';
        // catch the output
        CatchOutput();
        return;
    }

    // Check the installed security instances table for hard coded prefix bug and the bug fix bug :).
    echo "<h5>Checking Security Instances Table</h5>";
    $sprefix=xarDBGetSiteTablePrefix();

    echo "Table Prefix is : ".$sprefix."<br /><br />";
    echo "Checking hard coded table prefixes in security_instances table for categories, articles, ratings and hitcount modules.<br /><br />";
    $instancestable = $sprefix."_security_instances";
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

          foreach($categoriesinstances as $categoriesinstance){
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
          if ($categoryinstance != $xarquery) {
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
      if (xarModIsAvailable('hitcount')) {
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
            xarModUnregisterHook('item', 'create', 'API',
                                 'adminpanels', 'admin', 'createwc');
            xarModUnregisterHook('item', 'update', 'API',
                                 'adminpanels', 'admin', 'deletewc');
            xarModUnregisterHook('item', 'delete', 'API',
                                 'adminpanels', 'admin', 'deletewc');
            xarModUnregisterHook('item', 'remove', 'API',
                                 'adminpanels', 'admin', 'deletewc');

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
        if ($result->PO_RecordCount() != 1) {
            $msg = xarML("Group 'syndicate' not found.");
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                           new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return;
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
    $role = xarFindRole('Everybody');
    $admin = xarFindRole('Admin');
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
                             'set'     =>  '<a href="http://www.xaraya.com"><img src="modules/base/xarimages/xaraya.gif" alt="Powered by Xaraya" style="border:0px;" /></a>'),
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
                       array('name'    =>  'rolesdisplay',
                             'module'  =>  'roles',
                             'set'     =>  'tabbed'),
                       array('name'    =>  'confirmationemail',
                             'module'  =>  'roles',
                             'set'     =>  'Your account has been created for %%sitename%% and needs to be activated.
You can either do this now, or on the first time that you log in.
If you prefer to do it now, then you will need to follow this link :
%%validationlink%%
Here are the details that were provided.

IP Address of the person creating that account: %%ipaddress%%
User Name:  %%username%%
Password:  %%password%%

Validation Code to activate your account:  %%valcode%%

If you did not create this account, then do nothing.  The account will be deemed
inactive after a period of time and deleted from our records.  You will recieve
no further emails from us.Thank you,

%%siteadmin%%',
                             'override'  =>  1),
                     array('name'    =>  'remindertitle',
                             'module'  =>  'roles',
                             'set'     =>  'Replacement login information for %%name%% at
%%sitename%%',
                             'override'  =>  1),
                    array('name'    =>  'reminderemail',
                             'module'  =>  'roles',
                             'set'     =>  '%%name%%,

Here is your new password for %%sitename%%. You may now login to %%siteurl%%
using the following username and password:
username: %%username%%
password: %%password%%

-- %%siteadmin%%',
                             'override'  =>  1),
                    array('name'    =>  'validationtitle',
                             'module'  =>  'roles',
                             'set'     =>  'Validate your account %%name%% at %%sitename%%',
                             'override'  =>  1),
                    array('name'    =>  'validationemail',
                             'module'  =>  'roles',
                             'set'     =>  '%%name%%,

Your account must be validated again because your e-mail address has changed or
an administrator has unvalidated it. You can either do this now, or on the next
time that you log in. If you prefer to do it now, then you will need to follow
this link : %%validationlink%%
Validation Code to activate your account:  %%valcode%%

You will receive an email has soon as your account is activated again.

%%siteadmin%%%',
                             'override'  =>  1),
                    array('name'    =>  'deactivationtitle',
                             'module'  =>  'roles',
                             'set'     =>  '%%name%% deactivated at %%sitename%%',
                             'override'  =>  1),
                    array('name'    =>  'deactivationemail',
                             'module'  =>  'roles',
                             'set'     =>  '%%name%%,

Your account was deactivated by the administrator.
If you want to know the reason, contact %%adminmail%%
You will receive an email as soon as your account is activated again.

%%siteadmin%%%',
                             'override'  =>  1),
                    array('name'    =>  'pendingtitle',
                             'module'  =>  'roles',
                             'set'     =>  'Pending state of %%name%% at %%sitename%%',
                             'override'  =>  1),
                    array('name'    =>  'pendingemail',
                             'module'  =>  'roles',
                             'set'     =>  '%%name%%,

Your account is pending.
You\'ll have to wait for the explicit approval of the administrator to log
again.
If you want to know the reason, contact %%adminmail%%
You will receive an email has soon as your account is activated again.

%%siteadmin%%%',
                             'override'  =>  1),
                    array('name'    =>  'passwordtitle',
                             'module'  =>  'roles',
                             'set'     =>  'Your password at %%sitename%% has been changed',
                             'override'  =>  1),
                    array('name'    =>  'passwordemail',
                             'module'  =>  'roles',
                             'set'     =>  '%%name%%,

Your password has been changed by an administrator.
You can now login at %%siteurl%% with the following information:
Login : %%username%%
Password : %%password%%

%%siteadmin%%',
                             'override'  =>  1),
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

    // Delete any empty modvars.
    $delmodvars[] = array(array('name'    =>  'showtacs',
                                'module'  =>  'roles'));

    foreach($delmodvars as $delmodvar){
        foreach($delmodvar as $var){
            $currentvar = xarModGetVar("$var[module]", "$var[name]");
            if (isset($currentvar)){
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
        echo "Priviliges Masks do not exist, attempting to create... done! <br />";

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
    // Check the installed privs and masks.
    echo "<h5>Checking Hook Structure</h5>";

    $ratings = array();

    if (xarModIsAvailable('ratings')) {
        $ratings['deleteall'] = xarModRegisterHook('module', 'remove', 'API', 'ratings', 'admin', 'deleteall');
    }

    if (!isset($ratings['deleteall']) || !$ratings['deleteall']) {
        echo "Ratings Delete All Hook already exists, moving to next check. <br /> ";
    } else {
        echo "Setting Ratings Delete All Hook... done! <br />";
    }

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
    echo "<h5>Upgrade module states table</h5>";
    $module_states_table = $sitePrefix . '_module_states';

    // TODO: use adodb transactions to ensure atomicity?
    // The changes for bug 1716:
    // - add xar_id as primary key
    // - make index on xar_regid unique
    // 1. Add the primary key: save operation
    $changes = array('command'     => 'add',
                     'field'       => 'xar_id',
                     'type'        => 'integer',
                     'null'        => false,
                     'unsigned'    => true,
                     'increment'   => true,
                     'primary_key' => true,
                     'first'       => true);
    $query = xarDBAlterTable($module_states_table, $changes);
    $result = &$dbconn->Execute($query);
    if (!$result) {
        echo "FAILED to alter the $module_states_table table<br/>";
        echo "The column xar_id may already exist in the $module_states_table table<br/>";
    } else {
        echo "Added column xar_id in the $module_states_table table<br/>";

        // Bug #1971 - Have to use GenId to create values for xar_id on
        // existing rows or the create unique index will fail
        $query = "SELECT xar_regid, xar_state
                  FROM $module_states_table
                  WHERE xar_id IS NULL";
        $result = &$dbconn->Execute($query);
        if (!$result) {
            echo "FAILED to update the $module_states_table table<br/>";
        }

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
                echo "FAILED to update the $module_states_table table<br/>";
            }

            $result->MoveNext();
        }

        // Close result set
        $result->Close();

        // 2. change index for reg_id to unique
        $indexname = 'i_' . $sitePrefix . '_module_states_regid';
        $query = xarDBDropIndex($module_states_table, array('name' => $indexname));
        $result = &$dbconn->Execute($query);
        if (!$result) {
            echo "FAILED to drop the old index on the module states regid column<br/>";
        } else {
            echo "Dropped old index for $module_states_table table<br/>";
        }

        // 3. Add the new index.
        $index = array('name' => $indexname, 'unique' => true, 'fields' => array('xar_regid'));
        $query = xarDBCreateIndex($module_states_table, $index);

        $result = &$dbconn->Execute($query);
        if (!$result) {
            echo "FAILED to create the new index for the module states regid column<br/>";
        } else {
            echo "Added a new index on the $module_states_table table<br/>";
        }

        // 4. Set the version number of the modules module
        $query = "UPDATE " . $sitePrefix . "_modules SET xar_version='2.3.0' WHERE xar_regid=1";
        $result = &$dbconn->Execute($query);
        if(!$result) {
            echo "FAILED to update the version number for the modules module<br/>";
        } else {
            echo "Updated the version number for modules module<br/>";
        }
    }

    // If output caching if enabled, check to see if the table xar_cache_blocks exists.
    // If it does not exist, disable output caching so that xarcachemanager can be upgraded.
    echo "<h5>Checking xarCache State</h5>";
    $varCacheDir = xarCoreGetVarDirPath() . '/cache';
    if (file_exists($varCacheDir . '/output/cache.touch') && !xarModGetVar('xarcachemanager','CacheBlockOutput')) {
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
    } // Done with xarCache state check

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

    if (!xarModAPIFunc('blocks', 'user', 'getall', array('name' => 'reminder'))) {

        $now = time();

        $varshtml['html_content'] = 'Please delete install.php and upgrade.php from your webroot .';
        $varshtml['expire'] = $now + 24000;
        $msg = serialize($varshtml);

        $htmlBlockType = xarModAPIFunc('blocks', 'user', 'getblocktype',
                                       array('module'  => 'base',
                                             'type'    => 'html'));

        if (empty($htmlBlockType) && xarCurrentErrorType() != XAR_NO_EXCEPTION) {
            return;
        }

        $htmlBlockTypeId = $htmlBlockType['tid'];

        if (!xarModAPIFunc('blocks', 'admin', 'create_instance',
                           array('title'    => 'Reminder',
                                 'name'     => 'reminder',
                                 'content'  => $msg,
                                 'type'     => $htmlBlockTypeId,
                                 'groups'   => array(array('gid'      => 1,
                                                           'template' => '')),
                                 'template' => '',
                                 'state'    => 2))) {
            return;
        }
    } // End bug 630

?>
<div class="xar-mod-body"><h2><?php echo $complete; ?></h2><br />
Thank you, the upgrades are complete. It is recommended you go to the
<a href="<?php echo xarModUrl('modules','admin','list'); ?>">admin section of the modules module</a>
to upgrade the modules which have gotten a new version.
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
    echo xarTpl_renderPage($out);
    flush();
}
?>
