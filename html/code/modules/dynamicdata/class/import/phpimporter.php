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
use VirtualObjectFactory;
use BadParameterException;
use sys;

sys::import('modules.dynamicdata.class.objects.virtual');

/**
 * DataObject PHP Importer
 */
class PhpImporter extends JsonImporter
{
    /**
     * Summary of importContent
     * @param ?string $filepath
     * @param ?string $content not applicable for php importer
     * @throws \BadParameterException
     * @return mixed
     */
    public function importContent($filepath = null, $content = null)
    {
        if (empty($filepath) || !file_exists($filepath)) {
            throw new BadParameterException($filepath, 'Invalid import filepath "#(1)"');
        }
        if (!str_ends_with($filepath, '.php')) {
            throw new BadParameterException($filepath, 'Invalid import filetype "#(1)"');
        }

        $objectid = 0;
        if (str_contains($filepath, '-def.')) {
            $objectid = $this->importObjectDef($filepath);

        } elseif (str_contains($filepath, '-dat.')) {
            $objectid = $this->importItems($filepath);

        } else {
            throw new BadParameterException($filepath, 'Invalid import filename "#(1)"');

        }

        return $objectid;
    }

    /**
     * Summary of importObjectDef
     * @param string $filepath
     * @return int|mixed
     */
    public function importObjectDef($filepath)
    {
        $descriptor = static::importDefinition($filepath);
        $objectid = static::createObject($descriptor);
        return $objectid;
    }

    /**
     * Summary of importItems
     * @param string $filepath
     * @return mixed
     */
    public function importItems($filepath)
    {
        $items = include $filepath;
        // @todo import object items someday? See export :-)
        return count($items);
    }

    /**
     * Summary of importDefinition
     * @param string $filepath
     * @param bool $offline
     * @return VirtualObjectDescriptor
     */
    public static function importDefinition($filepath, $offline = false)
    {
        $args = include $filepath;
        return VirtualObjectFactory::getObjectDescriptor($args, $offline);
    }
}
