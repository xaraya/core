<?php
/**
 * File: $Id$
 * 
 * Xaraya Web Interface Entry Point
 * 
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Page/Block Caching
 * @author mikespub
 * @author jsb
 */

/**
 * Initialise the page caching options
 *
 * @returns mixed
 * @return true on success, exit if session-less page caching finds a hit
 */
function xarPageCache_init($args = array())
{
// TODO: clean up all these globals and put them e.g. into a single array
    global $xarPage_cacheTime;
    global $xarPage_cacheDisplay;
    global $xarPage_cacheShowTime;
    global $xarPage_cacheExpireHeader;
    global $xarPage_cacheGroups;
    global $xarPage_cacheHookedOnly;
    global $xarPage_sessionLess;
    global $xarPage_autoCachePeriod;

    $xarPage_cacheTime = isset($args['Page.TimeExpiration']) ?
        $args['Page.TimeExpiration'] : 1800;
    $xarPage_cacheDisplay = isset($args['Page.DisplayView']) ?
        $args['Page.DisplayView'] : 0;
    $xarPage_cacheShowTime = isset($args['Page.ShowTime']) ?
        $args['Page.ShowTime'] : 1;
    $xarPage_cacheExpireHeader = isset($args['Page.ExpireHeader']) ?
        $args['Page.ExpireHeader'] : 1;
    $xarPage_cacheGroups = isset($args['Page.CacheGroups']) ?
        $args['Page.CacheGroups'] : '';
    $xarPage_cacheHookedOnly = isset($args['Page.HookedOnly']) ?
        $args['Page.HookedOnly'] : 0;
    $xarPage_sessionLess = isset($args['Page.SessionLess']) ?
        $args['Page.SessionLess'] : '';
    $xarPage_autoCachePeriod = isset($args['AutoCache.Period']) ?
        $args['AutoCache.Period'] : 0;

    // Note : we may already exit here if session-less page caching is enabled
    xarPageCache_sessionLess();

/*
    global $xarOutput_cacheCollection;
    global $xarPage_cacheStorage;

    $storage = !empty($args['Page.CacheStorage']) ?
        $args['Page.CacheStorage'] : 'filesystem';
    $logfile = !empty($args['Page.LogFile']) ?
        $args['Page.LogFile'] : null;
    // Note: make sure this isn't used before core loading if we use database storage
    $xarPage_cacheStorage =& xarCache_getStorage(array('storage'  => $storage,
                                                       'type'     => 'page',
                                                       'cachedir' => $xarOutput_cacheCollection,
                                                       'expire'   => $xarPage_cacheTime,
                                                       'logfile'  => $logfile));
    if (empty($xarPage_cacheStorage)) {
        return false;
    }
*/

    return true;
}

/**
 * check if the content of a page is available in cache or not
 *
 * @access public
 * @param key the key identifying the particular cache you want to access
 * @param name the name of the page in that particular cache
 * @returns bool
 * @return true if the page is available in cache, false if not
 */
