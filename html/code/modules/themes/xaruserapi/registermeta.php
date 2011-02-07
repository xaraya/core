<?php
/**
 * Xaraya Meta class library
 *
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
**/
/**
 * Register function
 *
 * Register meta data in queue for later rendering
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @params array  $args array of optional parameters<br/>
 *         string $args[type] the type of meta tag, either name or http-equiv, required<br/>
 *         string $args[value] the value of the type, eg (author, rating, refresh, etc..), required<br/>
 *         string $args[content] the meta content, required<br/>
 *         string $args[lang] the ISO 639-1 language code for the content, optional<br/>
 *         string $args[dir] the text direction of the content (ltr|rtl), optional<br/>
 *         string $args[scheme] the scheme used to interpret the content, optional
 * @throws none
 * @return bool true on success
**/ 
function themes_userapi_registermeta($args)
{
    sys::import('modules.themes.class.xarmeta');
    $meta = xarMeta::getInstance();
    return $meta->register($args);           
}
?>