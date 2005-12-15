<?php // $Id$
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
// Purpose of file: Upgrade from Postnuke .71 -> .8
// ----------------------------------------------------------------------

global $dbconn, $pntable, $prefix;


// New table for language information
$dbconn->Execute("CREATE TABLE ".$pntables['languages']." (
                  pn_lid int(11) unsigned NOT NULL auto_increment,
                  pn_code varchar(25) NOT NULL default '',
                  pn_rss_code varchar(25) NOT NULL default '',
                  pn_name varchar(255) NOT NULL default '',
                  PRIMARY KEY (pn_lid),
                  KEY pn_lid (pn_lid),
                  KEY pn_name (pn_name),
                  KEY pn_code (pn_code)
                  )");

$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('1', 'ara', 'ar', 'Arabic')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('3', 'zho', '', 'Chinese')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('2', 'bul', 'bg', 'Bulgarian')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('4', 'cat', 'ca', 'Catalan')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('5', 'ces', 'cs', 'Czech')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('6', 'cro', 'hr', 'Croatian')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('7', 'dan', 'da', 'Danish')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('8', 'nld', 'nl', 'Nederlands')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('9', 'eng', 'en', 'English')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('10', 'epo', '', 'Esperanto')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('11', 'est', '', 'Estonian')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('12', 'fin', 'fi', 'Finnish')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('13', 'fra', 'fr', 'Français')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('14', 'deu', 'de', 'Deutsch')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('15', 'ell', 'el', 'Greek')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('16', 'heb', '', 'Hebrew')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('17', 'hun', 'hu', 'Hungarian')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('18', 'isl', 'is', 'Icelandic')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('19', 'ind', 'in', 'Indonesia')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('20', 'ita', 'it', 'Italiano')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('21', 'jpn', 'ja', 'Japanese')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('22', 'kor', 'ko', 'Korean')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('23', 'lav', '', 'Latvian')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('24', 'lit', '', 'Lithuanian')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('25', 'mas', '', 'Melayu')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('26', 'nor', 'no', 'Norsk')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('27', 'pol', 'pl', 'Polish')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('28', 'por', 'pt', 'Portuguese')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('29', 'ron', 'ro', 'Romanian')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('30', 'rus', 'ru', 'Russian')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('31', 'slv', 'sl', 'Slovenski')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('33', 'spa', 'es', 'Español')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('34', 'swe', 'sv', 'Svenska')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('35', 'tha', 'th', 'Thai')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('36', 'tur', 'tr', 'Turkish')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('37', 'ukr', 'uk', 'Ukranian')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('38', 'yid', '', 'Yiddish')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('39', 'x_all', '', 'All')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('40', 'x_brazilian_portuguese', '', 'Brazilian Portuguese')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('41', 'x_klingon', '', 'Klingon')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");
$result = $dbconn->Execute("INSERT INTO ".$pntables['languages']." VALUES ('42', 'x_rus_koi8r', '', 'Russian KOI8-R')") or die ("<b>"._NOTUPDATED.$pntables['languages']."</b>");

?>