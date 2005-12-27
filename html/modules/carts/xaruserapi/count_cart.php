<?php
// ----------------------------------------------------------------------
// Copyright (C) 2004: Marc Lutolf (marcinmilan@xaraya.com)
// Purpose of file:  Configuration functions for commerce
// ----------------------------------------------------------------------
//  based on:
//  (c) 2003 XT-Commerce
// ----------------------------------------------------------------------

   // counts total ammount of a product ID in cart.

  function commerce_userapi_count_cart() {

  $id_list=$_SESSION['cart']->get_product_id_list();

  $id_list= explode(', ',$id_list);

  $actual_content=array();

  for ($i=0,$n=sizeof($id_list); $i<$n; $i++) {

  $actual_content[]=array( 'id'=>$id_list[$i],
                           'qty'=>$_SESSION['cart']->get_quantity($id_list[$i]));

  }

  // merge product IDs
  $content=array();
  for ($i=0,$n=sizeof($actual_content);$i<$n; $i++) {

  //$act_id=$actual_content[$i]['id'];
  if (strpos($actual_content[$i]['id'],'{'))  {
    $act_id=substr($actual_content[$i]['id'],0,strpos($actual_content[$i]['id'],'{'));
  } else {
    $act_id=$actual_content[$i]['id'];
  }


  $_SESSION['actual_content'][$act_id]=array('qty'=>$_SESSION['actual_content'][$act_id]['qty']+$actual_content[$i]['qty']);

  }

  }
?>