<?php
// ----------------------------------------------------------------------
// Copyright (C) 2004: Marc Lutolf (marcinmilan@xaraya.com)
// Purpose of file:  Configuration functions for commerce
// ----------------------------------------------------------------------
//  based on:
//  (c) 2003 XT-Commerce
// ----------------------------------------------------------------------

// returns human readeable filesize :)

function commerce_userapi_filesize($file) {
    $a = array("B","KB","MB","GB","TB","PB");

    $pos = 0;
    $size = filesize(DIR_FS_CATALOG.'media/products/'.$file);
    while ($size >= 1024) {
        $size /= 1024;
        $pos++;
    }
    return round($size,2)." ".$a[$pos];
}

?>