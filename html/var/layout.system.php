<?php
/**
 * Directory Layout Configuration File 
 *
 * @package core
 * @subpackage core
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 */

/*
 * These values let you define the layout of Xaraya's root
 * directory and three main components
 *
 * For a standard installation ("out of the box")
 * use these values
 */
/*
$systemConfiguration = array(
    'rootDir' => "../";            // Relative to the calling script
    'webDir'  => "html/";          // Relative to the root directory
    'libDir'  => "lib/";           // Relative to the root directory
    'codeDir' => "html/code/";     // Relative to the root directory
);
*/
/*
 * For a shared hosting environment ("flat install")
 * uncomment these values
 */
$systemConfiguration = array(
    'rootDir' => "",               // Relative to the calling script
    'webDir'  => "",               // Relative to the root directory
    'libDir'  => "lib/",           // Relative to the root directory
    'codeDir' => "code/",          // Relative to the root directory
);

/*
 * This value is used to change the default behavior of the
 * xarServer::getBaseURL() and xarModURL() functions to allow you
 * to use things like Apache's mod_rewrite to shorten your
 * URLs even further then Short URL's allows, for example
 * completely removing the "index.php" from your site's URLs
 *
 * Comment them out to use Xaraya's built-in/auto values
 */
// 1. When you want to use some alternate URI path to your site
//$systemConfiguration['BaseURI'] = '/test';
// 2. When you want to use some alternate script file for your site
//$systemConfiguration['BaseModURL'] = 'index2.php';
// 3. When you want to use URLs like http://mysite.com/news/123
//$systemConfiguration['BaseURI'] = '';
//$systemConfiguration['BaseModURL'] = '';

/*
 * This value is used to change the default path of the
 * var directory.
 *
 * Comment them out to use Xaraya's built-in/auto values
 */
//$systemConfiguration['varDir'] = './var';
