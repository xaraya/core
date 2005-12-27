<?php
// ----------------------------------------------------------------------
// Copyright (C) 2004: Marc Lutolf (marcinmilan@xaraya.com)
// Purpose of file:  Configuration functions for commerce
// ----------------------------------------------------------------------
//  based on:
//  (c) 2003 XT-Commerce
//  (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
//  (c) 2002-2003 osCommerce (oscommerce.sql,v 1.83); www.oscommerce.com
//  (c) 2003  nextcommerce (nextcommerce.sql,v 1.76 2003/08/25); www.nextcommerce.org
// ----------------------------------------------------------------------

function commerce_userapi_show_category($args)
{
    extract($args);
//    global $foo, $categories_string, $cid;

    $configuration = xarModAPIFunc('commerce','admin','load_configuration');
    $localeinfo = xarLocaleGetInfo(xarMLSGetSiteLocale());
    $data['language'] = $localeinfo['lang'] . "_" . $localeinfo['country'];
    if (!isset($categories_string)) $categories_string = '';

    // image for first level
    $img_1='<img src="' . xarTplGetImage('icon_arrow.jpg', 'commerce') . '">&nbsp;';

    for ($a=0; $a<$foo[$counter]['level']; $a++) {
        if ($foo[$counter]['level'] == $a+1) {
            $categories_string .= "&nbsp;-&nbsp;";
        }
        $categories_string .= "&nbsp;&nbsp;";
    }
    if ($foo[$counter]['level'] == 0) {
        if (strlen($categories_string)=='0') {
            $categories_string .='<div width="100%"><span class="moduleRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">';
        }
        else {
            $categories_string .='<div width="100%"><span class="moduleRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)">';
        }

    // image for first level
    $categories_string .= $img_1;
    $categories_string .= '<b><a href="';
    //<img src="templates/zanier/img/recht_small.gif">
    }
    else {
        $categories_string .= '<a href="';
    }
    if ($foo[$counter]['parent'] == 0) {
        $cPath_new = $counter;
    } else {
        $cPath_new = $foo[$counter]['path'];
    }

    $categories_string .= xarModURL('commerce','user','default', array('cPath' => $cPath_new));
    $categories_string .= '">';

    // display category name
    if ( ($cid) && (in_array($counter, $cid)) ) {
        $categories_string .= '<b>' . $foo[$counter]['name'] . '</b>';
    } else {
        $categories_string .= $foo[$counter]['name'];
    }

    if (xarModAPIFunc('commerce','user','has_category_subcategories', array('cid' => $counter))) {
        $categories_string .= '-&gt;';
    }
    if ($foo[$counter]['level']=='') {
        $categories_string .= '</a></b>';
    } else {
        $categories_string .= '</a>';
    }

    if ($configuration['show_counts'] == 'true') {
        $products_in_category = xarModAPIFunc('commerce','user','count_products_in_category', array('cid' => $counter));
        if ($products_in_category > 0) {
            $categories_string .= '&nbsp;(' . $products_in_category . ')';
        }
    }

    if ($foo[$counter]['level'] == 0) {
        $categories_string .= '</span></div>';
    } else {
        $categories_string .= '<br />';
    }

    if ($foo[$counter]['next_id']) {
        $categories_string .= xarModAPIFunc('commerce','user','show_category', array('cid' => $cid, 'foo' => $foo, 'counter' => $foo[$counter]['next_id']));
    }
    return $categories_string;
}

?>