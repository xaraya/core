<?php
/**
 * Dealing with xaraya template tags and attributes
 *
 * @package blocklayout
 * @subpackage template
 * @copyright The Digital Development Foundataion, 2006
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @author Marcel van der Boom <mrb@hsdev.com>
**/

sys::import('exceptions.types');
class DuplicateTagException extends DuplicationExceptions
{
    protected $message = 'The tag definition for the tag: "#(1)" already exists.';
}

/**
 * Defines for template handling
 *
 */
define ('XAR_TPL_REQUIRED', 0); // default for attributes
define ('XAR_TPL_OPTIONAL', 2);
define ('XAR_TPL_STRING', 64);
define ('XAR_TPL_BOOLEAN', 128);
define ('XAR_TPL_INTEGER', 256);
define ('XAR_TPL_FLOAT', 512);
define ('XAR_TPL_ANY', XAR_TPL_STRING|XAR_TPL_BOOLEAN|XAR_TPL_INTEGER|XAR_TPL_FLOAT);

/**
 * Defines for tag properties
 *
**/
define('XAR_TPL_TAG_HASCHILDREN'               ,1);
define('XAR_TPL_TAG_HASTEXT'                   ,2);
define('XAR_TPL_TAG_ISASSIGNABLE'              ,4);
define('XAR_TPL_TAG_ISPHPCODE'                 ,8);
define('XAR_TPL_TAG_NEEDASSIGNMENT'            ,16);
define('XAR_TPL_TAG_NEEDPARAMETER'             ,32);
/**
 * Model of a template tag
 *
 * Only used for custom tags atm
 *
 * @package blocklayout
 * @access  public
 * @todo Make this more general
 * @todo _module, _type and _func and _handler introduce unneeded redundancy
 * @todo pass handler check at template registration someday (<mrb>what does this mean?)
 * @todo abstract the storing of the tag registration in a cache like interface (TagTemplateCache)
**/
/* DONT RENAME THIS CLASS YET */
class xarTemplateTag extends Object
{
    const NAME_REGEX = '^[a-z][-_a-z0-9]*$';

    // These need to stay public otherwise the (de)serialization from their storage in the database doesnt work!
    public $_name = NULL;          // Name of the tag
    public $_attributes = array(); // Array with the supported attributes
    public $_handler = NULL;       // Name of the handler function
    public $_module;               // Modulename
    public $_type;                 // Type of the handler (user/admin etc.)
    public $_func;                 // Function name
    // properties for registering what kind of tag we have here
    public $_hasChildren = false;
    public $_hasText = false;
    public $_isAssignable = false;
    public $_isPHPCode = true;
    public $_needAssignment = false;
    public $_needParameter = false;

    /**
     * Constructor
     *
     * @return void
     * @throws BadParameterException
    **/
    function __construct($module, $name, $attributes = array(), $handler = NULL, $flags = XAR_TPL_TAG_ISPHPCODE)
    {
        // See defines at top of file
        if (!eregi(self::NAME_REGEX, $name)) {
            throw new BadParameterException($name,'Illegal tag definition: "#(1)" is an invalid tag name.');
        }

        if (preg_match("/($module)_(\w+)api_(.*)/",$handler,$matches)) {
            $this->_type = $matches[2];
            $this->_func = $matches[3];
        } else {
            throw new BadParameterException($handler,'Illegal tag definition: "#(1)" is an invalid handler.');
        }

        if (!is_integer($flags)) {
            throw new BadParameterException($flags,'Illegal tag registration flags ("#(1)"): flags must be of integer type.');
        }

        // Everything seems to be in order, set the properties
        $this->_name = $name;
        $this->_handler = $handler;
        $this->_module = $module;

        if (is_array($attributes)) {
            $this->_attributes = $attributes;
        }
        $this->setFlags($flags);
    }

    private function setFlags($flags)
    {
        $this->_hasChildren    = ($flags & XAR_TPL_TAG_HASCHILDREN)    == XAR_TPL_TAG_HASCHILDREN;
        $this->_hasText        = ($flags & XAR_TPL_TAG_HASTEXT)        == XAR_TPL_TAG_HASTEXT;
        $this->_isAssignable   = ($flags & XAR_TPL_TAG_ISASSIGNABLE)   == XAR_TPL_TAG_ISASSIGNABLE;
        $this->_isPHPCode      = ($flags & XAR_TPL_TAG_ISPHPCODE)      == XAR_TPL_TAG_ISPHPCODE;
        $this->_needAssignment = ($flags & XAR_TPL_TAG_NEEDASSIGNMENT) == XAR_TPL_TAG_NEEDASSIGNMENT;
        $this->_needParameter  = ($flags & XAR_TPL_TAG_NEEDPARAMETER)  == XAR_TPL_TAG_NEEDPARAMETER;
    }

