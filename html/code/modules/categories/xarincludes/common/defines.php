<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya Content Management System
// Copyright (C) 2001 by the Xaraya Development Team.
// http://www.xaraya.com/
// ----------------------------------------------------------------------
// LICENSE
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// ----------------------------------------------------------------------
// Original Author of file:  Rabbitt (aka Carl P. Corliss)
// Purpose of file:  Defines for the comments API
// ----------------------------------------------------------------------

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
