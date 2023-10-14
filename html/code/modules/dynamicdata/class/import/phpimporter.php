<?php
/**
 * @package modules\dynamicdata
 * @subpackage dynamicdata
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.info/index.php/release/182.html
 *
 * @author mikespub <mikespub@xaraya.com>
 */

namespace Xaraya\DataObject\Import;

use VirtualObjectDescriptor;
use sys;

sys::import('modules.dynamicdata.class.objects.virtual');

/**
 * DataObject PHP Importer
 */
class PhpImporter extends DataObjectImporter
{
    /**
     * Summary of importDefinition
     * @param string $filepath
     * @param bool $offline
     * @return VirtualObjectDescriptor
     */
    public static function importDefinition($filepath, $offline = false)
    {
        $args = include $filepath;
        $arrayargs = ['access', 'config', 'sources', 'relations', 'objects', 'category'];
        foreach ($arrayargs as $name) {
            if (!empty($args[$name]) && is_array($args[$name])) {
                $args[$name] = serialize($args[$name]);
            }
        }
        $descriptor = new VirtualObjectDescriptor($args, $offline);
        return $descriptor;
    }
}
