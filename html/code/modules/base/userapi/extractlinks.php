<?php

/**
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Base\UserApi;

use Xaraya\Modules\MethodClass;
use Xaraya\Modules\Base\UserApi;
use xarServer;
use sys;

sys::import('xaraya.modules.method');

/**
 * base userapi extractlinks function
 * @extends MethodClass<UserApi>
 */
class ExtractlinksMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Extract a list of links from some HTML content (cfr. getfile and checklink)
     * Note: This is definitely not meant as an exhaustive link extractor
     * @author mikespub
     * @param array<string,mixed> $args Array of optional parameters<br/>
     * string   $args['content'] String the HTML content that you want to extract links from<br/>
     * string   $args['getlocal'] bool Indicates if we want to get local URLs (default is true)<br/>
     * string   $args['getremote'] bool Indicates if we want to get remote URLs (default is false)<br/>
     * string   $args['baseurl'] string Optional base URL for the content (default is this site)<br/>
     * @return array List of links found
     * @see UserApi::extractlinks()
     */
    public function __invoke(array $args = [])
    {
        if (empty($args['content'])) {
            return [];
        }
        if (!isset($args['getlocal'])) {
            $args['getlocal'] = true;
        }
        if (!isset($args['getremote'])) {
            $args['getremote'] = false;
        }
        if (!empty($args['baseurl'])) {
            $baseurl = $args['baseurl'];
        } elseif (preg_match('!<base[^>]*?\shref="([^"]+)"!im', $args['content'], $matches)) {
            $baseurl = $matches[1];
        } else {
            $baseurl = $this->ctl()->getBaseURL();
        }
        if (preg_match('!^(https?)://([^/]+)/!', $baseurl, $matches)) {
            $server = $matches[2]; // possibly with port number
            $protocol = $matches[1];
        } else {
            $parsed = parse_url($this->ctl()->getBaseURL());
            $server = $parsed['host'];
            $protocol = $parsed['scheme'];
        }

        $links = [];
        if (!preg_match_all('!<a[^>]*?\shref="([^"]+)"!im', $args['content'], $matches)) {
            return $links;
        }
        foreach ($matches[1] as $url) {
            // replace &amp; with &
            $url = preg_replace('/&amp;/', '&', $url);

            if (empty($url)) {
                continue;

            } elseif (strstr($url, '://')) {
                // only support http(s):// and ftp:// for now
                if (!preg_match('!^(https?|ftp)://!', $url)) {
                    continue;
                }
                // check if we're dealing with a local URL
                if (preg_match("!^(https?|ftp)://($server|localhost|127\.0\.0\.1)/!", $url)) {
                    if (!empty($args['getlocal'])) {
                        $links[$url] = 1;
                    }
                } elseif (!empty($args['getremote'])) {
                    $links[$url] = 1;
                }
                continue;

            } elseif (empty($args['getlocal'])) {
                continue;

                // ignore local anchors, javascript and other weird "links"
            } elseif (substr($url, 0, 1) == '#' || stristr($url, 'javascript') || strstr($url, '(')) {
                continue;

                // absolute URI
            } elseif (substr($url, 0, 1) == '/') {
                $url = $protocol . '://' . $server . $url;
                $links[$url] = 1;

                // relative URI
            } else {
                $url = $baseurl . $url;
                $links[$url] = 1;
            }
        }

        return array_keys($links);
    }
}
