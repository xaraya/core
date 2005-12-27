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

function commerce_userapi_draw_box_contents($box_contents, $box_shadow_color = BOX_SHADOW, $box_background_color = BOX_BGCOLOR_CONTENTS) {
    $contents = '<table border="0" width="100%" cellspacing="0" cellpadding="1" bgcolor="' . $box_shadow_color . '">' . CR .
                 '  <tr>' . CR .
                 '    <td><table border="0" width="100%" cellspacing="0" cellpadding="4" bgcolor="' . $box_background_color . '">' . CR .
                 '      <tr>' . CR .
                 '        <td><img src="images/pixel_trans.gif" border="0" width="100%" height="5"></td>' . CR .
                 '      </tr>';

    if (is_array($box_contents)) {
      for ($i=0; $i<sizeof($box_contents); $i++) {
        $contents .= xtc_draw_box_content_bullet($box_contents[$i]['title'], $box_contents[$i]['link']);
      }
    } else {
      $contents .= '      <tr>' . CR .
                   '        <td class="infoboxText">' . $box_contents . '</td>' . CR .
                   '      </tr>' . CR;
    }

    $contents .= '      <tr>' . CR .
                 '        <td><img src="images/pixel_trans.gif" border="0" width="100%" height="5"></td>' . CR .
                 '      </tr>' . CR .
                 '    </table></td>' . CR .
                 '  </tr>' . CR .
                 '</table>' . CR;

    return $contents;
  }
 ?>