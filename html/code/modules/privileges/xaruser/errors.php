<?php
/**
 * @package modules
 * @subpackage privileges module
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @link http://xaraya.com/index.php/release/1098.html
 */

    function privileges_user_errors()
    {
        if(!xarVarFetch('layout',   'isset', $data['layout']   , 'default', XARVAR_DONT_SET)) {return;}
        return $data;
    }
?>