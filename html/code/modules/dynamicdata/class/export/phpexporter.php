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

namespace Xaraya\DataObject\Export;

use DataObject;
use DataObjectMaster;
use DataPropertyMaster;

/**
 * DataObject PHP Class Exporter (TODO - experimental)
 */
class PhpExporter extends JsonExporter
{
    public function format($info)
    {
        return var_export($info, true);
    }

    public function exportObjectDef()
    {
        $objectdef = $this->getObjectDef();

        $info = '';
        $info = $this->addObjectDef($info, $objectdef);

        return $info;
    }

    /**
     * Summary of getObjectDef
     * @return DataObject|void
     */
    public function getObjectDef()
    {
        // we grab the actual object here
        $myobject = DataObjectMaster::getObject([
            'objectid' => $this->objectid,
            'allprops' => true,
        ]);

        if (!isset($myobject) || empty($myobject->label)) {
            return;
        }

        return $myobject;
    }

    public function addObjectDef($info, $objectdef)
    {
        $info .= '<?php

namespace Xaraya\DataObject\Generated;

use ObjectDescriptor;
';
        $seen = [];
        foreach ($objectdef->properties as $name => $property) {
            $classname = get_class($property);
            if (!empty($seen[$classname])) {
                continue;
            }
            $info .= "use " . $classname . ";\n";
            $seen[$classname] = true;
        }
        $info .= '
/**
$propertyargs = ';
        $info .= var_export($objectdef->descriptor->get('propertyargs'), true);
        //foreach ($objectdef->properties as $name => $property) {
        //    $info .= "\$args['$name'] = " . var_export($property->descriptor, true) . ";\n";
        //}
        $info .= ';
 */

class ' . ucfirst($objectdef->name) . ' extends ObjectDescriptor {
';
        foreach ($objectdef->properties as $name => $property) {
            $info .= "    /** @var " . get_class($property) . " */\n";
            $info .= "    protected \$" . $name . ";\n";
        }
        $info .= '
    public function setArgs(array $args = [])
    {
        parent::setArgs($args);
        // @todo set property values
    }
}
';

        //$file = dirname(__DIR__) . '/generated/' . ucfirst($objectdef->name) . '.php';
        //file_put_contents($file, $info);
        return $info;
    }
}
