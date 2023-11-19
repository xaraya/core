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
 * DataObject JSON Importer
 */
class JsonImporter extends DataObjectImporter
{
    /**
     * Summary of importContent
     * @param ?string $filepath
     * @param ?string $content
     * @throws \BadParameterException
     * @return mixed
     */
    public function importContent($filepath = null, $content = null)
    {
        if (!empty($filepath) && file_exists($filepath)) {
            $content = file_get_contents($filepath);
        }
        if (empty($content)) {
            throw new BadParameterException($filepath, 'Invalid import filepath "#(1)" or content');
        }

        // @todo check content for import type
        $objectid = 0;
        if (str_contains($filepath, '-def.')) {
            $objectid = $this->importObjectDef($content);

        } elseif (str_contains($filepath, '-dat.')) {
            $objectid = $this->importItems($content);

        } else {
            throw new BadParameterException($filepath, 'Invalid import filename "#(1)"');

        }

        return $objectid;
    }

    /**
     * Summary of importObjectDef
     * @param string $content
     * @return int|mixed
     */
    public function importObjectDef($content)
    {
        $descriptor = static::importDefinition($content);
        $objectid = static::createObject($descriptor);
        return $objectid;
    }

    /**
     * Summary of importItems
     * @param string $content
     * @return mixed
     */
    public function importItems($content)
    {
        $items = json_decode($content, true);
        // @todo import object items someday? See export :-)
        return count($items);
    }

    /**
     * Summary of importDefinition
     * @param string $content
     * @param bool $offline
     * @return VirtualObjectDescriptor
     */
    public static function importDefinition($content, $offline = false)
    {
        $args = json_decode($content, true);
        return VirtualObjectFactory::getObjectDescriptor($args, $offline);
    }
}
