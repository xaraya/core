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

function commerce_userapi_output_generated_category_path($args)
{
    extract($args);
    if(!isset($from)) $from = 'category';

    $calculated_category_path_string = '';
    $calculated_category_path = xarModAPIFunc('commerce','user','generate_category_path', array(
                                    'id' => $id,
                                    'from' => $from));
    for ($i = 0, $n = sizeof($calculated_category_path); $i < $n; $i++) {
        for ($j = 0, $k = sizeof($calculated_category_path[$i]); $j < $k; $j++) {
            $calculated_category_path_string .= $calculated_category_path[$i][$j]['text'] . '&nbsp;&gt;&nbsp;';
        }
        $calculated_category_path_string = substr($calculated_category_path_string, 0, -16) . '<br>';
    }
    $calculated_category_path_string = substr($calculated_category_path_string, 0, -4);
    if (strlen($calculated_category_path_string) < 1) $calculated_category_path_string = xarML('Top');
    return $calculated_category_path_string;
}
?>