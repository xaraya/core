<?php
/**
 * File: $Id$
 *
 * XML parser for Xaraya
 *
 * @package xml
 * @subpackage parser
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Marcel van der Boom <marcel@xaraya.com>
 */


/**
 * Xaraya Generic namespace aware XML parser
 *
 * The Xaraya XML parser. This parser is generic in nature
 * in that it isn't configured to handle a specific XML variety
 * Using the public methods modules can instantiate a parser
 * and set the handlers of it, so it can parse a certain XML
 * document structure and act accordingly.
 *
 * @access public
 * @package xml
 */
class xarXmlParser 
{
    var $encoding;      // Which input encoding are we gonna use for parsing?
    var $handler;       // Which handler object is attached to this parser?
    var $parser=NULL;   // The parser object itself
    var $parsed_result; // Parsed output
    
    /**
     * Construct the xarXmlParser object
     *
     * For xaraya we need to be able to set encoding, 
     * and have support for namespaces
     *
     * @access public
     * @param string $encoding character encoding to use (see top of file) 
     * @param object $handler which handler object handles the events generated
     * @todo build in recognition of domxml availability and set that as default handler
     */
    function xarXmlParser($encoding=XARXML_CHARSET_DEFAULT,$handler=NULL) 
    {
        $this->encoding=$encoding;
               
        $defHandlerClass = XARXML_DEFAULTHANDLER;
        if(is_object($handler) && is_subclass_of($handler,XARXML_HANDLERCLASS))
            $this->handler =& $handler;
        else
            $this->handler =& new $defHandlerClass();
    }
    
    /**
     * Parse a string
     *
     * @access public
     * @param string $xmldata string representation of xmldata to parse
     * @todo check the string more thoroughly, seems to be delicate
     */
    function parseString($xmldata) 
    {
        $this->__activate();
        if(!$this->__parse($xmldata, true)) {
            $this->__deactivate();
            return false;
        }
        return $this->__deactivate();
    }
    
    /**
     * Parse a file
     *
     * @access public
     * @param string $fileName path to file to parse
     */
    function parseFile($fileName) 
    {
        $fp = fopen($fileName,"r");
        if(!is_resource($fp)) {
            $this->lastmsg="Could't open $fileName";
            return false;
        }
        // If doc is empty return false
        if(filesize($fileName) == 0) {
            $this->lastmsg="File is empty";
            return false;
        }
        // Activate the parser with resolve base the base path of the file
        $resolve_base = dirname($fileName);
        $this->__activate($resolve_base);
        $xml='';

        // Parse in chunks
        while ($xmldata = fread($fp, XARXML_BLOCKREAD_SIZE)) {
            if(XARXML_PARSEWHILEREAD) {
                if(!$this->__parse($xmldata, feof($fp))) {
                    $this->__deactivate();
                    return false;
                }
            } else {
                $xml .= $xmldata;
            }
        }
        
        // Parse in whole
        if (!XARXML_PARSEWHILEREAD) {
            if (!$this->__parse($xml,true)) {
                $this->__deactivate();
                return false;
            }
        }
        return $this->__deactivate();
    }

    /**
     * Central parse function
     *
     * This is the only place where the actual parsing (by php i.e.) is don
     *
     * @access private
     * @param string $xmldata chunk of xmldata
     * @param bool   $final   denotes whether this is the last chunk we can expect
     * @todo put the $vals and $index arrays to use, we get them nearly for free here when parsing as a whole
     */
    function __parse($xmldata, $final) 
    {
        $vals=array(); $index=array();
        // FIXME: actually put that arrays to use in the handler,
        // tho we should do this in a portable way and in a 'SAX' way
        if(XARXML_PARSEWHILEREAD) {
            return xml_parse($this->parser,$xmldata, $final);
        } else {
            return xml_parse_into_struct($this->parser, $xmldata, &$vals, &$index);
        }
    }

    /**
     * Construct error information
     *
     * @access private
     *
     */
    function __getErrorInfo() 
    {
        $error = xml_get_error_code($this->parser);
        $this->lastmsg = "[".xml_get_current_line_number($this->parser).":"
            .xml_get_current_column_number($this->parser)."]-"
            .xml_error_string($error);
    }

        
    /**
     * Set a parser option
     * 
     * @access public
     * @param integer $option option to be set, one of the XML_OPTION_* constants
     * @param mixed   $value  value to set the option to
     */
    function setOption($option, $value) 
    {
        return xml_parser_set_option($this->parser, $option, $value);
    }
    
    /**
     * Get a parser option
     *
     * @access public
     * @param  integer $option option to retrieve, one of the XML_OPTION_* constants
     */
    function getOption($option)
    {
        return xml_parser_get_option($this->parser, $option);
    }

    
    /** 
     * Private methods
     *
     */

    /**
     * Activate the parser
     *
     * This method activates the parser to be set up for parsring a string
     * or a file. This activate/deactivate logic is necessary because the 
     * parser can only parse 1 file/string during it's instantation. When
     * you try to parser consecutive documents with the same instance all
     * kinds of weird errors are happening.
     *
     * @access private
     * @param string $resolve_base the base from which system/public ids are resolved
     *
     */
    function __activate($resolve_base = NULL) 
    {
        $this->parser=xml_parser_create_ns($this->encoding, XARXML_NAMESPACE_SEP);
        $this->setOption(XML_OPTION_CASE_FOLDING,false);
        $this->setOption(XML_OPTION_SKIP_WHITE,true);
        $this->__activateHandlers();
        $this->handler->_resolve_base = $resolve_base;
    }

    /**
     * Deactivate the parse
     *
     * When done parsing, this method deactivates the parser
     *
     * @access private
     */
    function __deactivate() 
    {
        $this->__geterrorinfo();
        $this->parsed_result = $this->handler->output;
        //$this->handler->_reset();
        return $this->__free();
    }

    /**
     * Free the parser
     *
     * @access private
     */
    function __free() 
    { 
        return xml_parser_free($this->parser); 
    }

    /**
     * Set the handlers
     *
     * For the registered handler to the parser, this private method 
     * activates them.
     *
     * @access private
     */
    function __activateHandlers()
    {
        $par = $this->parser;
        xml_set_object($par,&$this->handler);
        xml_set_default_handler($par,               'default_handler');
        xml_set_character_data_handler($par,        'character_data');
        xml_set_element_handler($par,               'open_tag',
                                                    'close_tag');
        xml_set_processing_instruction_handler($par,'process_instruction');
        xml_set_unparsed_entity_decl_handler($par,  'unparsed_entity');
        xml_set_notation_decl_handler($par,         'notation_declaration');
        xml_set_external_entity_ref_handler($par,   'external_entity_reference');
        xml_set_start_namespace_decl_handler($par,  'start_namespace');
        xml_set_end_namespace_decl_handler($par,    'end_namespace');
    }
}

?>
