<?php
/*
 * These values let you define the layout of Xaraya's root
 * directory and three main components
 *
 * For a default installation ("out of the box")
 * use these values
 */
$systemConfiguration['rootDir'] = "../";
$systemConfiguration['webDir']  = "html/";
$systemConfiguration['libDir']  = "lib/";
$systemConfiguration['codeDir'] = "code/";
/*
 * For a shared hosting environment ("flat install")
 * uncomment these values
 */
//$systemConfiguration['rootDir'] = "";
//$systemConfiguration['webDir']  = "";
//$systemConfiguration['libDir']  = "lib/";
//$systemConfiguration['codeDir'] = "";

/*
 * This value is used to change the default behavior of the
 * xarServerGetBaseURL() and xarModURL() functions to allow you
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
?>