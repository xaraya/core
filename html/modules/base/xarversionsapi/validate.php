<?php

/**
 * File: $Id$
 *
 * Base User version management functions
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @author Jason Judge
 * @todo none
 */


/**
 * Validate the format of a version number against some rule.
 *
 * @author Jason Judge
 * @param $args['TODO'] TODO
 * @returns result of validation: true or false
 * @return number indicating which parameter is the latest version
 */
function base_versionsapi_validate($args)
{
    extract($args);

    // TODO:
    // Validate the version numeber passed in against a rule or set
    // of rules defining version number formats.
    // Haven't quite decided if the rules are built up from sub-rules (e.g.
    // strict numeric yes/no, separator='.', fixed number of levels = n
    // etc.) or from a few full rules (e.g. version type = theme/module/
    // block/legal etc.)
    // My gut feeling is the bigger rules will be easier to handle, to
    // extend when needed and to pass bwteen functions.

    // Rules could include:
    // - numeric only
    // - strict number of levels
    // - implied '0' on empty levels allowed

    return true;
}

?>
