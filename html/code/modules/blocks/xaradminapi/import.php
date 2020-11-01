<?php
/**
 * @package modules\blocks
 * @subpackage blocks
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 * @link http://xaraya.info/index.php/release/13.html
 *
 * @author Marc Lutolf <marc@luetolf-carroll.com>
 */
/**
 * Import a block definition from XML
 *
 * @param $args['file'] location of the .xml file containing the object definition, or
 * @param $args['xml'] XML string containing the object definition
 * @return array block id on success, false on failure
 */
function blocks_adminapi_import(Array $args=array())
{
    extract($args);

    if (!isset($prefix)) $prefix = xarDB::getPrefix();
    $prefix .= '_';
    if (!isset($overwrite)) $overwrite = false;

    if (empty($xml) && empty($file)) {
        throw new EmptyParameterException('xml or file');
    } elseif (!empty($file) && (!file_exists($file) || !preg_match('/\.xml$/',$file)) ) {
        // check if we tried to load a file using an old path
        if (xarConfigVars::get(null, 'Site.Core.LoadLegacy') == true && strpos($file, 'modules/') === 0) {
            $file = sys::code() . $file;
            if (!file_exists($file)) {
                throw new BadParameterException($file,'Invalid importfile "#(1)"');
            }
        } else {
            throw new BadParameterException($file,'Invalid importfile "#(1)"');
        }
    }

    if (!empty($file)) {
        $xmlobject = simplexml_load_file($file);
        xarLog::message('Blocks: import file ' . $file, xarLog::LEVEL_INFO);
        
    } elseif (!empty($xml)) {
        // remove garbage from the end
        $xml = preg_replace('/>[^<]+$/s','>', $xml);
        $xmlobject = new SimpleXMLElement($xml);
    }
    // No better way of doing this?
    $dom = dom_import_simplexml ($xmlobject);
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
        $args = array();
        // Get the object's name
        $args['name'] = (string)($xmlobject->attributes()->name);
        xarLog::message('Blocks: importing ' . $args['name'], xarLog::LEVEL_INFO);

        // Check if the block exists
        // Strictly speaking we could have the same name for blocks in different states, but lets not allow that here
        $info = xarMod::apiFunc('blocks', 'instances', 'getitem', array('name' => $args['name']));
        $dupexists = !empty($info);
        if ($dupexists) {
            $msg = 'Duplicate definition for #(1) #(2)';
            $vars = array('block',xarVar::prepForDisplay($args['name']));
            throw new DuplicateException(null,$args['name']);
        }

        $importfields = array('block_id', 'type', 'name', 'title', 'state', 'content');
        foreach($importfields as $field) {
            if (isset($xmlobject->{$field}[0])) {
                $value = base64_decode((string)$xmlobject->{$field}[0]);
                try {
                    $boolean->validate($value, array());
                } catch (Exception $e) {
                    try {
                        $integer->validate($value, array());
                    } catch (Exception $e) {}
                }
                if ($field == 'type') {
                    $type = xarMod::apiFunc('blocks', 'types', 'getitem', array('type' => $value));
                    $args['type_id'] = $type['type_id'];
                } else {
                    $args[$field] = $value;
                }
            } else {
                die(xarML('Missing #(1) field', $field));
            }
        }

        // Oddly enough there is no blocks dd object, so do a direct SQL insert
        $tables = xarDB::getTables();
        sys::import('xaraya.structures.query');
        $q = new Query('INSERT', $tables['block_instances']);
        $q->addfield('name', $args['name']);
        $q->addfield('title', $args['title']);
        $q->addfield('type_id', (int)$args['type_id']);
        $q->addfield('state', (int)$args['state']);
        $q->addfield('content', $args['content']);
//        $q->qecho();exit;
        $q->run();
        $block_id = $q->lastid($tables['block_instances'], 'id');
        return $block_id;

    } else {
        return false;
    }
}

?>