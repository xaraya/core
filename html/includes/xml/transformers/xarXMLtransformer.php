<?php

/**
 * File: $Id$
 *
 * Xml transformer base file
 *
 * @package xml
 * @subpackage transformers
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @author Marcel van der Boom <marcel@xaraya.com>
*/


/**
 * Base class for all xml transformers
 *
 * This class forms the base for defining handlers. Override
 * this class with your own methods with the same name to create
 * a xml handler object which handles the parsing for you. The default
 * behaviour of this handler is to do nothing.
 *
 */
class xarXmlTransformer
{
    var $output;    // The XML output the transformer produces.
    
    // Abstract functions
    function default_handler(){}
    function character_data(){}
    function open_tag(){}
    function close_tag(){}
    function process_instruction(){}
    function unparsed_entity(){}
    function notation_declaraion(){}
    function external_entity_reference(){}
    function start_namespace(){}
    function end_namespace(){}
}

include "includes/xml/transformers/xmlcopy.php";
include "includes/xml/transformers/entityresolve.php";


?>
