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

function commerce_admin_content_manager()
{

  require_once(DIR_FS_INC . 'xtc_format_filesize.inc.php');
  require_once(DIR_FS_INC . 'xtc_filesize.inc.php');

  $languages = xtc_get_languages();


 if ($_GET['special']=='delete') {

 new xenQuery("DELETE FROM ".TABLE_CONTENT_MANAGER." where content_id='".$_GET['coID']."'");
 xarRedirectResponse(xarModURL('commerce','admin',(FILENAME_CONTENT_MANAGER));
} // if get special

 if ($_GET['special']=='delete_product') {

 new xenQuery("DELETE FROM ".TABLE_PRODUCTS_CONTENT." where content_id='".$_GET['coID']."'");
 xarRedirectResponse(xarModURL('commerce','admin',(FILENAME_CONTENT_MANAGER,'pID='.$_GET['pID']));
} // if get special

 if ($_GET['id']=='update' or $_GET['id']=='insert') {

        $content_title=xtc_db_prepare_input($_POST['cont_title']);
        $content_header=xtc_db_prepare_input($_POST['cont_heading']);
        $content_text=xtc_db_prepare_input($_POST['cont']);
        $coID=xtc_db_prepare_input($_POST['coID']);
        $upload_file=xtc_db_prepare_input($_POST['file_upload']);
        $content_status=xtc_db_prepare_input($_POST['status']);
        $content_language=xtc_db_prepare_input($_POST['language']);
        $select_file=xtc_db_prepare_input($_POST['select_file']);
        $file_flag=xtc_db_prepare_input($_POST['file_flag']);
        $parent_check=xtc_db_prepare_input($_POST['parent_check']);
        $parent_id=xtc_db_prepare_input($_POST['parent']);
        $group_id=xtc_db_prepare_input($_POST['content_group']);

        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
                if ($languages[$i]['code']==$content_language) $content_language=$languages[$i]['id'];
        } // for

        $error=false; // reset error flag
        if (strlen($content_title) < 1) {
          $error = true;
          $messageStack->add(ERROR_TITLE,'error');
        }  // if

        if ($content_status=='yes'){
        $content_status=1;
        } else{
        $content_status=0;
        }  // if

        if ($parent_check=='yes'){
        $parent_id=$parent_id;
        } else{
        $parent_id='0';
        }  // if



      if ($error == false) {
        // file upload
      if ($select_file!='default') $content_file_name=$select_file;

      if ($content_file = new upload('file_upload', DIR_FS_CATALOG.'media/content/')) {
        $content_file_name=$content_file->filename;
      }  // if


        // update data in table

                                $q->addfield('languages_id',$content_language);
                                $q->addfield('content_title',$content_title);
                                $q->addfield('content_heading',$content_header);
                                $q->addfield('content_text',$content_text);
                                $q->addfield('content_file',$content_file_name);
                                $q->addfield('content_status',$content_status);
                                $q->addfield('parent_id',$parent_id);
                                $q->addfield('content_group',$group_id);
                                $q->addfield('file_flag',$file_flag);
         if ($_GET['id']=='update') {
         xtc_db_perform(TABLE_CONTENT_MANAGER, $sql_data_array, 'update', "content_id = '" . $coID . "'");
        } else {
         xtc_db_perform(TABLE_CONTENT_MANAGER, $sql_data_array);
        } // if get id
        xarRedirectResponse(xarModURL('commerce','admin',(FILENAME_CONTENT_MANAGER));
        } // if error
        } // if

 if ($_GET['id']=='update_product' or $_GET['id']=='insert_product') {

        $content_title=xtc_db_prepare_input($_POST['cont_title']);
        $content_link=xtc_db_prepare_input($_POST['cont_link']);
        $content_language=xtc_db_prepare_input($_POST['language']);
        $product=xtc_db_prepare_input($_POST['product']);
        $upload_file=xtc_db_prepare_input($_POST['file_upload']);
        $filename=xtc_db_prepare_input($_POST['file_name']);
        $coID=xtc_db_prepare_input($_POST['coID']);
        $file_comment=xtc_db_prepare_input($_POST['file_comment']);
        $select_file=xtc_db_prepare_input($_POST['select_file']);


        $error=false; // reset error flag

        for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
                if ($languages[$i]['code']==$content_language) $content_language=$languages[$i]['id'];
        } // for

        if (strlen($content_title) < 1) {
          $error = true;
          $messageStack->add(ERROR_TITLE,'error');
        }  // if


        if ($error == false) {

/* mkdir() wont work with php in safe_mode
        if  (!is_dir(DIR_FS_CATALOG.'media/products/'.$product.'/')) {

        $old_umask = umask(0);
    xtc_mkdirs(DIR_FS_CATALOG.'media/products/'.$product.'/',0777);
        umask($old_umask);

        }
*/
if ($select_file=='default') {

        if ($content_file = new upload('file_upload', DIR_FS_CATALOG.'media/products/')) {
        $content_file_name=$content_file->filename;
        $old_filename=$content_file->filename;
        $timestamp=str_replace('.','',microtime());
        $timestamp=str_replace(' ','',$timestamp);
        $content_file_name=$timestamp.strstr($content_file_name,'.');
        $rename_string=DIR_FS_CATALOG.'media/products/'.$content_file_name;
        rename(DIR_FS_CATALOG.'media/products/'.$old_filename,$rename_string);
        copy($rename_string,DIR_FS_CATALOG.'media/products/backup/'.$content_file_name);
        }
        if ($content_file_name=='') $content_file_name=$filename;
 } else {
  $content_file_name=$select_file;
}
         // if

           // update data in table

                                $q->addfield('products_id',$product);
                                $q->addfield('content_name',$content_title);
                                $q->addfield('content_file',$content_file_name);
                                $q->addfield('content_link',$content_link);
                                $q->addfield('file_comment',$file_comment);
                                $q->addfield('languages_id',$content_language);

         if ($_GET['id']=='update_product') {
         xtc_db_perform(TABLE_PRODUCTS_CONTENT, $sql_data_array, 'update', "content_id = '" . $coID . "'");
         $content_id = xtc_db_insert_id();
        } else {
         xtc_db_perform(TABLE_PRODUCTS_CONTENT, $sql_data_array);
         $content_id = xtc_db_insert_id();
        } // if get id

        // rename filename




        xarRedirectResponse(xarModURL('commerce','admin',(FILENAME_CONTENT_MANAGER,'pID='.$product));
        }// if error


}



if (!$_GET['action']) {
?>
<div class="pageHeading"><br><?php echo HEADING_CONTENT; ?><br></div>
<div class="main"><?php echo CONTENT_NOTE; ?></div>
 <?php
 xtc_spaceUsed(DIR_FS_CATALOG.'media/content/');
echo '<div class="main">'.USED_SPACE.xtc_format_filesize($total).'</div>';
?>
<?php
// Display Content
for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {
        $content=array();


         $content_query=new xenQuery("SELECT
                                        content_id,
                                        categories_id,
                                        parent_id,
                                        languages_id,
                                        content_title,
                                        content_heading,
                                        content_text,
                                        file_flag,
                                        content_file,
                                        content_status,
                                        content_group,
                                        content_delete
                                        FROM ".TABLE_CONTENT_MANAGER."
                                        WHERE languages_id='".$languages[$i]['id']."'
                                        AND parent_id='0'");
      $q = new xenQuery();
      if(!$q->run()) return;
        while ($content_data=$q->output()) {

         $content[]=array(
                        'CONTENT_ID' =>$content_data['content_id'] ,
                        'PARENT_ID' => $content_data['parent_id'],
                        'LANGUAGES_ID' => $content_data['languages_id'],
                        'CONTENT_TITLE' => $content_data['content_title'],
                        'CONTENT_HEADING' => $content_data['content_heading'],
                        'CONTENT_TEXT' => $content_data['content_text'],
                        'FILE_FLAG' => $content_data['file_flag'],
                        'CONTENT_FILE' => $content_data['content_file'],
                        'CONTENT_DELETE' => $content_data['content_delete'],
                        'CONTENT_GROUP' => $content_data['content_group'],
                        'CONTENT_STATUS' => $content_data['content_status']);

        } // while content_data


?>
<br>
<div class="main"><?php echo xtc_image(xarTplGetImage(DIR_WS_LANGUAGES.$languages[$i]['directory'].'/admin/images/'.$languages[$i]['image']).'&nbsp;&nbsp;'.$languages[$i]['name']; ?></div>
<table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent" width="10" ><?php echo TABLE_HEADING_CONTENT_ID; ?></td>
                <td class="dataTableHeadingContent" width="10" >&nbsp;</td>
                <td class="dataTableHeadingContent" width="30%" align="left"><?php echo TABLE_HEADING_CONTENT_TITLE; ?></td>
                <td class="dataTableHeadingContent" width="1%" align="middle"><?php echo TABLE_HEADING_CONTENT_GROUP; ?></td>
                <td class="dataTableHeadingContent" width="25%"align="left"><?php echo TABLE_HEADING_CONTENT_FILE; ?></td>
                <td class="dataTableHeadingContent" nowrap width="5%" align="left"><?php echo TABLE_HEADING_CONTENT_STATUS; ?></td>
                <td class="dataTableHeadingContent" nowrap width="" align="middle"><?php echo TABLE_HEADING_CONTENT_BOX; ?></td>
                <td class="dataTableHeadingContent" width="30%" align="middle"><?php echo TABLE_HEADING_CONTENT_ACTION; ?>&nbsp;</td>
              </tr>
 <?php
for ($ii = 0, $nn = sizeof($content); $ii < $nn; $ii++) {
 $file_flag_sql = new xenQuery("SELECT file_flag_name FROM " . TABLE_CM_FILE_FLAGS . " WHERE file_flag=" . $content[$ii]['FILE_FLAG']);
      $q = new xenQuery();
      if(!$q->run()) return;
 $file_flag_result = $q->output();
 echo '              <tr class="dataTableRow" onmouseover="this.className=\'dataTableRowOver\'" onmouseout="this.className=\'dataTableRow\'">' . "\n";
 if ($content[$ii]['CONTENT_FILE']=='') $content[$ii]['CONTENT_FILE']='database';
 ?>
 <td class="dataTableContent" align="left"><?php echo $content[$ii]['CONTENT_ID']; ?></td>
 <td bgcolor="<?php echo substr((6543216554/$content[$ii]['CONTENT_GROUP']),0,6); ?>" class="dataTableContent" align="left">&nbsp;</td>
 <td class="dataTableContent" align="left"><?php echo $content[$ii]['CONTENT_TITLE']; ?>
 <?php
 if ($content[$ii]['CONTENT_DELETE']=='0'){
 echo '<font color="ff0000">*</font>';
} ?>
 </td>
 <td class="dataTableContent" align="middle"><?php echo $content[$ii]['CONTENT_GROUP']; ?></td>
 <td class="dataTableContent" align="left"><?php echo $content[$ii]['CONTENT_FILE']; ?></td>
 <td class="dataTableContent" align="middle"><?php if ($content[$ii]['CONTENT_STATUS']==0) { echo TEXT_NO; } else { echo TEXT_YES; } ?></td>
 <td class="dataTableContent" align="middle"><?php echo $file_flag_result['file_flag_name']; ?></td>
 <td class="dataTableContent" align="right">
 <a href="">
<?php
 if ($content[$ii]['CONTENT_DELETE']=='1'){
?>
 <a href="<?php echo xarModURL('commerce','admin',(FILENAME_CONTENT_MANAGER,'special=delete&coID='.$content[$ii]['CONTENT_ID']); ?>" onClick="return confirm('<?php echo CONFIRM_DELETE; ?>')">
 <?php echo xtc_image(xarTplGetImage(DIR_WS_ICONS.'delete.gif','Delete','','','style="cursor:hand" onClick="return confirm(\''.DELETE_ENTRY.'\')"').'  '.TEXT_DELETE.'</a>&nbsp;&nbsp;';
} // if content
?>
 <a href="<?php echo xarModURL('commerce','admin',(FILENAME_CONTENT_MANAGER,'action=edit&coID='.$content[$ii]['CONTENT_ID']); ?>">
<?php echo xtc_image(xarTplGetImage(DIR_WS_ICONS.'icon_edit.gif','Edit','','','style="cursor:hand" onClick="return confirm(\''.DELETE_ENTRY.'\')"').'  '.TEXT_EDIT.'</a>'; ?>
 <a style="cursor:hand" onClick="javascript:window.open('<?php echo xarModURL('commerce','admin',(FILENAME_CONTENT_PREVIEW,'coID='.$content[$ii]['CONTENT_ID']); ?>', 'popup', 'toolbar=0, width=640, height=600')"


 ><?php echo xtc_image(xarTplGetImage(DIR_WS_ICONS.'preview.gif','Preview','','','style="cursor:hand"').'&nbsp;&nbsp;'.TEXT_PREVIEW.'</a>'; ?>
 </td>
 </tr>

 <?php
 $content_1='';
         $content_1_query=new xenQuery("SELECT
                                        content_id,
                                        categories_id,
                                        parent_id,
                                        languages_id,
                                        content_title,
                                        content_heading,
                                        content_text,
                                        file_flag,
                                        content_file,
                                        content_status,
                                        content_delete
                                        FROM ".TABLE_CONTENT_MANAGER."
                                        WHERE languages_id='".$i."'
                                        AND parent_id='".$content[$ii]['CONTENT_ID']."'");
      $q = new xenQuery();
      if(!$q->run()) return;
        while ($content_1_data=$q->output()) {

         $content_1[]=array(
                        'CONTENT_ID' =>$content_1_data['content_id'] ,
                        'PARENT_ID' => $content_1_data['parent_id'],
                        'LANGUAGES_ID' => $content_1_data['languages_id'],
                        'CONTENT_TITLE' => $content_1_data['content_title'],
                        'CONTENT_HEADING' => $content_1_data['content_heading'],
                        'CONTENT_TEXT' => $content_1_data['content_text'],
                        'FILE_FLAG' => $content_1_data['file_flag'],
                        'CONTENT_FILE' => $content_1_data['content_file'],
                        'CONTENT_DELETE' => $content_1_data['content_delete'],
                        'CONTENT_STATUS' => $content_1_data['content_status']);
 }
for ($a = 0, $x = sizeof($content_1); $a < $x; $a++) {
if ($content_1[$a]!='') {
 $file_flag_sql = new xenQuery("SELECT file_flag_name FROM " . TABLE_CM_FILE_FLAGS . " WHERE file_flag=" . $content_1[$a]['FILE_FLAG']);
      $q = new xenQuery();
      if(!$q->run()) return;
 $file_flag_result = $q->output();
 echo '<tr class="dataTableRow" onmouseover="this.className=\'dataTableRowOver\'" onmouseout="this.className=\'dataTableRow\'">' . "\n";

 if ($content_1[$a]['CONTENT_FILE']=='') $content_1[$a]['CONTENT_FILE']='database';
 ?>
 <td class="dataTableContent" align="left"><?php echo $content_1[$a]['CONTENT_ID']; ?></td>
 <td class="dataTableContent" align="left">--<?php echo $content_1[$a]['CONTENT_TITLE']; ?></td>
 <td class="dataTableContent" align="left"><?php echo $content_1[$a]['CONTENT_FILE']; ?></td>
 <td class="dataTableContent" align="middle"><?php if ($content_1[$a]['CONTENT_STATUS']==0) { echo TEXT_NO; } else { echo TEXT_YES; } ?></td>
 <td class="dataTableContent" align="middle"><?php echo $file_flag_result['file_flag_name']; ?></td>
 <td class="dataTableContent" align="right">
 <a href="">
<?php
 if ($content_1[$a]['CONTENT_DELETE']=='1'){
?>
 <a href="<?php echo xarModURL('commerce','admin',(FILENAME_CONTENT_MANAGER,'special=delete&coID='.$content_1[$a]['CONTENT_ID']); ?>" onClick="return confirm('<?php echo CONFIRM_DELETE; ?>')">
 <?php echo xtc_image(xarTplGetImage(DIR_WS_ICONS.'delete.gif','Delete','','','style="cursor:hand" onClick="return confirm(\''.DELETE_ENTRY.'\')"').'  '.TEXT_DELETE.'</a>&nbsp;&nbsp;';
} // if content
?>
 <a href="<?php echo xarModURL('commerce','admin',(FILENAME_CONTENT_MANAGER,'action=edit&coID='.$content_1[$a]['CONTENT_ID']); ?>">
<?php echo xtc_image(xarTplGetImage(DIR_WS_ICONS.'icon_edit.gif','Edit','','','style="cursor:hand" onClick="return confirm(\''.DELETE_ENTRY.'\')"').'  '.TEXT_EDIT.'</a>'; ?>
 <a style="cursor:hand" onClick="javascript:window.open('<?php echo xarModURL('commerce','admin',(FILENAME_CONTENT_PREVIEW,'coID='.$content_1[$a]['CONTENT_ID']); ?>', 'popup', 'toolbar=0, width=640, height=600')"


 ><?php echo xtc_image(xarTplGetImage(DIR_WS_ICONS.'preview.gif','Preview','','','style="cursor:hand"').'&nbsp;&nbsp;'.TEXT_PREVIEW.'</a>'; ?>
 </td>
 </tr>


<?php
}
} // for content
} // for language
?>
</table>


<?php
}
} else {

switch ($_GET['action']) {
// Diplay Editmask
 case 'new':
 case 'edit':
 if ($_GET['action']!='new') {
        $content_query=new xenQuery("SELECT
                                        content_id,
                                        categories_id,
                                        parent_id,
                                        languages_id,
                                        content_title,
                                        content_heading,
                                        content_text,
                                        file_flag,
                                        content_file,
                                        content_status,
                                        content_group,
                                        content_delete
                                        FROM ".TABLE_CONTENT_MANAGER."
                                        WHERE content_id='".$_GET['coID']."'");

      $q = new xenQuery();
      if(!$q->run()) return;
        $content=$q->output();
}
        $languages_array = array();



  for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {

  if ($languages[$i]['id']==$content['languages_id']) {
         $languages_selected=$languages[$i]['code'];
         $languages_id=$languages[$i]['id'];
        }
    $languages_array[] = array('id' => $languages[$i]['code'],
               'text' => $languages[$i]['name']);

  } // for
  if ($languages_id!='') $query_string='languages_id='.$languages_id.' AND';
    $categories_query=new xenQuery("SELECT
                                        content_id,
                                        content_title
                                        FROM ".TABLE_CONTENT_MANAGER."
                                        WHERE ".$query_string." parent_id='0'
                                        AND content_id!='".$_GET['coID']."'");
      $q = new xenQuery();
      if(!$q->run()) return;
  while ($categories_data=$q->output()) {

  $categories_array[]=array(
                        'id'=>$categories_data['content_id'],
                        'text'=>$categories_data['content_title']);
 }
?>
<br><br>
<?php
 if ($_GET['action']!='new') {
echo xtc_draw_form('edit_content',FILENAME_CONTENT_MANAGER,'action=edit&id=update&coID='.$_GET['coID'],'post','enctype="multipart/form-data"').
  <input type="hidden" name="coID" value="#$_GET['coID']#">
} else {
echo xtc_draw_form('edit_content',FILENAME_CONTENT_MANAGER,'action=edit&id=insert&coID='.$_GET['coID'],'post','enctype="multipart/form-data"').
  <input type="hidden" name="coID" value="#$_GET['coID']#">
} ?>
<table class="main" width="100%" border="0">
   <tr>
      <td width="10%"><?php echo TEXT_LANGUAGE; ?></td>
      <td width="90%"><?php echo commerce_userapi_draw_pull_down_menu('language',$languages_array,$languages_selected); ?></td>
   </tr>
<?php
if ($content['content_delete']!=0 or $_GET['action']=='new') {
?>
      <tr>
      <td width="10%"><?php echo TEXT_GROUP; ?></td>
      <td width="90%"><?php echo xtc_draw_input_field('content_group',$content['content_group'],'size="5"'); ?><?php echo TEXT_GROUP_DESC; ?></td>
   </tr>
<?php
} else {
  <input type="hidden" name="content_group" value="#$content['content_group']#">
      <tr>
      <td width="10%"><?php echo TEXT_GROUP; ?></td>
      <td width="90%"><?php echo $content['content_group']; ?></td>
   </tr>
<?php
}
$file_flag_sql = new xenQuery("SELECT file_flag as id, file_flag_name as text FROM " . TABLE_CM_FILE_FLAGS);
      $q = new xenQuery();
      if(!$q->run()) return;
while($file_flag = $q->output()) {
    $file_flag_array[] = array('id' => $file_flag['id'], 'text' => $file_flag['text']);
}
?>
      <tr>
      <td width="10%"><?php echo TEXT_FILE_FLAG; ?></td>
      <td width="90%"><?php echo commerce_userapi_draw_pull_down_menu('file_flag',$file_flag_array,$content['file_flag']); ?></td>
   </tr>
<?php
/*  build in not completed yet
      <tr>
      <td width="10%"><?php echo TEXT_PARENT; ?></td>
      <td width="90%"><?php echo commerce_userapi_draw_pull_down_menu('parent',$categories_array,$content['parent_id']); ?><?php echo xtc_draw_checkbox_field('parent_check', 'yes',false).' '.TEXT_PARENT_DESCRIPTION; ?></td>
   </tr>
*/
?>
      <tr>
      <td valign="top" width="10%"><?php echo TEXT_STATUS; ?></td>
      <td width="90%"><?php
      if ($content['content_status']=='1') {
      echo xtc_draw_checkbox_field('status', 'yes',true).' '.TEXT_STATUS_DESCRIPTION;
      } else {
      echo xtc_draw_checkbox_field('status', 'yes',false).' '.TEXT_STATUS_DESCRIPTION;
      }

      ?><br><br></td>
   </tr>
   <tr>
      <td width="10%"><?php echo TEXT_TITLE; ?></td>
      <td width="90%"><?php echo xtc_draw_input_field('cont_title',$content['content_title'],'size="60"'); ?></td>
   </tr>
   <tr>
      <td width="10%"><?php echo TEXT_HEADING; ?></td>
      <td width="90%"><?php echo xtc_draw_input_field('cont_heading',$content['content_heading'],'size="60"'); ?></td>
   </tr>
   <tr>
      <td width="10%" valign="top"><?php echo TEXT_UPLOAD_FILE; ?></td>
      <td width="90%"><?php echo xtc_draw_file_field('file_upload').' '.TEXT_UPLOAD_FILE_LOCAL; ?></td>
   </tr>
         <tr>
      <td width="10%" valign="top"><?php echo TEXT_CHOOSE_FILE; ?></td>
      <td width="90%">
<?php
 if ($dir= opendir(DIR_FS_CATALOG.'media/content/')){
 while  (($file = readdir($dir)) !==false) {
        if (is_file( DIR_FS_CATALOG.'media/content/'.$file) and ($file !="index.html")){
        $files[]=array(
                        'id' => $file,
                        'text' => $file);
        }//if
        } // while
        closedir($dir);
 }
 // set default value in dropdown!
if ($content['content_file']=='') {
$default_array[]=array('id' => 'default','text' => TEXT_SELECT);
$default_value='default';
$files=array_merge($default_array,$files);
} else {
$default_array[]=array('id' => 'default','text' => TEXT_NO_FILE);
$default_value=$content['content_file'];
$files=array_merge($default_array,$files);
}
echo '<br>'.TEXT_CHOOSE_FILE_SERVER.'</br>';
echo commerce_userapi_draw_pull_down_menu('select_file',$files,$default_value);
      if ($content['content_file']!='') {
        echo TEXT_CURRENT_FILE.' <b>'.$content['content_file'].'</b><br>';
        }



?>
      </td>
      </td>
   </tr>
   <tr>
      <td width="10%" valign="top"></td>
      <td colspan="90%" valign="top"><br><?php echo TEXT_FILE_DESCRIPTION; ?></td>
   </tr>
   <tr>
      <td width="10%" valign="top"><?php echo TEXT_CONTENT; ?></td>

      <td width="90%">
   <?php
if (USE_SPAW=='true') {
$sw = new SPAW_Wysiwyg(
              $control_name='cont', // control's name
              $value=stripslashes($content['content_text']),                  // initial value
              $lang='',                   // language
              $mode = '',                 // toolbar mode
              $theme='default',                  // theme (skin)
              $width='100%',              // width
              $height='500px',            // height
              $css_stylesheet='',         // css stylesheet file for content
              $dropdown_data=''           // data for dropdowns (style, font, etc.)
            );


$sw->show();
} else {
echo xtc_draw_textarea_field('cont','','100','35',$content['content_text']);
}
?>



      </td>
   </tr>



    <tr>
        <td colspan="2" align="right" class="main">
<input type="image" src="#xarTplGetImage('buttons/' . xarSessionGetVar('language') . '/'.'button_save.gif')#" border="0" alt=IMAGE_SAVE>
<a href="<?php echo xarModURL('commerce','admin',(FILENAME_CONTENT_MANAGER); ?>"><?php echo xarModAPIFunc('commerce','user','image',array('src' => xarTplGetImage('buttons/' . xarSessionGetVar('language') . '/'.'button_back.gif'),'alt' => IMAGE_NEW_BACK);; ?></a></td>
   </tr>
</table>
</form>
<?php
 break;

 case 'edit_products_content':
 case 'new_products_content':

  if ($_GET['action']=='edit_products_content') {
        $content_query=new xenQuery("SELECT
                                        content_id,
                                        products_id,
                                        content_name,
                                        content_file,
                                        content_link,
                                        languages_id,
                                        file_comment,
                                        content_read

                                        FROM ".TABLE_PRODUCTS_CONTENT."
                                        WHERE content_id='".$_GET['coID']."'");

      $q = new xenQuery();
      if(!$q->run()) return;
        $content=$q->output();
}

 // get products names.
 $products_query=new xenQuery("SELECT
                                products_id,
                                products_name
                                FROM ".TABLE_PRODUCTS_DESCRIPTION."
                                WHERE language_id='".$_SESSION['languages_id']."'");
 $products_array='';

      $q = new xenQuery();
      if(!$q->run()) return;
 while ($products_data=$q->output()) {

 $products_array[]=array(
                        'id' => $products_data['products_id'],
                        'text' => $products_data['products_name']);
}

 // get languages
 $languages_array = array();



  for ($i = 0, $n = sizeof($languages); $i < $n; $i++) {

  if ($languages[$i]['id']==$content['languages_id']) {
         $languages_selected=$languages[$i]['code'];
         $languages_id=$languages[$i]['id'];
        }
    $languages_array[] = array('id' => $languages[$i]['code'],
               'text' => $languages[$i]['name']);

  } // for

  // get used content files
  $content_files_query=new xenQuery("SELECT DISTINCT
                                content_name,
                                content_file
                                FROM ".TABLE_PRODUCTS_CONTENT."
                                WHERE content_file!=''");
 $content_files='';

      $q = new xenQuery();
      if(!$q->run()) return;
 while ($content_files_data=$q->output()) {

 $content_files[]=array(
                        'id' => $content_files_data['content_file'],
                        'text' => $content_files_data['content_name']);
}

 // add default value to array
 $default_array[]=array('id' => 'default','text' => TEXT_SELECT);
 $default_value='default';
 $content_files=array_merge($default_array,$content_files);
 // mask for product content

 if ($_GET['action']!='new_products_content') {
 ?>
 <?php echo xtc_draw_form('edit_content',FILENAME_CONTENT_MANAGER,'action=edit_products_content&id=update_product&coID='.$_GET['coID'],'post','enctype="multipart/form-data"').
   <input type="hidden" name="coID" value="#$_GET['coID']#">
<?php
} else {
?>
<?php echo xtc_draw_form('edit_content',FILENAME_CONTENT_MANAGER,'action=edit_products_content&id=insert_product','post','enctype="multipart/form-data"');   ?>
<?php
}
?>
 <div class="main"><?php echo TEXT_CONTENT_DESCRIPTION; ?></div>
 <table class="main" width="100%" border="0">
   <tr>
      <td width="10%"><?php echo TEXT_PRODUCT; ?></td>
      <td width="90%"><?php echo commerce_userapi_draw_pull_down_menu('product',$products_array,$content['products_id']); ?></td>
   </tr>
      <tr>
      <td width="10%"><?php echo TEXT_LANGUAGE; ?></td>
      <td width="90%"><?php echo commerce_userapi_draw_pull_down_menu('language',$languages_array,$languages_selected); ?></td>
   </tr>
      <tr>
      <td width="10%"><?php echo TEXT_TITLE_FILE; ?></td>
      <td width="90%"><?php echo xtc_draw_input_field('cont_title',$content['content_name'],'size="60"'); ?></td>
   </tr>
      <tr>
      <td width="10%"><?php echo TEXT_LINK; ?></td>
      <td width="90%"><?php  echo xtc_draw_input_field('cont_link',$content['content_link'],'size="60"'); ?></td>
   </tr>
      <tr>
      <td width="10%" valign="top"><?php echo TEXT_FILE_DESC; ?></td>
      <td width="90%"><?php
      if (USE_SPAW=='true') {
             $sw = new SPAW_Wysiwyg('file_comment' /*name*/,stripslashes($content['file_comment']) /*value*/,
                       'de' /*language*/, 'sidetable' /*toolbar mode*/, 'default' /*theme*/,
                       '550px' /*width*/, '350px' /*height*/);
                       $sw->show();
         } else {
          echo xtc_draw_textarea_field('file_comment','','100','15',$content['file_comment']);
         }
        ?></td>
   </tr>
      <tr>
      <td width="10%"><?php echo TEXT_CHOOSE_FILE; ?></td>
      <td width="90%"><?php echo commerce_userapi_draw_pull_down_menu('select_file',$content_files,$default_value); ?><?php echo ' '.TEXT_CHOOSE_FILE_DESC; ?></td>
   </tr>
      <tr>
      <td width="10%" valign="top"><?php echo TEXT_UPLOAD_FILE; ?></td>
      <td width="90%"><?php echo xtc_draw_file_field('file_upload').' '.TEXT_UPLOAD_FILE_LOCAL; ?></td>
   </tr>
 <?php
 if ($content['content_file']!='') {
 ?>

         <tr>
      <td width="10%"><?php echo TEXT_FILENAME; ?></td>
      <td width="90%">
  <input type="hidden" name="file_name" value="#$content['content_file']#">
      .'<b>'.xtc_image(xarTplGetImage(DIR_WS_CATALOG.'admin/images/icons/icon'.strstr($content['content_file'],'.').'.gif').$content['content_file'].'</b>'; ?></td>
   </tr>
  <?php
}
?>
       <tr>
        <td colspan="2" align="right" class="main"><?php echo <input type="image" src="#xarTplGetImage('buttons/' . xarSessionGetVar('language') . '/'.'button_save.gif')#" border="0" alt=IMAGE_SAVE>; ?><a href="<?php echo xarModURL('commerce','admin',(FILENAME_CONTENT_MANAGER); ?>"><?php echo xarModAPIFunc('commerce','user','image',array('src' => xarTplGetImage('buttons/' . xarSessionGetVar('language') . '/'.'button_back.gif'),'alt' => IMAGE_NEW_BACK);; ?></a></td>
   </tr>
   </form>
   </table>

 <?php

 break;


}
}

if (!$_GET['action']) {
?>

<a href="<?php echo xarModURL('commerce','admin',(FILENAME_CONTENT_MANAGER,'action=new'); ?>">
  xarModAPIFunc('commerce','user','image',array('src' => xarTplGetImage('buttons/' . xarSessionGetVar('language') . '/'.'button_new_content.gif'))
</a>
<?php
}
?>
</td>
          </tr>
        </table>
 <?php
 if (!$_GET['action']) {
 // products content
 // load products_ids into array

 $products_id_query=new xenQuery("SELECT DISTINCT
                                pc.products_id,
                                pd.products_name
                                FROM ".TABLE_PRODUCTS_CONTENT." pc, ".TABLE_PRODUCTS_DESCRIPTION." pd
                                WHERE pd.products_id=pc.products_id and pd.language_id='".$_SESSION['languages_id']."'");

 $products_ids='';
      $q = new xenQuery();
      if(!$q->run()) return;
 while ($products_id_data=$q->output()) {

        $products_ids[]=array(
                        'id'=>$products_id_data['products_id'],
                        'name'=>$products_id_data['products_name']);

        } // while


 ?>
 <div class="pageHeading"><br><?php echo HEADING_PRODUCTS_CONTENT; ?><br></div>
  <?php
 xtc_spaceUsed(DIR_FS_CATALOG.'media/products/');
echo '<div class="main">'.USED_SPACE.xtc_format_filesize($total).'</div></br>';
?>
 <table border="0" width="100%" cellspacing="0" cellpadding="2">
    <tr class="dataTableHeadingRow">
     <td class="dataTableHeadingContent" nowrap width="5%" ><?php echo TABLE_HEADING_PRODUCTS_ID; ?></td>
     <td class="dataTableHeadingContent" width="95%" align="left"><?php echo TABLE_HEADING_PRODUCTS; ?></td>
</tr>
<?php

for ($i=0,$n=sizeof($products_ids); $i<$n; $i++) {
 echo '<tr class="dataTableRow" onmouseover="this.className=\'dataTableRowOver\'" onmouseout="this.className=\'dataTableRow\'">' . "\n";

 ?>
 <td class="dataTableContent_products" align="left"><?php echo $products_ids[$i]['id']; ?></td>
 <td class="dataTableContent_products" align="left"><b><?php echo xtc_image(xarTplGetImage(DIR_WS_CATALOG.'images/icons/arrow.gif'); ?><a href="<?php echo xarModURL('commerce','admin',(FILENAME_CONTENT_MANAGER,'pID='.$products_ids[$i]['id']);?>"><?php echo $products_ids[$i]['name']; ?></a></b></td>
 </tr>
<?php
if ($_GET['pID']) {
// display content elements
        $content_query=new xenQuery("SELECT
                                        content_id,
                                        content_name,
                                        content_file,
                                        content_link,
                                        languages_id,
                                        file_comment,
                                        content_read
                                        FROM ".TABLE_PRODUCTS_CONTENT."
                                        WHERE products_id='".$_GET['pID']."' order by content_name");
        $content_array='';
      $q = new xenQuery();
      if(!$q->run()) return;
        while ($content_data=$q->output()) {

                $content_array[]=array(
                                        'id'=> $content_data['content_id'],
                                        'name'=> $content_data['content_name'],
                                        'file'=> $content_data['content_file'],
                                        'link'=> $content_data['content_link'],
                                        'comment'=> $content_data['file_comment'],
                                        'languages_id'=> $content_data['languages_id'],
                                        'read'=> $content_data['content_read']);

                } // while content data

if ($_GET['pID']==$products_ids[$i]['id']){
?>

<tr>
 <td class="dataTableContent" align="left"></td>
 <td class="dataTableContent" align="left">

 <table border="0" width="100%" cellspacing="0" cellpadding="2">
    <tr class="dataTableHeadingRow">
    <td class="dataTableHeadingContent" nowrap width="2%" ><?php echo TABLE_HEADING_PRODUCTS_CONTENT_ID; ?></td>
    <td class="dataTableHeadingContent" nowrap width="2%" >&nbsp;</td>
    <td class="dataTableHeadingContent" nowrap width="5%" ><?php echo TABLE_HEADING_LANGUAGE; ?></td>
    <td class="dataTableHeadingContent" nowrap width="15%" ><?php echo TABLE_HEADING_CONTENT_NAME; ?></td>
    <td class="dataTableHeadingContent" nowrap width="30%" ><?php echo TABLE_HEADING_CONTENT_FILE; ?></td>
    <td class="dataTableHeadingContent" nowrap width="1%" ><?php echo TABLE_HEADING_CONTENT_FILESIZE; ?></td>
    <td class="dataTableHeadingContent" nowrap align="middle" width="20%" ><?php echo TABLE_HEADING_CONTENT_LINK; ?></td>
    <td class="dataTableHeadingContent" nowrap width="5%" ><?php echo TABLE_HEADING_CONTENT_HITS; ?></td>
    <td class="dataTableHeadingContent" nowrap width="20%" ><?php echo TABLE_HEADING_CONTENT_ACTION; ?></td>
    </tr>

<?php

 for ($ii=0,$nn=sizeof($content_array); $ii<$nn; $ii++) {

 echo '<tr class="dataTableRow" onmouseover="this.className=\'dataTableRowOver\'" onmouseout="this.className=\'dataTableRow\'">' . "\n";

 ?>
 <td class="dataTableContent" align="left"><?php echo  $content_array[$ii]['id']; ?> </td>
 <td class="dataTableContent" align="left"><?php



 if ($content_array[$ii]['file']!='') {

 echo xtc_image(xarTplGetImage(DIR_WS_CATALOG.'admin/images/icons/icon_'.str_replace('.','',strstr($content_array[$ii]['file'],'.')).'.gif');
} else {
echo xtc_image(xarTplGetImage(DIR_WS_CATALOG.'admin/images/icons/icon_link.gif');
}

for ($xx=0,$zz=sizeof($languages); $xx<$zz;$xx++){
    if ($languages[$xx]['id']==$content_array[$ii]['languages_id']) {
    $lang_dir=$languages[$xx]['directory'];
    break;
    }
}

?>
</td>
 <td class="dataTableContent" align="left"><?php echo xtc_image(xarTplGetImage(DIR_WS_CATALOG.'lang/'.$lang_dir.'/admin/images/icon.gif'); ?></td>
 <td class="dataTableContent" align="left"><?php echo $content_array[$ii]['name']; ?></td>
 <td class="dataTableContent" align="left"><?php echo $content_array[$ii]['file']; ?></td>
 <td class="dataTableContent" align="left"><?php echo xtc_filesize($content_array[$ii]['file']); ?></td>
 <td class="dataTableContent" align="left" align="middle"><?php
 if ($content_array[$ii]['link']!='') {
 echo '<a href="'.$content_array[$ii]['link'].'" target="new">'.$content_array[$ii]['link'].'</a>';
}
 ?>
  &nbsp;</td>
 <td class="dataTableContent" align="left"><?php echo $content_array[$ii]['read']; ?></td>
 <td class="dataTableContent" align="left">

  <a href="<?php echo xarModURL('commerce','admin',(FILENAME_CONTENT_MANAGER,'special=delete_product&coID='.$content_array[$ii]['id']).'&pID='.$products_ids[$i]['id']; ?>" onClick="return confirm('<?php echo CONFIRM_DELETE; ?>')">
 <?php

 echo xtc_image(xarTplGetImage(DIR_WS_ICONS.'delete.gif','Delete','','','style="cursor:hand" onClick="return confirm(\''.DELETE_ENTRY.'\')"').'  '.TEXT_DELETE.'</a>&nbsp;&nbsp;';

?>
 <a href="<?php echo xarModURL('commerce','admin',(FILENAME_CONTENT_MANAGER,'action=edit_products_content&coID='.$content_array[$ii]['id']); ?>">
<?php echo xtc_image(xarTplGetImage(DIR_WS_ICONS.'icon_edit.gif','Edit','','','style="cursor:hand" onClick="return confirm(\''.DELETE_ENTRY.'\')"').'  '.TEXT_EDIT.'</a>'; ?>

<?php
// display preview button if filetype
// .gif,.jpg,.png,.html,.htm,.txt,.tif,.bmp
if (    eregi('.gif',$content_array[$ii]['file'])
    or
    eregi('.jpg',$content_array[$ii]['file'])
    or
    eregi('.png',$content_array[$ii]['file'])
    or
    eregi('.html',$content_array[$ii]['file'])
    or
    eregi('.htm',$content_array[$ii]['file'])
    or
    eregi('.txt',$content_array[$ii]['file'])
    or
    eregi('.bmp',$content_array[$ii]['file'])
    ) {
?>
 <a style="cursor:hand" onClick="javascript:window.open('<?php echo xarModURL('commerce','admin',(FILENAME_CONTENT_PREVIEW,'pID=media&coID='.$content_array[$ii]['id']); ?>', 'popup', 'toolbar=0, width=640, height=600')"


 ><?php echo xtc_image(xarTplGetImage(DIR_WS_ICONS.'preview.gif','Preview','','','style="cursor:hand"').'&nbsp;&nbsp;'.TEXT_PREVIEW.'</a>'; ?>
<?php
}
?>



 </td>
 </tr>

<?php

} // for content_array
echo '</table></td></tr>';
}
} // for
}
?>


 </table>
 <a href="<?php echo xarModURL('commerce','admin',(FILENAME_CONTENT_MANAGER,'action=new_products_content'); ?>">
         xarModAPIFunc('commerce','user','image',array('src' => xarTplGetImage('buttons/' . xarSessionGetVar('language') . '/'.'button_new_content.gif'));
</a>
 <?php
} // if !$_GET['action']
}
?>