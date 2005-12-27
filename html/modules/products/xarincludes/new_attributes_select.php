<?php
/* --------------------------------------------------------------
   $Id: new_attributes_select.php,v 1.5 2003/12/14 15:59:43 fanta2k Exp $

   XT-Commerce - community made shopping
   http://www.xt-commerce.com

   Copyright (c) 2003 XT-Commerce
   --------------------------------------------------------------
   based on:
   (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
   (c) 2002-2003 osCommerce(new_attributes_select.php); www.oscommerce.com
   (c) 2003  nextcommerce (new_attributes_select.php,v 1.9 2003/08/21); www.nextcommerce.org

   Released under the GNU General Public License
   --------------------------------------------------------------
   Third Party contributions:
   New Attribute Manager v4b                Autor: Mike G | mp3man@internetwork.net | http://downloads.ephing.com
   copy attributes                          Autor: Hubi | http://www.netz-designer.de

   Released under the GNU General Public License
   --------------------------------------------------------------*/

$adminImages = $language ."/admin/images/buttons/";
?>
  <tr>
    <td class="pageHeading" colspan="3"><?php echo $pageTitle; ?></td>
  </tr>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" name="SELECT_PRODUCT" method="post"><input type="hidden" name="action" value="edit">
<?php
  echo "<TR>";
  echo "<TD class=\"main\"><BR><B>Please select a product to edit:<BR></TD>";
  echo "</TR>";
  echo "<TR>";
  echo "<TD class=\"main\"><SELECT NAME=\"current_product_id\">";

  $query = "SELECT * FROM products_description where products_id LIKE '%' AND language_id = '" . $_SESSION['languages_id'] . "' ORDER BY products_name ASC";

  $result = mysql_query($query) or die(mysql_error());

  $matches = mysql_num_rows($result);

  if ($matches) {
    while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
      $title = $line['products_name'];
      $current_product_id = $line['products_id'];

      echo "<OPTION VALUE=\"" . $current_product_id . "\">" . $title;
    }
  } else {
    echo "You have no products at this time.";
  }

  echo "</SELECT>";
  echo "</TD></TR>";

  echo "<TR>";
  echo "<TD class=\"main\">";
    <input type="image" src="#xarTplGetImage($language . '/buttons/button_edit.gif')#" border="0" alt=IMAGE_EDIT >.

  echo "</TD>";
  echo "</TR>";
  // start change for Attribute Copy
?>
<br><br>
<?php
  echo "<TR>";
  echo "<TD class=\"main\"><BR><B>Please select a product to copy attributes from:<BR></TD>";
  echo "</TR>";
  echo "<TR>";
  echo "<TD class=\"main\"><SELECT NAME=\"copy_product_id\">";

  $copy_query = mysql_query("SELECT pd.products_name, pd.products_id FROM products_description pd, products_attributes pa where pa.products_id = pd.products_id AND pd.products_id LIKE '%' AND pd.language_id = '" . $_SESSION['languages_id'] . "' GROUP BY pd.products_id ORDER BY pd.products_name ASC");
  $copy_count = mysql_num_rows($copy_query);

  if ($copy_count) {
      echo '<option value="0">no copy</option>';
      while ($copy_res = mysql_fetch_array($copy_query, MYSQL_ASSOC)) {
          echo '<option value="' . $copy_res['products_id'] . '">' . $copy_res['products_name'] . '</option>';
      }
  }
  else {
      echo 'No products to copy attributes from';
  }
  echo '</select></td></tr>';
  echo "<TR>";
  echo "<TD class=\"main\"><input type=\"image\" src=\"" . $adminImages . "button_edit.gif\"></TD>";
  echo "</TR>";
// end change for Attribute Copy
?>
</form>