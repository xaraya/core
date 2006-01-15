<?php
/**
 * File: $Id:
 * 
 * Function html_entity_decode
 * 
 * @package PHP Version Compatibility Library
 * @copyright (C) 2004 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Jo Dalle Nogare
 */

/**
 * Mimics the html_entity_decode() function introduced in PHP 4.3.0
 * 
 * @link http://www.php.net/manual/function.html_entity_decode.php
 */

function _html_entity_decode($string)
{
    $trans = get_html_translation_table(HTML_ENTITIES);
    $trans = array_flip($trans);
    $contents= strtr($string, $trans);

    return $contents;
}

?>
