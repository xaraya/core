<?php
/* --------------------------------------------------------------
   $Id: new_attributes_include.php,v 1.3 2003/12/31 14:07:00 fanta2k Exp $

   XT-Commerce - community made shopping
   http://www.xt-commerce.com

   Copyright (c) 2003 XT-Commerce
   --------------------------------------------------------------
   based on:
   (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
   (c) 2002-2003 osCommerce(new_attributes_functions); www.oscommerce.com
   (c) 2003  nextcommerce (new_attributes_include.php,v 1.11 2003/08/21); www.nextcommerce.org

   Released under the GNU General Public License
   --------------------------------------------------------------
   Third Party contributions:
   New Attribute Manager v4b                Autor: Mike G | mp3man@internetwork.net | http://downloads.ephing.com

   Released under the GNU General Public License
   --------------------------------------------------------------*/

   // include needed functions

   require_once(DIR_FS_INC .'xtc_get_tax_rate.inc.php');
   require_once(DIR_FS_INC .'xtc_get_tax_class_id.inc.php');
   require_once(DIR_FS_INC .'xtc_format_price.inc.php');
?>
  <tr>
    <td class="pageHeading" colspan="3"><?php echo $pageTitle; ?></td>
  </tr>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" name="SUBMIT_ATTRIBUTES"><input type="hidden" name="current_product_id" value="<?php echo $_POST['current_product_id']; ?>"><input type="hidden" name="action" value="change">
