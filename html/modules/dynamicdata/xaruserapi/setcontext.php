<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * get the current context variables for DD
 * save them if any are added
 */
function dynamicdata_userapi_setcontext($args)
{
    extract($args);

    $context = xarSessionGetVar('context');
    $added = false;

    if (empty($modid) && !empty($module)) {
        $modid = xarModGetIDFromName($module);
    }
    if (empty($modid) && !empty($moduleid)) {
        $modid = $moduleid;
    }

    if (!empty($modid)) {
        $context['modid'] = $modid;
    } elseif (empty($modid) && empty($context['modid'])) {
        $context['modid'] = 182;
        $added = true;
    }
    // cover all bases for now
    $context['moduleid'] = $context['modid'];

    if (!empty($itemtype)) {
        $context['itemtype'] = $itemtype;
    } elseif (empty($itemtype) && empty($context['itemtype'])) {
        $context['itemtype'] = 0;
        $added = true;
    }

    if (!empty($objectid)) {
        $context['objectid'] = $objectid;
    } elseif (empty($objectid) && empty($context['objectid'])) {
        $context['objectid'] = NULL;
        $added = true;
    }

    if (!empty($urlmodule)) {
        $context['urlmodule'] = $urlmodule;
    } elseif (empty($urlmodule) && empty($context['urlmodule'])) {
        $context['urlmodule'] = 'dynamicdata';
        $added = true;
    }

    if (!empty($tplmodule)) {
        $context['tplmodule'] = $tplmodule;
    } elseif (empty($tplmodule) && empty($context['tplmodule'])) {
        $context['tplmodule'] = 'dynamicdata';
        $added = true;
    }

    if (!empty($template)) {
        $context['template'] = $template;
    } elseif (empty($template) && empty($context['template'])) {
        $context['template'] = '';
        $added = true;
    }

    if (!empty($layout)) {
        $context['layout'] = $layout;
    } elseif (empty($layout) && empty($context['layout'])) {
        $context['layout'] = 'default';
        $added = true;
    }

    // save if anything changed
    if ((count($args) > 0) || $added) xarSessionSetVar('context',$context);
    return $context;
}

?>