    // Getters for the attributes, useless now since we're serializing this object
    // and the private parts would need sleep/wakeup handler for that to work
    // so, @todo here.
    public function hasChildren()
    {  return $this->_hasChildren;    }
    public function hasText()
    {  return $this->_hasText;    }
    public function isAssignable()
    {  return $this->_isAssignable;    }
    public function isPHPCode()
    {  return $this->_isPHPCode;    }
    public function needAssignement()
    {  return $this->_needAssignment;    }
    public function needParameter()
    {  return $this->_needParameter;    }
    public function getAttributes()
    {  return $this->_attributes;    }
    public function getName()
    {  return $this->_name;    }
    public function getModule()
    {  return $this->_module;    }
    public function getHandler()
    {  return $this->_handler;    }

    /**
     * Call the handler defined for the registered tag
     *
     * @return string code produced by the handler
     * @throws BadParameterException
    **/
    public function callHandler($args, $handler_type='render')
    {
        // FIXME: get rid of this once installation includes the right serialized info
        if (empty($this->_type) || empty($this->_func)) {
            $handler = $this->_handler;
            $module = $this->_module;
            if (preg_match("/($module)_(\w+)api_(.*)/",$handler,$matches)) {
                $this->_type = $matches[2];
                $this->_func = $matches[3];
            } else {
                // FIXME: why is this needed?
                $this->_name = NULL;
                throw new BadParameterException($handler,'Illegal tag definition: "#(1)" is an invalid handler.');
            }
        }
        // Add the type to the args
        $args['handler_type'] = $handler_type;
        $code = xarModAPIFunc($this->_module, $this->_type, $this->_func, $args);
        assert('is_string($code); /* A custom tag should return a string with the code to put into the compiled template */');
        // Make sure the code has UNIX line endings too
        $code = str_replace(array("\r\n","\r"),"\n",$code);
        return $code;
    }

    /**
     * Registers a tag to the theme system
     *
     * @return bool
     * @throws DuplicateTagException, SQLException
     * @todo Make this more generic, now only 'childless' tags are supported (only one handler)
     * @todo Consider using handler-array (define 'events' like in SAX)
     * @todo wrap the registration into constructor, either it succeeds creating the object or not, not having an object without succeeding sql.
    **/
    public function register()
    {
        try {
            // 1. if getobject fails -> BLException (no tag, which is good, we catch and ignore this)
            // 2. if it succeeds (== no exception), we raise one ourselves which we do not catch, so it bubbles
            $this->getObject($this->getName());
            throw new DuplicateTagException($this->getName(),'<xar:#(1)> tag is already defined.');
        } catch (BLException $e) {
            // Good, not registered yet
        }

        $dbconn =& xarDBGetConn();
        $xartable =& xarDBGetTables();

        $systemPrefix = xarDBGetSystemTablePrefix();
        $tag_table = $systemPrefix . '_template_tags';

        // Get next ID in table
        try {
            
            $dbconn->begin();

            $modInfo = xarMod::GetBaseInfo($this->getModule());
            $query = "INSERT INTO $tag_table
                      (xar_name, xar_modid, xar_handler, xar_data)
                      VALUES(?,?,?,?)";
            $bindvars = array(
                $this->getName(),
                $modInfo['systemid'],
                $this->getHandler(),
                serialize($this)
            );

            $stmt = $dbconn->prepareStatement($query);
            $stmt->executeUpdate($bindvars);
            $dbconn->commit();
        } catch (SQLException $e) {
            $dbconn->rollback();
            throw $e;
        }
        return true;
    }

    /**
     * Unregisters a tag to the theme system
     *
     * @param  string $tag_name tag to remove
     * @return bool
    **/
    public static function unregister($tag_name)
    {
        if (!eregi(self::NAME_REGEX, $tag_name)) {
            // throw exception
            return false;
        }

        $dbconn =& xarDBGetConn();
        $xartable =& xarDBGetTables();

        $tag_table = $xartable['template_tags'];
        $query = "DELETE FROM $tag_table WHERE xar_name = ?";
        $stmt = $dbconn->prepareStatement($query);
        $stmt->executeUpdate(array($tag_name));
        return true;
    }

    /**
     * Unregisters all tags of a specific module to the theme system
     *
     * @param  string $module namde of tags to remove
     * @return bool
    **/
    public static function unregisterall($module)
    {
        if (!eregi(self::NAME_REGEX, $module)) {
            // throw exception
            return false;
        }

        $dbconn =& xarDBGetConn();
        $xartable =& xarDBGetTables();

        $tag_table = $xartable['template_tags'];
        $query = "DELETE FROM $tag_table WHERE xar_modid = ?";
        $stmt = $dbconn->prepareStatement($query);
        $info = xarMod::getInfo(xarModGetIDFromName($module));
        $stmt->executeUpdate(array($info['systemid']));
        return true;
    }

