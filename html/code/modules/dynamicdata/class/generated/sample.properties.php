<?php
$properties = [];
$properties[] = array (
  'id' => '36',
  'name' => 'id',
  'label' => 'Id',
  'type' => '21',
  'defaultvalue' => '',
  'source' => 'dynamicdata',
  'translatable' => '0',
  'status' => '34',
  'seq' => '1',
  'configuration' => 'a:4:{s:12:"display_size";s:2:"10";s:17:"display_maxlength";s:2:"30";s:14:"display_layout";s:7:"default";s:15:"display_tooltip";s:35:"A unique identifier for each person";}',
);
$properties[] = array (
  'id' => '37',
  'name' => 'name',
  'label' => 'Name',
  'type' => '2',
  'defaultvalue' => '',
  'source' => 'dynamicdata',
  'translatable' => '0',
  'status' => '33',
  'seq' => '2',
  'configuration' => 'a:6:{s:12:"display_size";s:2:"50";s:17:"display_maxlength";s:3:"254";s:14:"display_layout";s:7:"default";s:15:"display_tooltip";s:17:"The person\'s name";s:21:"validation_min_length";s:1:"1";s:21:"validation_max_length";s:2:"30";}',
);
$properties[] = array (
  'id' => '38',
  'name' => 'age',
  'label' => 'Age',
  'type' => '15',
  'defaultvalue' => '',
  'source' => 'dynamicdata',
  'translatable' => '0',
  'status' => '33',
  'seq' => '3',
  'configuration' => 'a:6:{s:12:"display_size";s:2:"10";s:17:"display_maxlength";s:2:"30";s:14:"display_layout";s:7:"default";s:15:"display_tooltip";s:16:"The person\'s age";s:20:"validation_min_value";s:1:"0";s:20:"validation_max_value";s:3:"125";}',
);
$properties[] = array (
  'id' => '39',
  'name' => 'location',
  'label' => 'Location',
  'type' => '12',
  'defaultvalue' => '',
  'source' => 'dynamicdata',
  'translatable' => '0',
  'status' => '34',
  'seq' => '4',
  'configuration' => 'a:7:{s:12:"display_size";s:2:"50";s:17:"display_maxlength";s:3:"254";s:14:"display_layout";s:7:"default";s:15:"display_tooltip";s:27:"The person\'s favorite place";s:26:"validation_file_extensions";s:20:"gif,jpg,jpeg,png,bmp";s:27:"initialization_image_source";s:3:"url";s:28:"initialization_basedirectory";s:11:"var/uploads";}',
);
$properties[] = array (
  'id' => '40',
  'name' => 'partner',
  'label' => 'Partner',
  'type' => '18281',
  'defaultvalue' => 'dataobject:sample.name',
  'source' => 'dynamicdata',
  'translatable' => '0',
  'status' => '33',
  'seq' => '5',
  'configuration' => 'a:0:{}',
);
$properties[] = array (
  'id' => '41',
  'name' => 'children',
  'label' => 'Children',
  'type' => '18282',
  'defaultvalue' => 'dataobject:sample.name',
  'source' => 'dynamicdata',
  'translatable' => '0',
  'status' => '34',
  'seq' => '6',
  'configuration' => 'a:0:{}',
);
$properties[] = array (
  'id' => '42',
  'name' => 'parents',
  'label' => 'Parents',
  'type' => '18282',
  'defaultvalue' => 'dataobject:sample.name',
  'source' => 'dynamicdata',
  'translatable' => '0',
  'status' => '34',
  'seq' => '7',
  'configuration' => 'a:0:{}',
);
return $properties;
