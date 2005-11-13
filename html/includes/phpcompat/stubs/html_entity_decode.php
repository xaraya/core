<?php
/**
 * File: $Id:
 * 
 * Stub html_entity_decode
 *
 * @package PHP Version Compatibility Library
 * @copyright (C) 2004 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Jo Dalle Nogare
 */

/**
 * Stub for the html_entity_decode() function
 * 
 * @see _html_entity_decode()
 * @internal quote_style not supported and defaults to ENT_COMPAT
 */

function html_entity_decode($string)
{
    require_once 'functions/_html_entity_decode.php';
    return _html_entity_decode($string);
}

?>
