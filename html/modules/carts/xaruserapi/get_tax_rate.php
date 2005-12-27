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

function commerce_userapi_get_tax_rate($args)
{
    //FIXME: create an API function for this stuff
    include_once 'modules/xen/xarclasses/xenquery.php';
    $xartables = xarDBGetTables();

    if ( !isset($country_id) && !isset($zone_id) ) {
/*        if (!isset($_SESSION['customer_id'])) {
            $country_id = $configuration['store_country'];
            $zone_id = $configuration['store_zone'];
        }
        else {
            $country_id = $_SESSION['customer_country_id'];
            $zone_id = $_SESSION['customer_zone_id'];
        }
*/    }
$country_id = 1;
$zone_id = 1;
$class_id = 1;
    $q = new xenQuery('SELECT');
    $q->addtable($xartables['commerce_tax_rates'],'tr');
    $q->addtable($xartables['commerce_zones_to_geo_zones'],'za');
    $q->addtable($xartables['commerce_geo_zones'],'tz');
    $q->addfields(array('sum(tax_rate) as tax_rate'));
    $q->leftjoin('tr.tax_zone_id','za.geo_zone_id');
    $q->leftjoin('tz.geo_zone_id','tr.tax_zone_id');
    $c[1] = $q->peq('za.zone_country_id',NULL);
    $c[2] = $q->peq('za.zone_country_id',0);
    $c[3] = $q->peq('za.zone_country_id',$country_id);
    $q->qor($c);
    unset($c);

    $c[1] = $q->peq('za.zone_id',NULL);
    $c[2] = $q->peq('za.zone_id',0);
    $c[3] = $q->peq('za.zone_id',$zone_id);
    $q->qor($c);

    $q->eq('tr.tax_class_id',$class_id);
    $q->setgroup('tr.tax_priority');
    if(!$q->run()) return;
//    $q->qecho();exit;

    if ($q->output() != array()) {
        $tax_multiplier = 1.0;
        foreach ($q->output() as $tax) {
            $tax_multiplier *= 1.0 + ($tax['tax_rate'] / 100);
        }
        return ($tax_multiplier - 1.0) * 100;
    }
    else {
        return 0;
    }
}
 ?>