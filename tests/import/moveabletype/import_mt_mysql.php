<?php
/**
 * File: $Id$
 *
 * Import Moveable Type 2.64+  users into your Xaraya test site
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage import
 * @author rabbitt <rabbitt@xaraya.com>
 * @author apakuni <apakuni@xaraya.com>
 * @author mikespub <mikespub@xaraya.com>
 */

    function find_cat($needle, &$haystack) {
        foreach ($haystack as $key => $value) {
            if (array_search($needle, $value) !== FALSE) {
                return $value['cid'];
            }
        }
        return false;
    }

    // MT's status 1 (draft) == Xaraya's status 0 (submitted)
    $article_status_map[1] = 0;
    // MT's status 2 (Publish) == Xaraya's status 2 (Approved)
    $article_status_map[2] = 2;

    define('_USER_STATE_ACTIVE', 3);
    define('_ARTICLE_STATE_',0);

    echo "<strong>$step. Grabbing all data</strong><br>\n";

    $pubtype_config = array('title'     => array('label' => 'Title',
                                         'format' => 'textbox',
                                         'input' => 1),

                            'summary'   => array('label' => 'Introduction',
                                                 'format' => 'textarea_medium',
                                                 'input' => 1),

                            'bodytext'  => array('label' => 'Body Text',
                                                 'format' => 'textarea_large',
                                                 'input' => 1),

                            'bodyfile'  => array('label' => '',
                                                 'format' => 'static',
                                                 'input' => 0),

                            'notes'     => array('label' => 'Notes',
                                                 'format' => 'textarea_small',
                                                 'input' => 0),

                            'authorid'  => array('label' => 'Author',
                                                 'format' => 'username',
                                                 'input' => 0),

                            'pubdate'   => array('label' => 'Publication Date',
                                                 'format' => 'calendar',
                                                 'input' => 1),

                            'status'    => array('label' => 'Status',
                                                 'format' => 'status',
                                                 'input' => 0));

    $pubtype_settings = array('number_of_columns'    => 0,
                      'itemsperpage'         => 20,
                      'defaultview'          => 1,
                      'showcategories'       => 1,
                      'showprevnext'         => 0,
                      'showcomments'         => 1,
                      'showhitcounts'        => 1,
                      'showratings'          => 0,
                      'showarchives'         => 1,
                      'showmap'              => 1,
                      'showpublinks'         => 0,
                      'dotransform'          => 0,
                      'prevnextart'          => 0,
                      'usealias'             => 0,
                      'page_template'        => '');


    $users = 'SELECT author_id as uid,
                     author_name as uname,
                     author_nickname as realname,
                     author_password as pass,
                     author_email as email
                FROM mt_author
            ORDER BY author_id';

    $entries = 'SELECT entry_id as aid,
                       entry_author_id as uid,
                       entry_title as title,
                       mt_placement.placement_category_id as catid,
                       entry_text as summary,
                       entry_text_more as body,
                       entry_status as status,
                       entry_created_on as pubdate,
                       entry_blog_id as bid
                  FROM mt_entry, mt_placement
                 WHERE mt_placement.placement_entry_id = entry_id
              ORDER BY entry_id';

    $cats = 'SELECT category_id as catid,
                    category_label as name,
                    category_description as description,
                    category_blog_id as bid
               FROM mt_category
           ORDER BY category_id';

    $blogs = 'SELECT blog_id as bid,
                     blog_name as name,
                     blog_description as description
                FROM mt_blog
            ORDER BY blog_id';

    $comments = 'SELECT comment_id as cid,
                        comment_entry_id as aid,
                        comment_text as body,
                        comment_created_on as cdate,
                        comment_created_by as uid,
                        comment_author as author,
                        comment_email as email,
                        comment_ip as hostname,
                        comment_url as url,
                        comment_blog_id as bid
                   FROM mt_comment
               ORDER BY comment_id';

    $stuff = array('users' => $users,
                   'blogs' => $blogs,
                   'cats' => $cats,
                   'entries' => $entries,
                   'comments' => $comments);

    foreach ($stuff as $key => $query) {

        $result = $dbconn->Execute($query);

        if (!$result)
            return;

        $line = 1;

        echo '<br /><strong>GRABBING rows for: <i>'.$key.'</i></strong>';
        echo '<blockquote';

        while (!$result->EOF) {
            $row = $result->GetRowAssoc(false);

            switch ($key) {
                case 'users':
                    $uid = $row['uid'];
                    unset($row['uid']);

                    if (empty($row['realname'])) {
                        $row['realname'] = $row['uname'];
                    }
                    $row['date'] = $dbconn->DBTimeStamp(time());
                    $row['state'] = _USER_STATE_ACTIVE;
                    echo "<br />Adding User: [<b>$row[uname]</b>]";
                    $newuid = xarModAPIFunc('roles', 'admin', 'create', $row);
                    if ($newuid === 0) {
                        $aUser = xarModAPIFunc('roles','user','get', array('uname' => $row['uname']));
                        $newuid = $aUser['uid'];
                        echo " -- user already exists with uid: [<b>$newuid</b>]";
                    } else {
                        echo " -- uid: [<b>$newuid</b>]";
                    }

                    $userid[$uid] = $newuid;

                    break;

                case 'blogs':
                    $bid = $row['bid'];
                    unset($row['bid']);

                    $mt['blogs'][$bid] = $row;
                    break;

                case 'cats':
                    $catid = $row['catid'];
                    unset($row['catid']);

                    $mt['blogs'][$row['bid']]['categories'][$catid] = $row;
                    break;

                case 'entries':
                    $aid = $row['aid'];
                    unset($row['aid']);
                    $row['pubdate'] = $result->UnixTimeStamp($row['pubdate']);
                    if (empty($row['catid'])) $row['catid'] = 1; // default to the first category
                    $mt['blogs'][$row['bid']]['categories'][$row['catid']]['articles'][$aid] = $row;
                    break;

                case 'comments':
                    $cid = $row['cid'];
                    unset($row['cid']);

                    $row['cdate'] = $result->UnixTimeStamp($row['cdate']);
                    $signature = "\n--\n$row[author]\n$row[email]\n$row[url]";
                    $test1 = xarModAPIFunc('roles','user','get', array('uname' => $row['author']));
                    $test2 = xarModAPIFunc('roles','user','get', array('name' => $row['author']));
                    echo "<br />Working on Comment $cid: by author: $row[author]";
                    if (!is_array($test1) && !is_array($test2)) {
                        if (!is_numeric($row['uid'])) {
                            $u = xarModAPIFunc('roles','user','get',array('uname'=>'anonymous'));
                            $row['uid'] = $u['uid'];
                            $row['body'] .= $signature;
                        }
                    } else {
                        if (is_array($test1)) {
                            $row['uid'] = $test1['uid'];
                        } else {
                            $row['uid'] = $test2['uid'];
                        }
                    }
                    echo " -- using uid: [<b>$row[uid]</b>]";
                    foreach ($mt['blogs'][$row['bid']]['categories'] as $catid => $category) {
                        if (is_array($category['articles'])) {
                            foreach ($category['articles'] as $aid => $article) {
                                if ($aid == $row['aid']) {
                                    $mt['blogs'][$row['bid']]['categories'][$catid]['articles'][$aid]['comments'][$cid] = $row;
                                    $mt['blogs'][$row['bid']]['categories'][$catid]['articles'][$aid]['comments'][$cid]['title'] = $article['title'];
                                }
                            }
                        }
                    }
                    break;

            }
            $result->MoveNext();
        }
        echo '</blockquote>';
    }


    echo "<br /><br />";
    foreach ($mt as $key => $value) {
        switch ($key) {
            case 'users':
                echo "<br />User's Stored:";
                if (count($value)) {
                    foreach ($value as $uid => $user) {
                        $article_total = count($user['articles']);
                        echo "<br />User: [<strong><em>$user[uname]</em></strong>] / RealName: [<strong><em>$user[realname]</em></strong>]";
                    }
                }
                break;
            case 'blogs':
                if (count($value)) {
                    echo "<br /><br />Blogs stored:";
                    foreach ($value as $bid => $blog) {
                        echo "<br />Blog: <strong>$blog[name]</name></strong><blockquote>";
                        if (count($blog)) {
                            foreach ($blog['categories'] as $catid => $category) {
                                $article_total = count($category['articles']);
                                echo "<br />Category: [<strong><em>$category[name]</em></strong>] has [<strong><em>$article_total</em></strong>] articles";
                                if ($article_total) {
                                    echo '<blockquote style="font-size: 9pt;">';
                                    foreach ($category['articles'] as $aid => $article) {
                                        $comment_total = count($article['comments']);
                                        echo '<br />Article "<em><strong>'.$article['title'].'</strong></em>" has [<strong><em>'.$comment_total.'</em></strong>] comments';
                                    }
                                    echo '</blockquote>';
                                }
                            }
                        }
                        echo '</blockquote>';
                    }
                }
                break;
        }
    }

    echo "<br/><br/><HR><br /><br />";

    echo "<br />Adding Publication Type: [<b>blog</b>]";

    $pubtype_added = false;
    $pubtypes = xarModAPIFunc('articles','user','getpubtypes');

    foreach ($pubtypes as $pubid => $pubtype) {
        if ($pubtype['name'] == 'blog') {
            echo " -- pubtype already exists with pubid: $pubid";
            $pubtype_added = true;
        }
    }

    if (!$pubtype_added) {
        $pubid = xarModAPIFunc('articles','admin','createpubtype',
                         array('name'   => 'blog',
                               'descr'  => 'Blogging Publications',
                               'config' => $pubtype_config));
    }

    $cat_list = xarModAPIFunc('categories','user','getcat',array());
    $roots = array();

    if (is_numeric($pubid) && $pubid) {

        foreach ($mt['blogs'] as $bid => $blog) {

            echo "<br />Adding Blog: [<strong>$blog[name]</strong>]";
            if (($bid = find_cat($blog['name'], $cat_list)) === FALSE) {
                if (empty($blog['description'])) {
                    $blog['description'] = $blog['name'];
                }
                $bid = xarModAPIFunc('categories','admin','create',
                               array('name'         => $blog['name'],
                                     'description'  => $blog['description'],
                                     'parent_id'    => 0));
            } else {
                echo " -- already exists as bid: $bid";
            }

            if (is_numeric($bid) && $bid)
                $roots[] = $bid;

            if ((is_numeric($bid) && $bid) && is_array($blog['categories'])) {
                echo '<blockquote>';
                foreach ($blog['categories'] as $catid => $category) {
                    echo "<br />Adding Category [<b>$category[name]</b>] to blog with parent id: $bid";
                    if ( ($new_catid = find_cat($category['name'], $cat_list)) == FALSE) {

                        if (empty($category['description']))
                            $category['description'] = $category['name'];

                        $new_catid = xarModAPIFunc('categories','admin','create',
                                             array('name'        => $category['name'],
                                                   'description' => $category['description'],
                                                   'parent_id'   => $bid));
                    } else {
                        echo " -- already exists as catid: $new_catid";
                    }
                    if (is_numeric($new_catid) && $new_catid) {
                        foreach ($category['articles'] as $aid => $article) {
                            echo "<br />Adding Article: <b><i>$article[title]</i></b>";
                            $new_article['title']       = $article['title'];
                            $new_article['summary']     = $article['summary'];
                            $new_article['body']        = $article['body'];
                            $new_article['status']      = $article_status_map[$article['status']];
                            $new_article['pubdate']     = $article['pubdate'];
                            $new_article['ptid']        = $pubid;
                            $new_article['authorid']    = $userid[$article['uid']];
                            $new_article['cids']        = $new_catid;

                            $new_aid = xarModAPIFunc('articles','admin','create',$new_article);

                            if ($new_aid !== FALSE && $new_aid) {
                                foreach ($article['comments'] as $cid => $comment) {
                                    echo "<br />Attaching comment to article: $new_aid from user: $comment[author] with uid: $comment[uid]";
                                    $comment['modid']    = xarModGetIDFromName('articles');
                                    $comment['objectid'] = $new_aid;
                                    $comment['comment']  = $comment['body'];
                                    unset($comment['cid']);
                                    $comment['author']   = $comment['uid'];
                                    $comment['date']     = $comment['cdate'];
                                    $new_cid = xarModAPIFunc('comments','user','add',$comment);
                                }
                            }
                        }
                    } else {
                        echo '<br /><span style="color: red">Unable to add Blog Category: [<stong><em>'.$category['name'].'</em></strong>]</span>';
                    }
                }
                echo '</blockquote>';
            } else {
                echo '<br /><span style="color: red">Unable to add Blog: [<stong><em>'.$blog['name'].'</em></strong>]</span>';
            }
        }
        if (xarExceptionMajor() != XAR_NO_EXCEPTION) {
            xarExceptionHandled();
            xarExceptionFree();
        }
        echo "<br />Adding Publication settings for pubid: $pubid -- ";

        if (!is_string(xarModGetVar('articles', 'settings.'.$pubid))) {
            echo "settings created";
            xarModSetVar('articles', 'settings.'.$pubid, serialize($pubtype_settings));
        } else {
            echo "using previously created settings";
        }

        echo "<br />Adding Publication setting: number_of_categories -- ";
        $num_cats = xarModGetVar('articles', 'number_of_categories.'.$pubid);
        $new_cat_total = $num_cats + count($roots);
        if (!is_numeric($num_cats) || !$num_cats) {
            $num_cats = 0;
        }
        echo "previous value of: [<b>$num_cats</b>] - New value: [<b>$new_cat_total</b>]";

        echo "<br />Adding Publication setting: mastercids -- ";
        $mastercids = xarModGetVar('articles','mastercids.'.$pubid);
        echo "previous value of: [<b>$mastercids</b>] - ";
        $mastercids .= $num_cats ? ';'.implode(';',$roots) : implode(';',$roots);
        echo "new value: [<b>$mastercids</b>]";

        $num_cats += count($roots);

        xarModSetVar('articles', 'number_of_categories.'.$pubid, $new_cat_total);
        xarModSetVar('articles', 'mastercids.'.$pubid, $mastercids);
    } else {
        echo '<br /><span style="color: red">Unable to add Blog pubtype</span>';
    }
?>
