<?php
/**
 * File: $Id$
 *
 * Import Slashcode sections and topics into your Xaraya test site
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage import
 * @author Richard Cave <rcave@xaraya.com>
 */

/**
 * Note : this file is part of import_slashcode.php and cannot be run separately
 */

    echo "<strong>$step. Importing Slashcode topics into categories</strong><br/>\n";

    // Initialize table names
    $table_sections = 'sections';
    $table_topics = 'topics';
    $table_topic_parents = 'topic_parents';
    $table_topics_tree = xarDBGetSiteTablePrefix() . '_installer_topics';
    
    // Delet current categories
    if ($resetcat) {
        $dbconn->Execute("DELETE FROM " . $tables['categories']);
        //$dbconn->Execute('FLUSH TABLE ' . $tables['categories']);
    }
    $regid = xarModGetIDFromName('articles');
    if ($reset) {
        $dbconn->Execute("DELETE FROM " . $tables['categories_linkage'] . " WHERE xar_modid=$regid");
        //$dbconn->Execute('FLUSH TABLE ' . $tables['categories_linkage']);
    }
    if (!empty($docounter)) {
        if ($reset) {
            $regid2 = xarModGetIDFromName('categories');
            $dbconn->Execute("DELETE FROM " . $tables['hitcount'] . " WHERE xar_moduleid = " . $regid2);
            //$dbconn->Execute('FLUSH TABLE ' . $tables['hitcount']);
        }
    }
    
    // No hits tracked in Slashcode topics - set to 0 for hitcount
    $counter = 0;

    // Slashcode topics are stored in two tables - topics and topic_parents.  Since
    // it's a bit difficult to do recursive joins in such a manner that a single 
    // SELECT query will return all the topics in a tree in their proper order,
    // we'll brute force our way through the tables.
    
    echo "Creating root category for sections<br/>\n";
    $sections_cid = xarModAPIFunc('categories', 
                                  'admin', 
                                  'create', 
                                  array('name' => 'Sections',
                                        'description' => 'Sections',
                                        'parent_id' => 0));
                                        
    if (!$sections_cid) {
        die("Error creating sections category");
    }
                               
    echo "Creating root category for topics<br/>\n";
    $categories_cid = xarModAPIFunc('categories', 
                                    'admin', 
                                    'create', 
                                    array('name' => 'Topics',
                                          'description' => 'Topics',
                                          'parent_id' => 0));
                                          
    if (!$categories_cid) {
        die("Error creating topics category");
    }
                                  
    // preset the article categories to those two types
    if ($reset) {
        $settings = unserialize(xarModGetVar('articles', 'settings.1'));
        $settings['number_of_categories'] = 2;
        $settings['cids'] = array($sections_cid, $categories_cid);
        xarModSetVar('articles', 'settings.1', serialize($settings));
        xarModSetVar('articles', 'number_of_categories.1', 2);
        xarModSetVar('articles', 'mastercids.1', $sections_cid .';'.$categories_cid);
    } else {
        // you'll be in trouble with your categories here...
        die("You must reset your categories to continue." );
    }

    // Get all sections
    $query = "SELECT $table_sections.id,
                     $table_sections.title, 
                     $table_sections.url
              FROM $table_sections
              ORDER BY $table_sections.id ASC";

    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select all sections from " . $table_sections . " failed : " . $dbconn->ErrorMsg());
    }

    // Loop through result
    while (!$result->EOF) {
        list($id, $title, $url) = $result->fields;
        // Create a new category based on the section
        $sections[$id] = xarModAPIFunc('categories', 
                                       'admin', 
                                       'create', 
                                       array('name' => $title,
                                             'description' => $url,
                                             'parent_id' => $sections_cid));
                                         
        echo "Creating section ($id) $title<br/>\n";
        if (!empty($docounter)) {
            $hcid = xarModAPIFunc('hitcount',
                                  'admin',
                                  'create',
                                  array('modname' => 'categories',
                                        'objectid' => $sections[$id],
                                        'hits' => $counter));
            if (!isset($hcid)) {
                echo "Couldn't create hit counter $counter for section $sections[$id] $title<br/>\n";
            }
        }
        $result->MoveNext();
    }
    $result->Close();

    // To make it easier to retrieve the values from the tables topics and topic_parents,
    // we'll create a new table and store the values that we need in that table.

    // In case the topics_tree table exits, drop the table
    $dbconn->Execute("DROP TABLE " . $table_topics_tree);

    // Create topic tree table
    $fields = array(
        'xar_tid'            => array('type'=>'integer','size'=>'tiny','null'=>FALSE),
        'xar_textname'       => array('type'=>'varchar','size'=>80,'null'=>FALSE),
        'xar_image'          => array('type'=>'varchar','size'=>100,'null'=>FALSE),
        'xar_parent_tid'     => array('type'=>'integer','size'=>'tiny','null'=>TRUE)
    );

    // Create the table DDL

    $query = xarDBCreateTable($table_topics_tree,$fields);

    if (empty($query)) {
        echo "Couldn't create query for table $table_topics_tree<br/>\n";
        return; // throw back
    }

    // Pass the Table Create DDL to adodb to create the table
    $dbconn->Execute($query);

    // Check for an error with the database
    if ($dbconn->ErrorNo() != 0) {
        die("Oops, create of table " . $table_topics_tree . " failed : " . $dbconn->ErrorMsg());
    }

    // Insert the topics and topic_parent rows into this table
    $query = "INSERT INTO $table_topics_tree
              SELECT $table_topics.tid, 
                     $table_topics.textname, 
                     $table_topics.image, 
                     $table_topic_parents.parent_tid
              FROM $table_topics
              LEFT JOIN $table_topic_parents
              ON $table_topics.tid = $table_topic_parents.tid";
  
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, insert into " . $table_topics_tree . " failed : " . $dbconn->ErrorMsg());
    }
    
    // Get all of the topics from the topics tree where parent_tid is null
    $query = "SELECT $table_topics_tree.xar_tid,
                     $table_topics_tree.xar_textname, 
                     $table_topics_tree.xar_image
              FROM   $table_topics_tree
              WHERE  $table_topics_tree.xar_parent_tid IS NULL";
  
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select parent topics from " . $table_topics_tree . " failed : " . $dbconn->ErrorMsg());
    }
    
    // Loop through result
    while (!$result->EOF) {
        list($tid, $textname, $image) = $result->fields;

        // Create category
        $cid = xarModAPIFunc('categories', 
                             'admin', 
                             'create', 
                             array('name' => $textname,
                                   'description' => $textname,
                                   'parent_id' => $categories_cid));
                             
        // Set new cid in categories array
        $categories[$tid] = $cid;
              
        // Set parent cat array
        $parentcats[] = array('tid' => $tid, 'cid' => $cid);
        
        echo "Creating parent category ($cid) $textname- parent " . $categories_cid['cid'] . "<br/>\n";
        
        if (!empty($docounter)) {
            $hcid = xarModAPIFunc('hitcount',
                                  'admin',
                                  'create',
                                  array('modname' => 'categories',
                                        'objectid' => $cid,
                                        'hits' => $counter));
            if (!isset($hcid)) {
                echo "Couldn't create hit counter $counter for section $cid $textname<br/>\n";
            }
        }
        $result->MoveNext();
    }
    $result->Close();
    
    // TODO:  Rewrite to use depth and lineage.  But for now, brute force retrieval of all of the 
    // children and grandchid topics of the parent topics.
    // 

    // Get child topics
    foreach ($parentcats as $parentcat) {
        // Get all children topics
        $query = "SELECT $table_topics_tree.xar_tid,
                         $table_topics_tree.xar_textname, 
                         $table_topics_tree.xar_image
                  FROM   $table_topics_tree
                  WHERE  $table_topics_tree.xar_parent_tid = " . $parentcat['tid'];

        $result =& $dbconn->Execute($query);
        if (!$result) {
            die("Oops, select child topics from " . $table_topics_tree . " failed : " . $dbconn->ErrorMsg());
        }
        
        // Loop through result
        while (!$result->EOF) {
            list($tid, $textname, $image) = $result->fields;
            // Create category
            $cid = xarModAPIFunc('categories', 
                                 'admin', 
                                 'create', 
                                 array('name' => $textname,
                                       'description' => $textname,
                                       'parent_id' => $parentcat['cid']));

            // Set new cid in categories array
            $categories[$tid] = $cid;
                   
            // Set parent cat array
            $childcats[] = array('tid' => $tid, 'cid' => $cid);

            echo "Creating child category ($cid) $textname - parent " . $parentcat['cid'] . "<br/>\n";
            
            if (!empty($docounter)) {
                $hcid = xarModAPIFunc('hitcount',
                                      'admin',
                                      'create',
                                      array('modname' => 'categories',
                                            'objectid' => $cid,
                                            'hits' => $counter));
                if (!isset($hcid)) {
                    echo "Couldn't create hit counter $counter for section $cid $textname<br/>\n";
                }
            }
            $result->MoveNext();
        }        
        $result->Close();
    }
    
    // Retrieve grandchild topics
    foreach ($childcats as $childcat) {
        // Get all children topics
        $query = "SELECT $table_topics_tree.xar_tid,
                         $table_topics_tree.xar_textname, 
                         $table_topics_tree.xar_image
                  FROM   $table_topics_tree
                  WHERE  $table_topics_tree.xar_parent_tid = " . $childcat['tid'];

        $result =& $dbconn->Execute($query);
        if (!$result) {
            die("Oops, select grandchild topics from " . $table_topics_tree . " failed : " . $dbconn->ErrorMsg());
        }
        
        // Loop through result
        while (!$result->EOF) {
            list($tid, $textname, $image) = $result->fields;
            // Create category
            $cid = xarModAPIFunc('categories', 
                                 'admin', 
                                 'create', 
                                 array('name' => $textname,
                                       'description' => $textname,
                                       'parent_id' => $childcat['cid']));
            
            // Set new cid in categories array
            $categories[$tid] = $cid;
                                           
            // Set parent cat array
            $grandchildcats[] = array('tid' => $tid, 'cid' => $cid);

            echo "Creating grandchild category ($cid) $textname - parent " . $childcat['cid'] . " <br/>\n";
            
            if (!empty($docounter)) {
                $hcid = xarModAPIFunc('hitcount',
                                      'admin',
                                      'create',
                                      array('modname' => 'categories',
                                            'objectid' => $cid,
                                            'hits' => $counter));
                if (!isset($hcid)) {
                    echo "Couldn't create hit counter $counter for section $cid $textname<br/>\n";
                }
            }
            $result->MoveNext();
        }        
        $result->Close();
    }
    
    echo "<strong>TODO:  Retrieve more than 3 levels of topics</strong><br/><br/>\n";   
    //echo "<strong>TODO : copy the topic_nexus and topic_nexus_extra tables</strong><br/><br/>\n";
    
    xarModSetVar('installer','sections_cid',$sections_cid);
    xarModSetVar('installer','sections',serialize($sections));
    xarModSetVar('installer','categories_cid',$categories_cid);
    xarModSetVar('installer','categories',serialize($categories));
    
    echo '<a href="import_slashcode.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import_slashcode.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br/>';
    
    // Optimize tables
    $dbconn->$dbtype = xarModGetVar('installer','dbtype');
    switch ($dbtype) {
        case 'mysql':
            $query = 'OPTIMIZE TABLE ' . $tables['categories'];
            $result =& $dbconn->Execute($query);
            if (!empty($docounter)) {
                $query = 'OPTIMIZE TABLE ' . $tables['hitcount'];
                $result =& $dbconn->Execute($query);
            }
            break;
        case 'postgres':
            $query = 'VACUUM ANALYZE ' . $tables['categories'];
            $result =& $dbconn->Execute($query);
            if (!empty($docounter)) {
                $query = 'VACUUM ANALYZE ' . $tables['hitcount'];
                $result =& $dbconn->Execute($query);
            }
            break;
        default:
            break;
    }
      

?>
