<?php // File: $Id$ $Name$
// ----------------------------------------------------------------------
// PostNuke Content Management System
// Copyright (C) 2001 by the PostNuke Development Team.
// http://www.postnuke.com/
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
// Original Author of file:  PostNuke Development Team
// Purpose of file: Downgrade from PHPNuke 5.4 to PHPNuke 5.3.1
// ----------------------------------------------------------------------


// Users Table Alteration to add forums support

mysql_query("ALTER TABLE $user_prefix"._users." DROP user_posts");
mysql_query("ALTER TABLE $user_prefix"._users." DROP user_attachsig");
mysql_query("ALTER TABLE $user_prefix"._users." DROP user_rank");
mysql_query("ALTER TABLE $user_prefix"._users." DROP user_level");

// Author's Table Alteration

mysql_query("ALTER TABLE $prefix"._authors." DROP radminforum");
mysql_query("ALTER TABLE $prefix"._authors." DROP radmincontent");
mysql_query("ALTER TABLE $prefix"._authors." DROP radminency");

// Links Table Alteration
mysql_query("ALTER TABLE $prefix"._links_categories." DROP parentid");

echo "phpBB Tables intact.";

?>