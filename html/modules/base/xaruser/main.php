<?php

/**
 * File: $Id$
 *
 * Base User GUI functions
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @author Paul Rosania
 * @todo decide whether to use this file or delete it
 */
function base_user_main()
{

// Security Check
    if(!xarSecurityCheck('ViewBase')) return;

    xarTplSetPageTitle(xarML('Welcome'));

    //return the output
    return array();
}

?>