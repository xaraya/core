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
 */

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
           $xarPage_cacheTime,
           $xarOutput_cacheTheme,
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

        xarPage_httpCacheHeaders($cache_file);

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

    xarOutputCleanCached('Page');
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
           $xarPage_cacheTime,
           $xarOutput_cacheTheme,
           $xarPage_cacheDisplay,
           $xarOutput_cacheSizeLimit,
           $xarPage_cacheShowTime,
           $xarPage_cacheCode;
    
    $xarTpl_themeDir = xarTplGetThemeDir();
    
    if (xarCore_IsCached('Page.Caching', 'nocache')) { return; }

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
        !xarCacheSizeLimit($xarOutput_cacheCollection, 'Page')) {
        
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

        xarPage_httpCacheHeaders($cache_file);

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
        global $xarPage_autoCachePeriod;

        if (!empty($xarPage_autoCachePeriod) &&
            filemtime('var/cache/output/autocache.start') < time() - $xarPage_autoCachePeriod) {
            @touch('var/cache/output/autocache.start');

            // re-calculate Page.SessionLess based on autocache.log and save in config.caching.php
            $cachingConfigFile = 'var/cache/config.caching.php';
            if (file_exists($cachingConfigFile) &&
                is_writable($cachingConfigFile)) {

                include $cachingConfigFile;
                if (!empty($cachingConfiguration['AutoCache.MaxPages']) &&
                    file_exists('var/cache/output/autocache.log') &&
                    filesize('var/cache/output/autocache.log') > 0) {

                    $logs = @file('var/cache/output/autocache.log');
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
                        if (file_exists('var/cache/output/autocache.stats') &&
                            filesize('var/cache/output/autocache.stats') > 0) {

                            $stats = @file('var/cache/output/autocache.stats');
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
                        $fp = @fopen('var/cache/output/autocache.stats', 'w');
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

            $fp = @fopen('var/cache/output/autocache.log', 'w');
        } else {
            $fp = @fopen('var/cache/output/autocache.log', 'a');
        }
        if ($fp) {
            @fwrite($fp, "$time $status $addr $url\n");
            @fclose($fp);
        }
   }
}

function xarPage_httpCacheHeaders($cache_file)
{
    global $xarPage_cacheCode,
           $xarPage_cacheExpireHeader,
           $xarPage_cacheTime;

    if (!file_exists($cache_file)) { return; }
    $mod = filemtime($cache_file);
    // doesn't seem to be taken into account ?
    $etag = $xarPage_cacheCode.$mod;
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
        if (!empty($since) && strtotime($since) >= $mod) {   
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
               gmdate("D, d M Y H:i:s", $mod + $xarPage_cacheTime) .
               " GMT");
        header("Cache-Control: public, max-age=" . $xarPage_cacheTime);
    } else {
        header("Expires: 0");
        header("Cache-Control: public, must-revalidate");
    }
    header("ETag: $etag");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s", $mod) . " GMT");
    // we can't use this after session_start()
    //session_cache_limiter('public');
    // PHP doesn't set the Pragma header when sending back a cookie
    if (isset($_COOKIE['XARAYASID'])) {
        header("Pragma: public");
    } else {
        header("Pragma:");
    }
}

?>
