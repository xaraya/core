<?php
/**
 * @package modules\authsystem
 * @subpackage authsystem
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Sample REST API call supported by this module (if any)
 *
 * @return string of info
 */
function authsystem_restapi_honeypot($args = [])
{
    // @checkme handle POSTed args by passing $args['input'] only in handler?
    //extract($args);
    if (empty($args['username']) || empty($args['password'])) {
        $result = 'Missing username or password.';
    } else {
        $result = 'Wanna have a cookie?';
    }
    return $result;
}
