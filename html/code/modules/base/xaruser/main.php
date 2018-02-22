<?php
/**
 * Main entry point for the user interface of this module
 *
 * @package modules\base
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/68.html
 */

/**
 * The main user interface function of this module.
 * 
 * This function is the default function, and is called whenever the module is
 * initiated without defining arguments.  
 * The function displays the module's main entry page, or redirects to different page if the admin has defined one.
 *
 * @author Paul Rosania
 * @author Marc Lutolf
 * 
 * @param array $args Optional parameters
 * @return mixed output display string
 */
function base_user_main(Array $args=array())
{
    // Security Check
    if(!xarSecurityCheck('ViewBase')) return;
    
    /* fetch some optional 'page' argument or parameter */
    extract($args);
    if (!xarVarFetch('page','str',$page,'',XARVAR_NOT_REQUIRED)) return;
    if (!empty($page)){
        xarTpl::setPageTitle($page);
        /* Cache the custom page name so it is accessible elsewhere */
        xarVarSetCached('Base.pages','page',$page);
    } else {
        $pageTemplate = xarModVars::get('base', 'AlternatePageTemplateName');
        if (xarModVars::get('base', 'UseAlternatePageTemplate') != '' &&
            $pageTemplate != '') {
            xarTpl::setPageTemplateName($pageTemplate);
        }
        xarTpl::setPageTitle(xarML('Welcome'));
    }
    /* if you want to include different pages in your user-main template
     * return array('page' => $page);
     * if you want to use different user-main-<page> templates
     */
    return xarTpl::module('base','user','main',array(),$page);
}
?>
