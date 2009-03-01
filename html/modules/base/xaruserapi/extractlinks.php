<?php
/**
 * Extract links
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * Extract a list of links from some HTML content (cfr. getfile and checklink)
 * Note: this is definitely not meant as an exhaustive link extractor
 *
 * @author mikespub
 * @access public
 * @param $args['content'] string the HTML content that you want to extract links from
 * @param $args['getlocal'] bool indicates if we want to get local URLs (default is true)
 * @param $args['getremote'] bool indicates if we want to get remote URLs (default is false)
 * @param $args['baseurl'] string optional base URL for the content (default is this site)
 * @return array list of links found
 */
function base_userapi_extractlinks($args)
{
    if (empty($args['content'])) {
        return array();
    }
    if (!isset($args['getlocal'])) {
        $args['getlocal'] = true;
    }
    if (!isset($args['getremote'])) {
        $args['getremote'] = false;
    }
    if (!empty($args['baseurl'])) {
        $baseurl = $args['baseurl'];
    } elseif (preg_match('!<base[^>]*?\shref="([^"]+)"!im',$args['content'],$matches)) {
        $baseurl = $matches[1];
    } else {
        $baseurl = xarServerGetBaseURL();
    }
    if (preg_match('!^(https?)://([^/]+)/!',$baseurl,$matches)) {
        $server = $matches[2]; // possibly with port number
        $protocol = $matches[1];
    } else {
        $server = xarServerGetHost();
        $protocol = xarServerGetProtocol();
    }

    $links = array();
    if (!preg_match_all('!<a[^>]*?\shref="([^"]+)"!im',$args['content'],$matches)) {
        return $links;
    }
    foreach ($matches[1] as $url) {
        // replace &amp; with &
        $url = preg_replace('/&amp;/','&',$url);

        if (empty($url)) {
            continue;

        } elseif (strstr($url,'://')) {
            // only support http(s):// and ftp:// for now
            if (!preg_match('!^(https?|ftp)://!',$url)) {
                continue;
            }
            // check if we're dealing with a local URL
            if (preg_match("!^(https?|ftp)://($server|localhost|127\.0\.0\.1)/!",$url)) {
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
        } elseif (substr($url,0,1) == '#' || stristr($url,'javascript') || strstr($url,'(')) {
            continue;

        // absolute URI
        } elseif (substr($url,0,1) == '/') {
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

?>
