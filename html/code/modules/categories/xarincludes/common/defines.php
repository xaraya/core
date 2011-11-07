<?php
/**
 * Categories Module
 *
 * @package modules
 * @subpackage categories module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/147.html
 *
 * @author Rabbitt (aka Carl P. Corliss)
 */

if (defined('_COM_SORT_ASC')) {
    return;
}

// the following two defines specify the sorting direction which
// can be either ascending or descending
define('_CAT_SORT_ASC', 1);
define('_CAT_SORT_DESC',2);

// the following four defines specify the sort order which can be any of
// the following: author, date, topic, lineage
// TODO: Add Rank sorting
define ('_CAT_SORTBY_AUTHOR', 1);
define ('_CAT_SORTBY_DATE', 2);
define ('_CAT_SORTBY_THREAD', 3);
define ('_CAT_SORTBY_TOPIC', 4);

// the following define is for $cid when
// you want to retrieve all comments as opposed
// to entering in a real comment id and getting
// just that specific comment
define('_CAT_RETRIEVE_ALL', 1);
define('_CAT_VIEW_FLAT','flat');
define('_CAT_VIEW_NESTED','nested');
define('_CAT_VIEW_THREADED','threaded');

// the following defines are for the $depth variable
// the -1 (FULL_TREE) tells it to get the full
// tree/branch and the the 0 (TREE_LEAF) tells the function
// to acquire just that specific leaf on the tree.
//
define('_CAT_FULL_TREE',((int) '-1'));
define('_CAT_TREE_LEAF',1);

// Maximum allowable branch depth
//define('_CAT_MAX_DEPTH',20);

// Status of comment nodes
define('_CAT_STATUS_OFF',1);
define('_CAT_STATUS_ON',2);
define('_CAT_STATUS_ROOT_NODE',3);

?>
