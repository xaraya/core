<?php
/**
 * Handle css tag
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * Handler for the xar:style tag
 *
 * Attributes:
 * scope     - [common|(theme)|module|block|property] - where to look for it
 * method    - [(link)|import|embed]    - what method do we use to include the style info
 * alternatedir - CDATA                 - optional alternate folder to look in
 * base         - CDATA                 - base folder to look in
 * file       - CDATA                   - basename of the style file to include
 * fileext    - CDATA                   - file extension to use
 * source     - CDATA                   - source code to embed
 * alternate - [true|(false)]           - this style is an alternative to the main styling?
 * rel       - CDATA                    - the rel value
 * type      - (text/css)               - what content is to be expected
 * media     - [all|(screen)|print|aural|handeld|projection|tv|tty|braille]     
 *             for which media are we including style info (space separated list)
 * title     - ""                       - what title can we attach to the styling, if any
 * condition - [IE|(IE5)|(!IE6)|(lt IE7)] - encase in conditional comment 
 *             (for serving to ie-win of various flavours)
 * module    - CDATA                     - for which module are we including style info
 * property  - CDATA                     - for which property are we including style info
 *
 * <xar:style scope="common" method="link" alternatedir="mystyle" base="style" file="style" fileext="css" alternate="false" rel="stylesheet" type="text/css" media="screen" title="Stylesheet Title"/>
 * <xar:style scope="theme" method="import" alternatedir="mystyle" base="style" file="style" fileext="css" alternate="false" rel="stylesheet" type="text/css" media="screen"/>
 * <xar:style scope="module" method="embed" type="text/css" media="screen" source="body { margin: 0; }"/>
 * <xar:style scope="block" method="link" alternatedir="mydir" file="style" fileext="css" alternate="false" rel="stylesheet" type="text/css" media="screen" module="blockmodule" title="Block Stylesheet"/>
 * <xar:style scope="property" method="import" alternatedir="mydir" file="style" fileext="css" alternate="false" rel="stylesheet" type="text/css" media="screen" property="propertyname"/>
 */
/**
 * Handle css tag
 *
 * @author andyv <andyv@xaraya.com>
 * @author Chris Powis <crisp@xaraya.com>
 * @access public
 * @params array  $args array of optional parameters<br/>
 *         string $args[scope] scope of style, one of common!theme(default)|module|block|property<br/>
 *         string $args[method] style method, one of link(default)|import|embed<br/>
 *         string $args[alternatedir] alternative base folder to look in, falling back to...<br/>
 *         string $args[base] base folder to look in, default depends on scope<br/>
 *         string $args[file] name of file required for link or embed methods<br/>
 *         string $args[filext] extension to use for file(s), optional, default "css"<br/>
 *         string $args[source] source code, required for embed method, default null<br/>
 *         string $args[alternate] switch to set rel="alternate stylesheet", optional true|false(default)<br/>
 *         string $args[rel] rel attribute, optional, default "stylesheet"<br/>
 *         string $args[type] link/style type attribute, optional, default "text/css"<br/>
 *         string $args[media] media attribute, optional, default "screen"<br/>
 *         string $args[title] title attribute, optional, default ""<br/>
 *         string $args[condition] conditionals for ie browser, optional, default null<br/>
 *         string $args[module] module for module|block scope, optional, default current module<br/>
 *         string $args[property] property required for property scope 
 * @throws none
 * @return boolean true on success
 */

function themes_userapi_register(Array $args=array())
{
    sys::import('modules.themes.class.xarcss');
    $css = xarCSS::getInstance();
    return $css->register($args);
}
?>