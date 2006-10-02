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
    public $args       = '';                     // special args needed?
    public $aliases    = array();                // aliases for this property
    public $format     = 0;                      // what format type do we have here?
                                                 // 0 = ? what?
                                                 // 1 =

    function __construct($args=array())
    {
        assert('is_array($args)');
        if(!empty($args))
            foreach($args as $key=>$value)
                $this->$key = $value;
    }

    static function clearCache()
    {
        $dbconn = &xarDBGetConn();
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

        $dbconn = &xarDBGetConn();
        $tables = xarDBGetTables();
        $propdefTable = $tables['dynamic_properties_def'];

        // Make sure the db is the same as in the old days
        $reqmods = join(';',$this->reqmodules);
        if($this->format == 0) $this->format = $this->id;

        $sql = "INSERT INTO $propdefTable
                (xar_prop_id, xar_prop_name, xar_prop_label,
                 xar_prop_parent, xar_prop_filepath, xar_prop_class,
                 xar_prop_format, xar_prop_validation, xar_prop_source,
                 xar_prop_reqfiles, xar_prop_reqmodules, xar_prop_args, xar_prop_aliases)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
        if(!isset($stmt))
            $stmt = $dbconn->prepareStatement($sql);

        $bindvars = array(
            (int) $this->id, $this->name, $this->desc,
            $this->parent, $this->filepath, $this->class,
            $this->format, $this->validation, $this->source,
            $this->reqfiles, $reqmods, $this->args, $this->aliases
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

    static function &Retrieve()
    {
        $dbconn =& xarDBGetConn();
        $tables = xarDBGetTables();
        // Sort by required module(s) and then by name
        $query = "SELECT  xar_prop_id, xar_prop_name, xar_prop_label,
                          xar_prop_parent, xar_prop_filepath, xar_prop_class,
                          xar_prop_format, xar_prop_validation, xar_prop_source,
                          xar_prop_reqfiles,xar_prop_reqmodules, xar_prop_args,
                          xar_prop_aliases
                  FROM    $tables[dynamic_properties_def]
                  ORDER BY xar_prop_reqmodules, xar_prop_name";
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
                    $validation,$source,$reqfiles,$reqmodules,$args,$aliases
                ) = $result->fields;

                $property['id']             = $id;
                $property['name']           = $name;
                $property['label']          = $label;
                $property['format']         = $format;
                $property['filepath']       = $filepath;
                $property['validation']     = $validation;
                $property['source']         = $source;
                $property['dependancies']   = $reqfiles;
                $property['requiresmodule'] = $reqmodules;
                $property['args']           = $args;
                $property['propertyClass']  = $class;
                // TODO: this return a serialized array of objects, does that hurt?
                $property['aliases']        = $aliases;

                $proptypes[$id] = $property;
            }
        }
        $result->close();
        return $proptypes;
    }
}
?>