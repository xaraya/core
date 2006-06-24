<?php
/**
 * Main function
 * @package modules
 * @copyright (C) 2005-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * @author Paul Rosania
 */
function base_user_main($args)
{
    // Security Check
    if(!xarSecurityCheck('ViewBase')) return;
    /* fetch some optional 'page' argument or parameter */
    extract($args);
    if (!xarVarFetch('page','str',$page,'',XARVAR_NOT_REQUIRED)) return;
    if (!empty($page)){
        xarTplSetPageTitle($page);
        /* Cache the custom page name so it is accessible elsewhere */
        xarVarSetCached('Base.pages','page',$page);
    } else {
        $pageTemplate = xarModGetVar('base', 'AlternatePageTemplateName');
        if (xarModGetVar('base', 'UseAlternatePageTemplate') != '' &&
            $pageTemplate != '') {
            xarTplSetPageTemplateName($pageTemplate);
        }
        xarTplSetPageTitle(xarML('Welcome'));
    }
    /* if you want to include different pages in your user-main template
     * return array('page' => $page);
     * if you want to use different user-main-<page> templates
     */
    return xarTplModule('base','user','main',array(),$page);
}
?>
