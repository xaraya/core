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

// Output a form pull down menu

function commerce_userapi_draw_pull_down_menu($args)
{
    extract($args);
    if (!isset($name)) return '';
    if (!isset($values)) return '';
    if (!isset($default)) $default = '';
    if (!isset($parameters)) $parameters = '';
    if (!isset($required)) $required = false;

    $field = '<select name="' . strtr(trim($name), array('"' => '&quot;')) . '"';

    if (xarModAPIFunc('commerce','user','not_null',array('arg' => $parameters))) $field .= ' ' . $parameters;

    $field .= '>';

    if (empty($default) && isset($GLOBALS[$name])) $default = $GLOBALS[$name];

    for ($i=0, $n=sizeof($values); $i<$n; $i++) {
        $field .= '<option value="' . strtr(trim($values[$i]['id']), array('"' => '&quot;')) . '"';
        if ($default == $values[$i]['id']) {
            $field .= ' SELECTED';
        }

        $field .= '>' . strtr(trim($values[$i]['text']), array('"' => '&quot;', '\'' => '&#039;', '<' => '&lt;', '>' => '&gt;')) . '</option>';
    }
    $field .= '</select>';

    if ($required == true) $field .= '&nbsp;<span class="fieldRequired">* Required</span>';

    return $field;
}
 ?>