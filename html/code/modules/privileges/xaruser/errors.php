<?php
/**
 * @package modules
 * @subpackage privileges module
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.com/index.php/release/1098.html
 */
/**
 * @return array data for the template display
 */

    function privileges_user_errors()
    {
        if(!xarVarFetch('layout',   'isset', $data['layout']   , 'default', XARVAR_DONT_SET)) {return;}
        if(!xarVarFetch('redirecturl',   'isset', $data['redirecturl']   , xarServer::getCurrentURL(array(),false), XARVAR_DONT_SET)) {return;}
        if (!xarUserIsLoggedIn()) {
            return $data;
        } else {
            xarController::redirect($data['redirecturl']);
            return true;
        }
    }
?>