    public static function getObject($tag_name)
    {
        // cache tags for compile performance
        static $tag_objects = array();
        static $stmt = null;
        
        if (isset($tag_objects[$tag_name])) {
            return $tag_objects[$tag_name];
        }

        $dbconn =& xarDBGetConn();
        $xartable =& xarDBGetTables();

        $systemPrefix = xarDBGetSystemTablePrefix();
        $tag_table = $systemPrefix . '_template_tags';
        $mod_table = $systemPrefix . '_modules';
        $query = "SELECT tags.xar_data, mods.xar_name
                  FROM $tag_table tags, $mod_table mods
                  WHERE tags.xar_modid = mods.xar_id AND tags.xar_name=?";
        if(!isset($stmt)) $stmt = $dbconn->prepareStatement($query);
        
        $stmt->setLimit(1);
        $result = $stmt->executeQuery(array($tag_name),ResultSet::FETCHMODE_NUM);

        if ($result->first()) {
            list($obj,$module) = $result->getRow();
            $result->close();
        } else {
            $result->close();
            // Throw a generic BL exception for now
            $msg = '<xar:#(1)> tag is not defined.';
            throw new BLException($tag_name,$msg);
        }

        // Module must be active for the tag to be active
        if(!xarMod::isAvailable($module)) return; //throw back

        // WATCH OUT!: unserializing an object doesnt unserialize its private parts
        $obj = unserialize($obj);

        // Cache it
        $tag_objects[$tag_name] = $obj;
        return $obj;
    }

    /**
     * Check the attributes of a tag
     *
     * @param   string    $name  Name of the tag
     * @param   array     $attrs Attribute array
     * @return  bool
     * @throws  BLException, BLValidationException
    **/
    public function checkAttributes($attrs)
    {
        foreach ($this->getAttributes() as $attr) {
            $attr_name = $attr->getName();
            if (isset($attrs[$attr_name])) {
                // check that type matches
                $attr_types = $attr->getAllowedTypes();

                if ($attr_types & XAR_TPL_STRING) {
                    continue;
                } elseif (($attr_types & XAR_TPL_BOOLEAN)
                          && eregi ('^(true|false|1|0)$', $attrs[$attr_name])) {
                    continue;
                } elseif (($attr_types & XAR_TPL_INTEGER)
                          && eregi('^\-?[0-9]+$', $attrs[$attr_name])) {
                    continue;
                } elseif (($attr_types & XAR_TPL_FLOAT)
                          && eregi('^\-?[0-9]*.[0-9]+$', $attrs[$attr_name])) {
                    continue;
                }

                // bad type for attribute
                throw new BLValidationException(array($attr_name,$name),'"#(1)" attribute in <xar:#(2)> tag does not have correct type. See tag documentation.');
            } elseif ($attr->isRequired()) {
                // required attribute is missing!
                throw new BLValidationException(array($attr_name,$name),'Required "#(1)" attribute is missing from <xar:#(2)> tag. See tag documentation.');
            }
        }
        return true;
    }
}

/**
 * Wrappers related to xarTemplate class for 1.x API compatibility
 *
**/
function xarTplRegisterTag($tag_module, $tag_name, $tag_attrs = array(), $tag_handler = NULL, $flags = XAR_TPL_TAG_ISPHPCODE)
{   $tag = new xarTemplateTag($tag_module, $tag_name, $tag_attrs, $tag_handler, $flags);
    return $tag->register();
}
function xarTplUnregisterTag($tag_name)
{    return xarTemplateTag::unregister($tag_name);
}
function xarTplCheckTagAttributes($name, $attrs)
{   $tag_ref = xarTemplateTag::getObject($name);
    return $tag_ref->checkAttributes($attrs);
}
function xarTplGetTagObjectFromName($tag_name)
{    return xarTemplateTag::getObject($tag_name);
}

/**
 * Model of a tag attribute
 *
 * Mainly uses for custom tags
 *
 * @package blocklayout
 * @access public
 * @throws BadParameterException
 * @todo see FIXME
**/
class xarTemplateAttribute extends Object
{
    const  NAME_REGEX = '^[a-z][-_a-z0-9]*$';

    public $_name;     // Attribute name
    public $_flags;    // Attribute flags (datatype, required/optional, etc.)

    function __construct($name, $flags = NULL)
    {
        if (!eregi(self::NAME_REGEX, $name)) {
            // This should be a XML validation exception perhaps?
            throw new BadParameterException($name,'The attribute name "#(1)" is invalid. Attribute names contain letters, numbers, _ and -, and must start with a letter.');
        }

        if (!is_integer($flags) && $flags != NULL) {
            throw new BadParameterException($flags,"Illegal attribute flags ('#(1)'): flags must be of integer type.");
        }

        $this->_name  = $name;
        $this->_flags = $flags;

        // FIXME: <marco> Why do you need both XAR_TPL_REQUIRED and XAR_TPL_OPTIONAL when XAR_TPL_REQUIRED = ~XAR_TPL_OPTIONAL?
        if ($this->_flags == NULL) {
            $this->_flags = XAR_TPL_ANY|XAR_TPL_REQUIRED;
        } elseif ($this->_flags == XAR_TPL_OPTIONAL) {
            $this->_flags = XAR_TPL_ANY|XAR_TPL_OPTIONAL;
        }
    }

    function getFlags()
    {  return $this->_flags; }

    function getAllowedTypes()
    {  return ($this->getFlags() & (~ XAR_TPL_OPTIONAL)); }

    function getName()
    {  return $this->_name; }

    function isRequired()
    {  return !$this->isOptional(); }

    function isOptional()
    {  return ($this->_flags & XAR_TPL_OPTIONAL); }
}
?>
