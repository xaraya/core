<?php
/**
 * Main entry point for the admin interface of this module
 *
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * Entry point for error messages
 *
 * @author Marc Lutolf <mfl@netspan.ch>
 */
function modules_user_errors($args)
{
    if(!xarSecurityCheck('EditModules')) return;
    $data['layout'] = 'general';
    $data['message'] = urldecode($args['message']);
    return $data;
}

?>