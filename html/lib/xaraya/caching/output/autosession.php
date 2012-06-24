<?php
/**
 * Automatic discovery & update of candidates for session-less page caching
 * 
 * @package core
 * @subpackage caching
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @author mikespub
 * @author jsb
**/

class xarAutoSessionCache extends Object
{
    /**
     * Log the HIT / MISS status of URLs requested by first-time visitors
     *
     * @return none
     */
    public static function logStatus($status = 'MISS', $autoCachePeriod)
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

            if (!empty($autoCachePeriod) &&
                filemtime(xarOutputCache::$cacheDir.'/autocache.start') < time() - $autoCachePeriod) {
                // re-calculate Page.SessionLess based on autocache.log and save in config.caching.php
                self::refreshSessionLessList();

                $fp = @fopen(xarOutputCache::$cacheDir.'/autocache.log', 'w');
            } else {
                $fp = @fopen(xarOutputCache::$cacheDir.'/autocache.log', 'a');
            }
            if ($fp) {
                @fwrite($fp, "$time $status $addr $url\n");
                @fclose($fp);
            }
        }
    }

    /**
     * Re-calculate Page.SessionLess based on autocache.log and save in config.caching.php
     *
     */
    public static function refreshSessionLessList()
    {
        @touch(xarOutputCache::$cacheDir.'/autocache.start');

        $xarVarDir = sys::varpath();

        $cachingConfigFile = $xarVarDir.'/cache/config.caching.php';
        if (file_exists($cachingConfigFile) &&
            is_writable($cachingConfigFile)) {

            $cachingConfiguration = array();
            include $cachingConfigFile;
            if (!empty($cachingConfiguration['AutoCache.MaxPages']) &&
                file_exists(xarOutputCache::$cacheDir.'/autocache.log') &&
                filesize(xarOutputCache::$cacheDir.'/autocache.log') > 0) {

                $logs = @file(xarOutputCache::$cacheDir.'/autocache.log');
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

                    $checkurls = str_replace("'","\\'",$checkurls);
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
                    if (file_exists(xarOutputCache::$cacheDir.'/autocache.stats') &&
                        filesize(xarOutputCache::$cacheDir.'/autocache.stats') > 0) {

                        $stats = @file(xarOutputCache::$cacheDir.'/autocache.stats');
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
                    $fp = @fopen(xarOutputCache::$cacheDir.'/autocache.stats', 'w');
                    if ($fp) {
                        foreach ($autocachestats as $url => $stats) {
                            if ($stats['HIT'] + $stats['MISS'] < 2) {
                                continue;
                            }
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
    }
}

?>
