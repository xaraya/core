<?php
/**
 * Main entry point for the admin interface of this module
 *
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 *
 * @author Marcel van der Boom
 */

/**
 * Main entry point for the admin interface of this module
 *
 * This function is the default function for the admin interface, and is called whenever the module is
 * initiated with only an admin type but no func parameter passed.  
 * The function displays the module's overview page, or redirects to another page if overviews are disabled.
 *
 * @author John Robeson
 * @author Greg Allan
 * 
 * @return mixed Output display string or boolean true if redirected
 */
function base_admin_main()
{
    // Security
    if(!xarSecurity::check('EditBase')) return;

    $samemodule = xarController::isRefererSameModule();
    
    if (((bool)xarModVars::get('modules', 'disableoverview') == false) || $samemodule){
        return xarTpl::module('base','admin','overview');
    } else {
        xarController::redirect(xarController::URL('base', 'admin', 'modifyconfig'));
        return true;
    }
}
