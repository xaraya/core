<?php

$object = [
  'objectid' => 4,
  'allprops' => true,
  'id' => '4',
  'name' => 'sample',
  'label' => 'Sample Object',
  'module_id' => 182,
  'itemtype' => 3,
  'class' => 'DataObject',
  'filepath' => 'auto',
  'urlparam' => 'itemid',
  'maxid' => '3',
  'config' => '',
  'access' => [],
  'datastore' => 'dynamicdata',
  'sources' => [],
  'relations' => '',
  'objects' => '',
  'isalias' => '0',
  'moduleid' => 182,
  'tplmodule' => 'dynamicdata',
  'template' => 'sample',
];
$properties = [];
$properties[] = [
  'id' => '36',
  'name' => 'id',
  'label' => 'Id',
  'type' => '21',
  'defaultvalue' => '',
  'source' => 'dynamicdata',
  'translatable' => '0',
  'status' => '34',
  'seq' => '1',
  'configuration' =>
  [
    'display_size' => '10',
    'display_maxlength' => '30',
    'display_layout' => 'default',
    'display_tooltip' => 'A unique identifier for each person',
  ],
];
$properties[] = [
  'id' => '37',
  'name' => 'name',
  'label' => 'Name',
  'type' => '2',
  'defaultvalue' => '',
  'source' => 'dynamicdata',
  'translatable' => '0',
  'status' => '33',
  'seq' => '2',
  'configuration' =>
  [
    'display_size' => '50',
    'display_maxlength' => '254',
    'display_layout' => 'default',
    'display_tooltip' => 'The person\'s name',
    'validation_min_length' => '1',
    'validation_max_length' => '30',
  ],
];
$properties[] = [
  'id' => '38',
  'name' => 'age',
  'label' => 'Age',
  'type' => '15',
  'defaultvalue' => '',
  'source' => 'dynamicdata',
  'translatable' => '0',
  'status' => '33',
  'seq' => '3',
  'configuration' =>
  [
    'display_size' => '10',
    'display_maxlength' => '30',
    'display_layout' => 'default',
    'display_tooltip' => 'The person\'s age',
    'validation_min_value' => '0',
    'validation_max_value' => '125',
  ],
];
$properties[] = [
  'id' => '39',
  'name' => 'location',
  'label' => 'Location',
  'type' => '12',
  'defaultvalue' => '',
  'source' => 'dynamicdata',
  'translatable' => '0',
  'status' => '34',
  'seq' => '4',
  'configuration' =>
  [
    'display_size' => '50',
    'display_maxlength' => '254',
    'display_layout' => 'default',
    'display_tooltip' => 'The person\'s favorite place',
    'validation_file_extensions' => 'gif,jpg,jpeg,png,bmp',
    'initialization_image_source' => 'url',
    'initialization_basedirectory' => 'var/uploads',
  ],
];
$properties[] = [
  'id' => '40',
  'name' => 'partner',
  'label' => 'Partner',
  'type' => '18281',
  'defaultvalue' => 'dataobject:sample.name',
  'source' => 'dynamicdata',
  'translatable' => '0',
  'status' => '33',
  'seq' => '5',
  'configuration' => [],
];
$properties[] = [
  'id' => '41',
  'name' => 'children',
  'label' => 'Children',
  'type' => '18282',
  'defaultvalue' => 'dataobject:sample.name',
  'source' => 'dynamicdata',
  'translatable' => '0',
  'status' => '34',
  'seq' => '6',
  'configuration' => [],
];
$properties[] = [
  'id' => '42',
  'name' => 'parents',
  'label' => 'Parents',
  'type' => '18282',
  'defaultvalue' => 'dataobject:sample.name',
  'source' => 'dynamicdata',
  'translatable' => '0',
  'status' => '34',
  'seq' => '7',
  'configuration' => [],
];
$object['propertyargs'] = $properties;
return $object;
