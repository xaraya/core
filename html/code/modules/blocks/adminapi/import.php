<?php

/**
 * @package modules\blocks
 * @category Xaraya Web Applications Framework
 * @version 2.6.1
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link https://github.com/mikespub/xaraya-modules
**/

namespace Xaraya\Modules\Blocks\AdminApi;

use Xaraya\Modules\Blocks\MethodClass;
use Xaraya\Modules\Blocks\AdminApi;
use Xaraya\Modules\Blocks\InstancesApi;
use Xaraya\Modules\Blocks\TypesApi;
use BadParameterException;
use DuplicateException;
use EmptyParameterException;
use Exception;
use Query;
use SimpleXMLElement;
use sys;
use ValueValidations;

sys::import('modules.blocks.method');
sys::import('xaraya.validations');

/**
 * blocks adminapi import function
 * @extends MethodClass<AdminApi>
 */
class ImportMethod extends MethodClass
{
    /** functions imported by bermuda_cleanup */

    /**
     * Import a block definition from XML
     * @param mixed $args ['file'] location of the .xml file containing the object definition, or
     * @param mixed $args ['xml'] XML string containing the object definition
     * @return int|bool block id on success, false on failure
     * @see AdminApi::import()
     */
    public function __invoke(array $args = [])
    {
        extract($args);
        /** @var InstancesApi $instancesapi */
        $instancesapi = $this->instancesapi();
        /** @var TypesApi $typesapi */
        $typesapi = $this->typesapi();

        if (!isset($prefix)) {
            $prefix = $this->db()->getPrefix();
        }
        $prefix .= '_';
        if (!isset($overwrite)) {
            $overwrite = false;
        }

        if (empty($xml) && empty($file)) {
            throw new EmptyParameterException('xml or file');
        } elseif (!empty($file) && (!file_exists($file) || !preg_match('/\.xml$/', $file))) {
            throw new BadParameterException($file, 'Invalid importfile "#(1)"');
        }

        if (!empty($file)) {
            $xmlobject = simplexml_load_file($file);
            $this->log()->info('Blocks: import file ' . $file);

        } elseif (!empty($xml)) {
            // remove garbage from the end
            $xml = preg_replace('/>[^<]+$/s', '>', $xml);
            $xmlobject = new SimpleXMLElement($xml);
        }
        // No better way of doing this?
        $dom = dom_import_simplexml($xmlobject);
        $roottag = $dom->tagName;

        sys::import('xaraya.validations');
        $boolean = ValueValidations::get('bool');
        $integer = ValueValidations::get('int');

        if ($roottag == 'block') {

            # --------------------------------------------------------
            #
            # Process an block definition (-def.xml file)
            #
            //FIXME: this unconditionally CLEARS the incoming parameter!!
            $args = [];
            // Get the object's name
            $args['name'] = (string) ($xmlobject->attributes()->name);
            $this->log()->info('Blocks: importing ' . $args['name']);

            // Check if the block exists
            // Strictly speaking we could have the same name for blocks in different states, but lets not allow that here
            $info = $instancesapi->getitem(['name' => $args['name']]);
            $dupexists = !empty($info);
            if ($dupexists) {
                $msg = 'Duplicate definition for #(1) #(2)';
                $vars = ['block',$this->prep()->text($args['name'])];
                throw new DuplicateException(null, $args['name']);
            }

            $importfields = ['block_id', 'type', 'name', 'title', 'state', 'content'];
            foreach ($importfields as $field) {
                if (isset($xmlobject->{$field}[0])) {
                    $value = base64_decode((string) $xmlobject->{$field}[0]);
                    try {
                        $boolean->validate($value, []);
                    } catch (Exception $e) {
                        try {
                            $integer->validate($value, []);
                        } catch (Exception $e) {
                        }
                    }
                    if ($field == 'type') {
                        $type = $typesapi->getitem(['type' => $value]);
                        $args['type_id'] = $type['type_id'];
                    } else {
                        $args[$field] = $value;
                    }
                } else {
                    $this->exit($this->ml('Missing #(1) field', $field));
                    return false;
                }
            }

            // Oddly enough there is no blocks dd object, so do a direct SQL insert
            $tables = $this->db()->getTables();
            sys::import('xaraya.structures.query');
            $q = new Query('INSERT', $tables['block_instances']);
            $q->addfield('name', $args['name']);
            $q->addfield('title', $args['title']);
            $q->addfield('type_id', (int) $args['type_id']);
            $q->addfield('state', (int) $args['state']);
            $q->addfield('content', $args['content']);
            $q->run();
            $block_id = $q->lastid($tables['block_instances'], 'id');
            return $block_id;

        } else {
            return false;
        }
    }
}
