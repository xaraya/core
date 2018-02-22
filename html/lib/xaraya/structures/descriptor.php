<?php
/**
 * @package core\structures
 * @subpackage structures
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * Generic descriptor for a Data Object in the Dynamic Data sense
 *
 * @todo this does not belong here
**/

class ObjectDescriptor extends DataContainer
{
    protected $args;

    function __construct(array $args=array())
    {
        $this->setArgs($args);
    }

    public function getArgs()
    {
        return $this->args;
    }

    public function refresh(Object $object)
    {
        $publicproperties = $object->getPublicProperties();
        foreach ($this->args as $key => $value) if (in_array($key,$publicproperties)) $object->$key = $value;
    }

    public function store(Object $object)
    {
        $publicproperties = $object->getPublicProperties();
        foreach ($publicproperties as $key => $value) $this->args[$key] = $value;
    }

    public function setArgs(array $args=array())
    {
        if (empty($this->args)) $this->args = $args;
        else foreach($args as $key => $value) if (isset($value)) $this->args[$key] = $value;
    }

    public function exists($arg=null)
    {
        if (empty($arg)) return false;
        return isset($this->args[$arg]);
    }
    
    public function get($arg=null)
    {
        if (empty($arg)) return null;
        if ($this->exists($arg)) return $this->args[$arg];
        return null;
    }

    public function set($arg=null, $value=null)
    {
        if (empty($arg)) return true;
        if ($this->exists($arg)) $this->args[$arg] = $value;
        return true;
    }
}
?>
