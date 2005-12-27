<?php
// ----------------------------------------------------------------------
// Copyright (C) 2004: Marc Lutolf (marcinmilan@xaraya.com)
// Purpose of file:  Configuration functions for commerce
// ----------------------------------------------------------------------
//  based on:
//  (c) 2003 Mario Zanier for XTcommerce
//  (c) 2003  nextcommerce (nextcommerce.sql,v 1.76 2003/08/25); www.nextcommerce.org
// ----------------------------------------------------------------------

// safe download function, file get renamed bevor sending to browser!!
function commerce_userapi_get_download($content_id) {

    $content_query=new xenQuery("SELECT
                    content_file,
                    content_read
                    FROM ".TABLE_PRODUCTS_CONTENT."
                    WHERE content_id='".$content_id."'");

      $q = new xenQuery();
      if(!$q->run()) return;
    $content_data=$q->output();
    // update file counter

    new xenQuery("UPDATE
            ".TABLE_PRODUCTS_CONTENT."
            SET content_read='".($content_data['content_read']+1)."'
            WHERE content_id='".$content_id."'");

    // original filename
    $filename = DIR_FS_CATALOG.'media/products/'.$content_data['content_file'];
    $backup_filename = DIR_FS_CATALOG.'media/products/backup/'.$content_data['content_file'];
    // create md5 hash id from original file
    $orign_hash_id=md5_file($filename);

    clearstatcache();

    // create new filename with timestamp
    $timestamp=str_replace('.','',microtime());
        $timestamp=str_replace(' ','',$timestamp);
        $new_filename=DIR_FS_CATALOG.'media/products/'.$timestamp.strstr($content_data['content_file'],'.');

        // rename file
        rename($filename,$new_filename);


    if (file_exists($new_filename)) {


    header("Content-type: application/force-download");
    header("Content-Disposition: attachment; filename=".$new_filename);
    @readfile($new_filename);
    // rename file to original name
    rename($new_filename,$filename);
    $new_hash_id=md5_file($filename);
    clearstatcache();

    // check hash id of file again, if not same, get backup!
    if ($new_hash_id!=$orign_hash_id) copy($backup_filename,$filename);
    }


}
?>