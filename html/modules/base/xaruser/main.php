<?php
/**
 * File: $Id$
 *
 * Base User GUI functions
 *
 * @package modules
 * @copyright (C) 2004 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @author Paul Rosania
 * @todo decide whether to use this file or delete it
 */
function base_user_main($args)
{
    // Security Check
    if(!xarSecurityCheck('ViewBase')) return;
    // fetch some optional 'page' argument or parameter
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
    // if you want to include different pages in your user-main template
    //return array('page' => $page);
    // if you want to use different user-main-<page> templates
    return xarTplModule('base','user','main',array(),$page);
}
?>