function xarPageIsCached($cacheKey, $name = '')
{
    global $xarOutput_cacheCollection,
           $xarOutput_cacheTheme,
           $xarPage_cacheTime,
           $xarPage_cacheDisplay,
           $xarPage_cacheCode,
           $xarPage_cacheGroups;

    $xarTpl_themeDir = xarTplGetThemeDir();

    $page = xarServerGetVar('HTTP_HOST') . $xarTpl_themeDir .
            xarUserGetNavigationLocale();

    // add user groups as a factor if necessary
    // Note : we don't share the cache between groups or with anonymous here
    if (!empty($xarPage_cacheGroups) && xarUserIsLoggedIn()) {
        $gidlist = xarCache_getParents();
        $page .= join(';',$gidlist);
    }

    $page .= xarServerGetVar('REQUEST_URI');
    $param = xarServerGetVar('QUERY_STRING');
    if (!empty($param)) {
        $page .= '?' . $param;
    }
    // use this global instead of $cache_file so we can cache several things
    // based on different $cacheKey (and $name if necessary) in one page request
    // - e.g. for module and block caching
    $xarPage_cacheCode = md5($page);

    // CHECKME: use $name for something someday ?
    $cache_file = "$xarOutput_cacheCollection/page/$cacheKey-$xarPage_cacheCode.php";

    if (// if this page is a user type page AND
        strpos($cacheKey, '-user-') &&
        // (display views can be cached OR it is not a display view) AND
        (($xarPage_cacheDisplay == 1) || (!strpos($cacheKey, '-display'))) &&
        // the http request is a GET OR a HEAD AND
        (xarServerGetVar('REQUEST_METHOD') == 'GET' || xarServerGetVar('REQUEST_METHOD') == 'HEAD') &&
        // (we're caching the output of all themes OR this is the theme we're caching) AND
        (empty($xarOutput_cacheTheme) ||
         strpos($xarTpl_themeDir, $xarOutput_cacheTheme)) &&
        // the file is present AND
        file_exists($cache_file) &&
        // the file has something in it AND
        filesize($cache_file) > 0 &&
        // (cached pages don't expire OR this file hasn't expired yet) AND
        ($xarPage_cacheTime == 0 ||
         filemtime($cache_file) > time() - $xarPage_cacheTime) &&
        // the current user is eligible for receiving cached pages...
        xarPage_checkUserCaching()) {

        // create another copy for session-less page caching if necessary
        if (!empty($GLOBALS['xarPage_cacheNoSession'])) {
            $cacheKey = 'static';
            $cacheCode = md5($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
            $cache_file2 = "$xarOutput_cacheCollection/page/$cacheKey-$cacheCode.php";
        // Note that if we get here, the first-time visitor will receive a session cookie,
        // so he will no longer benefit from this himself ;-)
            @copy($cache_file, $cache_file2);
        }

        // this may already exit if we have a 304 Not Modified
        $modtime = filemtime($cache_file);
        xarPageCache_sendHeaders($modtime);

        return true;
    } else {
        return false;
    }
}

/**
 * get the content of a cached page
 *
 * @access public
 * @param key the key identifying the particular cache you want to access
 * @param name the name of the page in that particular cache
 * @returns mixed
 * @return content of the page, or void if page isn't cached
 */
function xarPageGetCached($cacheKey, $name = '')
{
    global $xarOutput_cacheCollection, $xarPage_cacheCode;

    // CHECKME: use $name for something someday ?
    $cache_file = "$xarOutput_cacheCollection/page/$cacheKey-$xarPage_cacheCode.php";
    @readfile($cache_file);

    xarCache_CleanCached('Page');
}

/**
 * set the content of a cached page
 *
 * @access public
 * @param string $cacheKey the key identifying the particular cache you want to
 *                         access
 * @param string $name     the name of the page in that particular cache
 * @param string $value    value the new content for that page
 * @returns void
 */
function xarPageSetCached($cacheKey, $name, $value)
{
    global $xarOutput_cacheCollection,
           $xarOutput_cacheTheme,
           $xarPage_cacheTime,
           $xarPage_cacheDisplay,
           $xarPage_cacheShowTime,
           $xarPage_cacheHookedOnly,
           $xarPage_cacheCode;

    $xarTpl_themeDir = xarTplGetThemeDir();
    
    if (xarCore_IsCached('Page.Caching', 'nocache')) { return; }
    
    if ($xarPage_cacheHookedOnly) {
        $modName = substr($cacheKey, 0, strpos($cacheKey, '-'));
        if (!xarModIsHooked('xarcachemanager', $modName)) { return; }
    }

    // CHECKME: use $name for something someday ?
    $cache_file = "$xarOutput_cacheCollection/page/$cacheKey-$xarPage_cacheCode.php";

    if (// if this page is a user type page AND
        strpos($cacheKey, '-user-') &&
        // (display views can be cached OR it is not a display view) AND
        (($xarPage_cacheDisplay == 1) || (!strpos($cacheKey, '-display'))) &&
        // the http request is a GET OR a HEAD AND
        (xarServerGetVar('REQUEST_METHOD') == 'GET' || xarServerGetVar('REQUEST_METHOD') == 'HEAD') &&
        // (we're caching the output of all themes OR this is the theme we're caching) AND
        (empty($xarOutput_cacheTheme) ||
         strpos($xarTpl_themeDir, $xarOutput_cacheTheme)) &&
        // ((the cache file doesn't exist) OR (expires AND has expired)) AND
        (!file_exists($cache_file) ||
         ($xarPage_cacheTime != 0 &&
          filemtime($cache_file) < time() - $xarPage_cacheTime)) &&
        // the current user's page views are eligible for caching AND
        xarPage_checkUserCaching() &&
        // the cache collection directory hasn't reached its size limit...
        !xarCache_SizeLimit($xarOutput_cacheCollection, 'Page')) {
        
        // if request, modify the end of the file with a time stamp
        if ($xarPage_cacheShowTime == 1) {
            $now = xarML('Last updated on #(1)',
                         strftime('%a, %d %B %Y %H:%M:%S %Z', time()));
            $value = preg_replace('#</body>#',
                                  // TODO: set this up to be templated
                                  '<div class="xar-sub" style="text-align: center; padding: 8px; ">'.$now.'</div></body>',
                                  $value);
        }

        xarOutputSetCached($cacheKey, $cache_file, 'Page', $value);

        $modtime = filemtime($cache_file);
        xarPageCache_sendHeaders($modtime);

    }
}

/**
 * aliased depricated function
 */
function xarPageDelCached($cacheKey, $name)
{
    xarOutputDelCached($cacheKey, $name);
}

/**
 * aliased depricated function
 */
function xarPageFlushCached($cacheKey)
{
    xarOutputFlushCached($cacheKey);
}

/**
 * check if the user can benefit from page caching
 *
 * @access private
 * @return bool
 * @todo avoid DB lookup by passing group via cookies ?
 * @todo Note : don't do this if admins get cached too :)
 */
function xarPage_checkUserCaching()
{
    global $xarPage_cacheGroups;

    if (!xarUserIsLoggedIn()) {
        // always allow caching for anonymous users
        return true;
    } elseif (empty($xarPage_cacheGroups)) {
        // if no other cache groups are defined
        return false;
    }

    $gidlist = xarCache_getParents();

    $groups = explode(';',$xarPage_cacheGroups);
    foreach ($groups as $groupid) {
        if (in_array($groupid,$gidlist)) {
            return true;
        }
    }
    return false;
}

/**
 * Log the HIT / MISS status of URLs requested by first-time visitors
 *
 * @access private
 * @return void
 */
function xarPage_autoCacheLogStatus($status = 'MISS')
{
    if (!empty($_SERVER['REQUEST_METHOD']) &&
        ($_SERVER['REQUEST_METHOD'] == 'GET' || $_SERVER['REQUEST_METHOD'] == 'HEAD') &&
    // the URL is one of the candidates for session-less caching
    // TODO: make compatible with IIS and https (cfr. xarServer.php)
        !empty($_SERVER['HTTP_HOST']) &&
        !empty($_SERVER['REQUEST_URI'])) {

        $time = time();
        $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $addr = !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '-';
        //$ref = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '-';

        // cfr. xarCache_init()
        global $xarOutput_cacheCollection;
        global $xarPage_autoCachePeriod;

        if (!empty($xarPage_autoCachePeriod) &&
            filemtime($xarOutput_cacheCollection.'/autocache.start') < time() - $xarPage_autoCachePeriod) {
            @touch($xarOutput_cacheCollection.'/autocache.start');

            $xarVarDir = xarCache_getVarDirPath();

            // re-calculate Page.SessionLess based on autocache.log and save in config.caching.php
            $cachingConfigFile = $xarVarDir.'/cache/config.caching.php';
            if (file_exists($cachingConfigFile) &&
                is_writable($cachingConfigFile)) {

                include $cachingConfigFile;
                if (!empty($cachingConfiguration['AutoCache.MaxPages']) &&
                    file_exists($xarOutput_cacheCollection.'/autocache.log') &&
                    filesize($xarOutput_cacheCollection.'/autocache.log') > 0) {

                    $logs = @file($xarOutput_cacheCollection.'/autocache.log');
                    $autocacheproposed = array();
                    if (!empty($cachingConfiguration['AutoCache.KeepStats'])) {
                        $autocachestats = array();
                        $autocachefirstseen = array();
                        $autocachelastseen = array();
                    }
                    foreach ($logs as $entry) {
                        if (empty($entry)) continue;
                        list($time,$status,$addr,$url) = explode(' ',$entry);
                        $url = trim($url);
                        if (!isset($autocacheproposed[$url])) $autocacheproposed[$url] = 0;
                        $autocacheproposed[$url]++;
                        if (!empty($cachingConfiguration['AutoCache.KeepStats'])) {
                            if (!isset($autocachestats[$url])) $autocachestats[$url] = array('HIT' => 0,
                                                                                             'MISS' => 0);
                            $autocachestats[$url][$status]++;
                            if (!isset($autocachefirstseen[$url])) $autocachefirstseen[$url] = $time;
                            $autocachelastseen[$url] = $time;
                        }
                    }
                    unset($logs);
                    // check that all required URLs are included
                    if (!empty($cachingConfiguration['AutoCache.Include'])) {
                        foreach ($cachingConfiguration['AutoCache.Include'] as $url) {
                            if (!isset($autocacheproposed[$url]) ||
                                $autocacheproposed[$url] < $cachingConfiguration['AutoCache.Threshold'])
                                $autocacheproposed[$url] = 99999999;
                        }
                    }
                    // check that all forbidden URLs are excluded
                    if (!empty($cachingConfiguration['AutoCache.Exclude'])) {
                        foreach ($cachingConfiguration['AutoCache.Exclude'] as $url) {
                            if (isset($autocacheproposed[$url])) unset($autocacheproposed[$url]);
                        }
                    }
                    // sort descending by count
                    arsort($autocacheproposed, SORT_NUMERIC);
                    // build the list of URLs proposed for session-less caching
                    $checkurls = array();
                    foreach ($autocacheproposed as $url => $count) {
                        if (count($checkurls) >= $cachingConfiguration['AutoCache.MaxPages'] ||
                            $count < $cachingConfiguration['AutoCache.Threshold']) {
                            break;
                        }
                    // TODO: check against base URL ? (+ how to determine that without core)
                        $checkurls[] = $url;
                    }
                    sort($checkurls);
                    sort($cachingConfiguration['Page.SessionLess']);
                    if (count($checkurls) > 0 &&
                        $checkurls != $cachingConfiguration['Page.SessionLess']) {

                        $sessionlesslist = "'" . join("','",$checkurls) . "'";

                        $cachingConfig = join('', file($cachingConfigFile));
                        $cachingConfig = preg_replace('/\[\'Page.SessionLess\'\]\s*=\s*array\s*\((.*)\)\s*;/i', "['Page.SessionLess'] = array($sessionlesslist);", $cachingConfig);
                        $fp = @fopen ($cachingConfigFile, 'wb');
                        if ($fp) {
                            @fwrite ($fp, $cachingConfig);
                            @fclose ($fp);
                        }
                    }
                    // save cache statistics
                    if (!empty($cachingConfiguration['AutoCache.KeepStats'])) {
                        if (file_exists($xarOutput_cacheCollection.'/autocache.stats') &&
                            filesize($xarOutput_cacheCollection.'/autocache.stats') > 0) {

                            $stats = @file($xarOutput_cacheCollection.'/autocache.stats');
                            foreach ($stats as $entry) {
                                if (empty($entry)) continue;
                                list($url,$hit,$miss,$first,$last) = explode(' ',$entry);
                                $last = trim($last);
                                if (!isset($autocachestats[$url])) {
                                    $autocachestats[$url] = array('HIT' => $hit,
                                                                  'MISS' => $miss);
                                    $autocachefirstseen[$url] = $first;
                                    $autocachelastseen[$url] = $last;
                                } else {
                                    $autocachestats[$url]['HIT'] += $hit;
                                    $autocachestats[$url]['MISS'] += $miss;
                                    $autocachefirstseen[$url] = $first;
                                }
                            }
                            unset($stats);
                        }
                        $fp = @fopen($xarOutput_cacheCollection.'/autocache.stats', 'w');
                        if ($fp) {
                            foreach ($autocachestats as $url => $stats) {
                                @fwrite($fp, $url . ' ' . $stats['HIT'] . ' ' . $stats['MISS'] . ' ' . $autocachefirstseen[$url] . ' ' . $autocachelastseen[$url] . "\n");
                            }
                            @fclose($fp);
                        }
                        unset($autocachestats);
                        unset($autocachefirstseen);
                        unset($autocachelastseen);
                    }
                }
            }

            $fp = @fopen($xarOutput_cacheCollection.'/autocache.log', 'w');
        } else {
            $fp = @fopen($xarOutput_cacheCollection.'/autocache.log', 'a');
        }
        if ($fp) {
            @fwrite($fp, "$time $status $addr $url\n");
            @fclose($fp);
        }
   }
}

/**
 * Send HTTP headers for page caching (or return 304 Not Modified)
 *
 * @access private
 * @return void
 */
function xarPageCache_sendHeaders($modtime = 0)
{
    global $xarPage_cacheCode,
           $xarPage_cacheExpireHeader,
           $xarPage_cacheTime;

    if (empty($modtime)) {
    // CHECKME: this means 304 will never apply - is that what we want here ?
        // default to current time
        $modtime = time();
    }
    // doesn't seem to be taken into account ?
    $etag = $xarPage_cacheCode.$modtime;
    $match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ?
        $_SERVER['HTTP_IF_NONE_MATCH'] : NULL;
    if (!empty($match) && $match == $etag) {
        // jsb:  for some reason, Mozilla based browsers
        // do not re-send an ETag after getting a 304
        // so this only works once per cached page
        header('HTTP/1.0 304');
        exit;
    } else {
        $since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ?
            $_SERVER['HTTP_IF_MODIFIED_SINCE'] : NULL;
        if (!empty($since) && strtotime($since) >= $modtime) {   
            header('HTTP/1.0 304');
            exit;
            // jsb: according to RFC 2616, if $match isn't empty but is
            // not equal to the ETag we should send a 412 response
            // But browser behavior seems inconsistant with the doc and
            // often results in more data being sent than is necessary.
        }
    }
    if (!empty($xarPage_cacheExpireHeader)) {
        // this tells clients and proxies that this file is good until local
        // cache file is due to expire, and can be reused w/out revalidating
        header("Expires: " .
               gmdate("D, d M Y H:i:s", $modtime + $xarPage_cacheTime) .
               " GMT");
        header("Cache-Control: public, max-age=" . $xarPage_cacheTime);
    } else {
        header("Expires: 0");
        header("Cache-Control: public, must-revalidate");
    }
    header("ETag: $etag");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s", $modtime) . " GMT");
    // we can't use this after session_start()
    //session_cache_limiter('public');
    // PHP doesn't set the Pragma header when sending back a cookie
    if (isset($_COOKIE['XARAYASID'])) {
        header("Pragma: public");
    } else {
        header("Pragma:");
    }
}

function xarPageCache_sessionLess()
{
    global $xarOutput_cacheCollection;
    global $xarPage_sessionLess;
    global $xarPage_cacheCode;
    global $xarPage_cacheTime;
    
    // Session-less page caching (TODO: extend and place in separate function)
    if (!empty($xarPage_sessionLess) &&
        is_array($xarPage_sessionLess) &&
    // we have no session id in a cookie or URL parameter
        empty($_REQUEST['XARAYASID']) &&
    // we're dealing with a GET OR a HEAD request
        !empty($_SERVER['REQUEST_METHOD']) &&
        ($_SERVER['REQUEST_METHOD'] == 'GET' || $_SERVER['REQUEST_METHOD'] == 'HEAD') &&
    // the URL is one of the candidates for session-less caching
    // TODO: make compatible with IIS and https (cfr. xarServer.php)
        !empty($_SERVER['HTTP_HOST']) &&
        !empty($_SERVER['REQUEST_URI']) &&
        in_array('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],
                 $xarPage_sessionLess)
       ) {
        $cacheKey = 'static';
        $xarPage_cacheCode = md5($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
        $cache_file = "$xarOutput_cacheCollection/page/$cacheKey-$xarPage_cacheCode.php";
        if (file_exists($cache_file) &&
            filesize($cache_file) > 0 &&
            ($xarPage_cacheTime == 0 ||
             filemtime($cache_file) > time() - $xarPage_cacheTime)) {

            // this may already exit if we have a 304 Not Modified
            $modtime = filemtime($cache_file);
            xarPageCache_sendHeaders($modtime);

            // CHECKME: so we'll never log those 304 Not Modified's here at the moment !? :-)
            if (file_exists($xarOutput_cacheCollection.'/autocache.start')) {
                xarPage_autoCacheLogStatus('HIT');
            }

            xarPageGetCached($cacheKey);
            // we're done here !
            exit;

        } else {
            // tell xarPageSetCached() that we want to save another copy here
            $GLOBALS['xarPage_cacheNoSession'] = 1;
            // we'll continue with the core loading etc. here
        }
    }
}

?>
