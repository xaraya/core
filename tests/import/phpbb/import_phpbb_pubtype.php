<?php
/**
 * File: $Id$
 *
 * Create publication type for phpBB forums in your Xaraya test site
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage import
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Note : this file is part of import_phpbb.php and cannot be run separately
 */

if ($importmodule == 'articles') {
    echo "<strong>$step. Creating publication type for phpBB forums in articles</strong><br/>\n";

    $pubtypes = xarModAPIFunc('articles','user','getpubtypes');
    $ptid = '';
    foreach ($pubtypes as $id => $pubtype) {
        if ($pubtype['name'] == 'forums') {
            $ptid = $id;
            break;
        }
    }
    if (empty($ptid)) {
        $ptid = xarModAPIFunc('articles', 'admin', 'createpubtype',
                              array (
                                'name' => 'forums',
                                'descr' => 'Discussion Forums',
                                'config' => 
                                array (
                                  'title' => 
                                  array (
                                    'label' => 'Subject',
                                    'format' => 'textbox',
                                    'input' => 'on',
                                  ),
                                  'summary' => 
                                  array (
                                    'label' => 'Username',
                                    'format' => 'textbox',
                                    'input' => 'on',
                                  ),
                                  'bodytext' => 
                                  array (
                                    'label' => 'Message',
                                    'format' => 'textarea_large',
                                    'input' => 'on',
                                  ),
                                  'bodyfile' => 
                                  array (
                                    'label' => '',
                                    'format' => 'fileupload',
                                  ),
                                  'notes' => 
                                  array (
                                    'label' => 'Last Post ?',
                                    'format' => 'calendar',
                                  ),
                                  'authorid' => 
                                  array (
                                    'label' => 'Author',
                                    'format' => 'username',
                                  ),
                                  'pubdate' => 
                                  array (
                                    'label' => 'Publication Date',
                                    'format' => 'calendar',
                                  ),
                                  'status' => 
                                  array (
                                    'label' => 'Status',
                                    'format' => 'status',
                                  ),
                                ),
                              )
                             );
        if (empty($ptid)) {
            echo "Creating publication type 'forums' failed : " . xarExceptionRender('text') . "<br/>\n";
        } else {
            $settings = array (
                         'itemsperpage' => '40',
                         'number_of_columns' => '0',
                         'defaultview' => '1',
                         'showcategories' => 0,
                         'showprevnext' => '1',
                         'showcomments' => '1',
                         'showhitcounts' => '1',
                         'showratings' => '1',
                         'showarchives' => 0,
                         'showmap' => 0,
                         'showpublinks' => 0,
                         'dotransform' => 0,
                         'prevnextart' => '1',
                         'page_template' => '',
                        );
            xarModSetVar('articles', 'settings.'.$ptid, serialize($settings));
            xarModSetVar('articles', 'number_of_categories.'.$ptid, 0);
            xarModSetVar('articles', 'mastercids.'.$ptid, '');
            xarModSetAlias('forums','articles');
            echo "Publication type 'forums' created...<br />\n";
        }
    } else {
        echo "Publication type 'forums' already exists...<br />\n";
    }
    xarModSetVar('installer','ptid',$ptid);
}

?>
