<?php
// ----------------------------------------------------------------------
// Copyright (C) 2004: Marc Lutolf (marcinmilan@xaraya.com)
// Purpose of file:  Configuration functions for commerce
// ----------------------------------------------------------------------
//  based on:
//  (c) 2003 Mario Zanier for XTcommerce
//  (c) 2003  nextcommerce (nextcommerce.sql,v 1.76 2003/08/25); www.nextcommerce.org
// ----------------------------------------------------------------------

function commerce_userapi_delete_file($file){

    $delete= @unlink($file);
    clearstatcache();
    if (@file_exists($file)) {
        $filesys=eregi_replace("/","\\",$file);
        $delete = @system("del $filesys");
        clearstatcache();
        if (@file_exists($file)) {
            $delete = @chmod($file,0775);
            $delete = @unlink($file);
            $delete = @system("del $filesys");
        }
    }
    clearstatcache();
    if (@file_exists($file)) {
        return false;
    }
    else {
    return true;
} // end function
}
?>