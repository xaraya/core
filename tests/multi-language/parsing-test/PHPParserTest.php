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

//include_once('/modules/translations/class/PHPParser.php');
include_once('../PHPParser.php');
define('PHPPARSERDEBUG','1');

$p = new PHPParser();
$p->parse('parser-test.php');

//var_dump($p);
?>