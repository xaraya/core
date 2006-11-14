<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mrb <marcel@xaraya.com>
 */
xarMod::loadDbInfo('dynamicdata','dynamicdata');
/**
 * Class to model registration information for a property
 *
 * This corresponds directly to the db info we register for a property.
 *
 */
class PropertyRegistration extends Object
{
    public $id         = 0;                      // id of the property, hardcoded to make things easier
    public $name       = 'propertyType';         // what type of property are we dealing with
    public $desc       = 'Property Description'; // description of this type
    public $type       = 1;
    public $parent     = '';                     // this type is derived from?
    public $class      = '';                     // what is the class?
    public $validation = '';                     // what is its default validation?
    public $source     = 'dynamic_data';         // what source is default for this type?
    public $reqfiles   = array();                // do we require some files to be present?
    public $reqmodules = array();                // do we require some modules to be present?
    public $args       = array();                     // special args needed?
    public $aliases    = array();                // aliases for this property
    public $format     = 0;                      // what format type do we have here?
                                                 // 0 = ? what?
                                                 // 1 =

    function __construct(Array $args=array())
    {
        if(!empty($args))
            foreach($args as $key=>$value)
                $this->$key = $value;
    }

    static function clearCache()
    {
        $dbconn = xarDBGetConn();
        $tables = xarDBGetTables();
        $sql = "DELETE FROM $tables[dynamic_properties_def]";
        $res = $dbconn->ExecuteUpdate($sql);
        return $res;
    }

    function getRegistrationInfo(Object $class)
    {
        $this->id   = $class->id;
        $this->name = $class->name;
        $this->desc = $class->desc;
        $this->reqmodules = $class->reqmodules;
        $this->args = $class->args;
        return $this;
    }

    function Register()
    {
        static $stmt = null;

        // Sanity checks (silent)
        foreach($this->reqfiles as $required)
            if(!file_exists($required))
                return false;

        foreach($this->reqmodules as $required)
            if(!xarModIsAvailable($required))
                return false;

        $dbconn = xarDBGetConn();
        $tables = xarDBGetTables();
        $propdefTable = $tables['dynamic_properties_def'];

        // Make sure the db is the same as in the old days
        assert('count($this->reqmodules)==1; /* The reqmodules registration should only contain the name of the owning module */');
        $modInfo = xarMod::getBaseInfo($this->reqmodules[0]);
        $modId = $modInfo['systemid'];

        if($this->format == 0) $this->format = $this->id;

        $sql = "INSERT INTO $propdefTable
                (xar_prop_id, xar_prop_name, xar_prop_label,
                 xar_prop_parent, xar_prop_filepath, xar_prop_class,
                 xar_prop_format, xar_prop_validation, xar_prop_source,
                 xar_prop_reqfiles, xar_prop_modid, xar_prop_args, xar_prop_aliases)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
        if(!isset($stmt))
            $stmt = $dbconn->prepareStatement($sql);

        $bindvars = array(
            (int) $this->id, $this->name, $this->desc,
            $this->parent, $this->filepath, $this->class,
            $this->format, $this->validation, $this->source,
            serialize($this->reqfiles), $modId, is_array($this->args) ? serialize($this->args) : $this->args, serialize($this->aliases)
        );
        $res = $stmt->executeUpdate($bindvars);

        if(!empty($this->aliases))
        {
            foreach($this->aliases as $aliasInfo)
            {
                $aliasInfo->filepath = $this->filepath; // Make sure
                $aliasInfo->class = $this->class;
                $aliasInfo->format = $this->format;
                $aliasInfo->reqmodules = $this->reqmodules;
                // Recursive!!
                $res = $aliasInfo->Register();
            }
        }
        return $res;
    }

    static function Retrieve()
    {
        $dbconn = xarDBGetConn();
        $tables = xarDBGetTables();
        // Sort by required module(s) and then by name
        $query = "SELECT  p.xar_prop_id, p.xar_prop_name, p.xar_prop_label,
                          p.xar_prop_parent, p.xar_prop_filepath, p.xar_prop_class,
                          p.xar_prop_format, p.xar_prop_validation, p.xar_prop_source,
                          p.xar_prop_reqfiles, m.xar_name, p.xar_prop_args,
                          p.xar_prop_aliases
                  FROM    $tables[dynamic_properties_def] p INNER JOIN $tables[modules] m
                  ON      p.xar_prop_modid = m.xar_id
                  ORDER BY m.xar_name, xar_prop_name";
        $result = $dbconn->executeQuery($query);
        $proptypes = array();
        if($result->RecordCount() == 0 )
            $proptypes = xarModAPIFunc(
                'dynamicdata','admin','importpropertytypes',
                array('flush'=>false)
            );
        else
        {
            while($result->next())
            {
                list(
                    $id,$name,$label,$parent,$filepath,$class,$format,
                    $validation,$source,$reqfiles,$modname,$args,$aliases
                ) = $result->fields;

                $property['id']             = $id;
                $property['name']           = $name;
                $property['label']          = $label;
                $property['format']         = $format;
                $property['filepath']       = $filepath;
                $property['validation']     = $validation;
                $property['source']         = $source;
                $property['dependancies']   = unserialize($reqfiles);
                $property['requiresmodule'] = $modname;
                $property['args']           = $args;
                $property['propertyClass']  = $class;
                // TODO: this return a serialized array of objects, does that hurt?
                $property['aliases']        = unserialize($aliases);

                $proptypes[$id] = $property;
            }
        }
        $result->close();
        return $proptypes;
    }
}
?>