<?php
// $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Marco Canini
// Purpose of file:
// ----------------------------------------------------------------------

//include_once('/modules/translations/class/TPLParser.php');
include_once('../TPLParser.php');
define('TPLPARSERDEBUG','1');

$p = new TPLParser();
$p->parse('parser-test.xd');

//var_dump($p);
?>