<?php
  if ($cPath) echo '<input type="hidden" name="cPathID" value="' . $cPath . '">';

  require('new_attributes_functions.php');

  // Temp id for text input contribution.. I'll put them in a seperate array.
  $tempTextID = '1999043';

  // Lets get all of the possible options
  $query = "SELECT * FROM products_options where products_options_id LIKE '%' AND language_id = '" . $_SESSION['languages_id'] . "'";
  $result = mysql_query($query) or die(mysql_error());
  $matches = mysql_num_rows($result);

  if ($matches) {
    while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
      $current_product_option_name = $line['products_options_name'];
      $current_product_option_id = $line['products_options_id'];
      // Print the Option Name
      echo "<TR class=\"dataTableHeadingRow\">";
      echo "<TD class=\"dataTableHeadingContent\"><B>" . $current_product_option_name . "</B></TD>";
      echo "<TD class=\"dataTableHeadingContent\"><B>Attribute Model</B></TD>";
      echo "<TD class=\"dataTableHeadingContent\"><B>Stock</B></TD>";
      echo "<TD class=\"dataTableHeadingContent\"><B>Value Weight</B></TD>";
      echo "<TD class=\"dataTableHeadingContent\"><B>Weight Prefix</B></TD>";
      echo "<TD class=\"dataTableHeadingContent\"><B>Value Price</B></TD>";
      echo "<TD class=\"dataTableHeadingContent\"><B>Price Prefix</B></TD>";

      if ($optionTypeInstalled == '1') {
        echo "<TD class=\"dataTableHeadingContent\"><B>Option Type</B></TD>";
        echo "<TD class=\"dataTableHeadingContent\"><B>Quantity</B></TD>";
        echo "<TD class=\"dataTableHeadingContent\"><B>Order</B></TD>";
        echo "<TD class=\"dataTableHeadingContent\"><B>Linked Attr.</B></TD>";
        echo "<TD class=\"dataTableHeadingContent\"><B>ID</B></TD>";
      }

      if ($optionSortCopyInstalled == '1') {
        echo "<TD class=\"dataTableHeadingContent\"><B>Weight</B></TD>";
        echo "<TD class=\"dataTableHeadingContent\"><B>Weight Prefix</B></TD>";
        echo "<TD class=\"dataTableHeadingContent\"><B>Sort Order</B></TD>";
      }
      echo "</TR>";

      // Find all of the Current Option's Available Values
      $query2 = "SELECT * FROM products_options_values_to_products_options WHERE products_options_id = '" . $current_product_option_id . "' ORDER BY products_options_values_id DESC";
      $result2 = mysql_query($query2) or die(mysql_error());
      $matches2 = mysql_num_rows($result2);

      if ($matches2) {
        $i = '0';
        while ($line = mysql_fetch_array($result2, MYSQL_ASSOC)) {
          $i++;
          $rowClass = rowClass($i);
          $current_value_id = $line['products_options_values_id'];
          $isSelected = checkAttribute($current_value_id, $_POST['current_product_id'], $current_product_option_id);
          if ($isSelected) {
            $CHECKED = ' CHECKED';
          } else {
            $CHECKED = '';
          }

          $query3 = "SELECT * FROM products_options_values WHERE products_options_values_id = '" . $current_value_id . "' AND language_id = '" . $_SESSION['languages_id'] . "'";
          $result3 = mysql_query($query3) or die(mysql_error());
          while($line = mysql_fetch_array($result3, MYSQL_ASSOC)) {
            $current_value_name = $line['products_options_values_name'];
            // Print the Current Value Name
            echo "<TR class=\"" . $rowClass . "\">";
            echo "<TD class=\"main\">";
            // Add Support for multiple text input option types (for Chandra's contribution).. and using ' to begin/end strings.. less of a mess.
            if ($optionTypeTextInstalled == '1' && $current_value_id == $optionTypeTextInstalledID) {
              $current_value_id_old = $current_value_id;
              $current_value_id = $tempTextID;
              echo '<input type="checkbox" name="optionValuesText[]" value="' . $current_value_id . '"' . $CHECKED . '>&nbsp;&nbsp;' . $current_value_name . '&nbsp;&nbsp;';
              echo '<input type="hidden" name="' . $current_value_id . '_options_id" value="' . $current_product_option_id . '">';
            } else {
              echo "<input type=\"checkbox\" name=\"optionValues[]\" value=\"" . $current_value_id . "\"" . $CHECKED . ">&nbsp;&nbsp;" . $current_value_name . "&nbsp;&nbsp;";
            }
            echo "</TD>";
            echo "<TD class=\"main\" align=\"left\"><input type=\"text\" name=\"" . $current_value_id . "_model\" value=\"" . $attribute_value_model . "\" size=\"15\"></TD>";
            echo "<TD class=\"main\" align=\"left\"><input type=\"text\" name=\"" . $current_value_id . "_stock\" value=\"" . $attribute_value_stock . "\" size=\"4\"></TD>";
            echo "<TD class=\"main\" align=\"left\"><input type=\"text\" name=\"" . $current_value_id . "_weight\" value=\"" . $attribute_value_weight . "\" size=\"10\"></TD>";
            echo "<TD class=\"main\" align=\"left\"><SELECT name=\"" . $current_value_id . "_weight_prefix\"><OPTION value=\"+\"" . $posCheck_weight . ">+<OPTION value=\"-\"" . $negCheck_weight . ">-</SELECT></TD>";

            // brutto Admin
            if (PRICE_IS_BRUTTO=='true'){
            $attribute_value_price_calculate = xtc_format_price(xtc_round($attribute_value_price*((100+(xtc_get_tax_rate(xtc_get_tax_class_id($_POST['current_product_id']))))/100),PRICE_PRECISION),false,false);
            } else {
            $attribute_value_price_calculate = xtc_round($attribute_value_price,PRICE_PRECISION);
            }
            echo "<TD class=\"main\" align=\"left\"><input type=\"text\" name=\"" . $current_value_id . "_price\" value=\"" . $attribute_value_price_calculate . "\" size=\"10\">";
            // brutto Admin
            if (PRICE_IS_BRUTTO=='true'){
             echo TEXT_NETTO .'<b>'.xtc_format_price(xtc_round($attribute_value_price,PRICE_PRECISION),true,false).'</b>  ';
            }

            echo "</TD>";

            if ($optionTypeInstalled == '1') {
              extraValues($current_value_id, $_POST['current_product_id']);
              echo "<TD class=\"main\" align=\"left\"><input type=\"text\" name=\"" . $current_value_id . "_prefix\" value=\"" . $attribute_prefix . "\" size=\"4\"></TD>";
              echo "<TD class=\"main\" align=\"left\"><SELECT name=\"" . $current_value_id . "_type\">";
              displayOptionTypes($attribute_type);
              echo "</SELECT></TD>";
              echo "<TD class=\"main\" align=\"left\"><input type=\"text\" name=\"" . $current_value_id . "_qty\" value=\"" . $attribute_qty . "\" size=\"4\"></TD>";
              echo "<TD class=\"main\" align=\"left\"><input type=\"text\" name=\"" . $current_value_id . "_order\" value=\"" . $attribute_order . "\" size=\"4\"></TD>";
              echo "<TD class=\"main\" align=\"left\"><input type=\"text\" name=\"" . $current_value_id . "_linked\" value=\"" . $attribute_linked . "\" size=\"4\"></TD>";
              echo "<TD class=\"main\" align=\"left\">" . $current_value_id . "</TD>";
            } else {
              echo "<TD class=\"main\" align=\"left\"><SELECT name=\"" . $current_value_id . "_prefix\"> <OPTION value=\"+\"" . $posCheck . ">+<OPTION value=\"-\"" . $negCheck . ">-</SELECT></TD>";
              if ($optionSortCopyInstalled == '1') {
                getSortCopyValues($current_value_id, $_POST['current_product_id']);
                echo "<TD class=\"main\" align=\"left\"><input type=\"text\" name=\"" . $current_value_id . "_weight\" value=\"" . $attribute_weight . "\" size=\"10\"></TD>";
                echo "<TD class=\"main\" align=\"left\"><SELECT name=\"" . $current_value_id . "_weight_prefix\">";
                sortCopyWeightPrefix($attribute_weight_prefix);
                echo "</SELECT></TD>";
                echo "<TD class=\"main\" align=\"left\"><input type=\"text\" name=\"" . $current_value_id . "_sort\" value=\"" . $attribute_sort . "\" size=\"4\"></TD>";
              }
            }

            echo "</TR>";

            if ($optionTypeTextInstalled == '1' && $current_value_id_old == $optionTypeTextInstalledID) {
              $tempTextID++;
            }
          }
          if ($i == $matches2 ) $i = '0';
        }
      } else {
        echo "<TR>";
        echo "<TD class=\"main\"><SMALL>No values under this option.</SMALL></TD>";
        echo "</TR>";
      }
    }
  }
?>
  <tr>
    <td colspan="10" class="main"><br>
<?php
<input type="image" src="#xarTplGetImage('buttons/' . xarSessionGetVar('language') . '/'.'button_save.gif')#" border="0" alt=IMAGE_SAVE>
echo $backLink.
xarModAPIFunc('commerce','user','image',array('src' => xarTplGetImage('buttons/' . xarSessionGetVar('language') . '/'.'button_cancel.gif'),'alt' => '')
</a>';
?>
</td>
  </tr>
</form>