<?php
/**
 * BlockLayout Template Engine Compiler
 *
 * @package blocklayout
 * @copyright (C) 2003,2004 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Marco Canini <marco@xaraya.com>
 * @author Paul Rosania  <paul@xaraya.com>
 * @author Marcel van der Boom <marcel@xaraya.com>
 * @author Marty Vance <dracos@xaraya.com>
 * @author Garrett Hunter <garrett@blacktower.com>
 */

/**
 * Defines for token handling
 *
 */
// Tags
define('XAR_TOKEN_TAG_START'         , '<'      ); // Opening a tag
define('XAR_TOKEN_TAG_END'           , '>'      ); // Closing a tag
define('XAR_TOKEN_ENDTAG_START'      , '/'      ); // Start of an end tag

// Entities
define('XAR_TOKEN_ENTITY_START'      , '&'      ); // Start of an entity
define('XAR_TOKEN_ENTITY_END'        , ';'      ); // End of an entity

// Tag tokens
define('XAR_TOKEN_NONMARKUP_START'   , '!'      ); // Start of non markup inside tag
define('XAR_TOKEN_PI_DELIM'          , '?'      ); // Processing instruction delimiter inside tag
define('XAR_TOKEN_NS_DELIM'          , ':'      ); // Namespace delimiter
define('XAR_TOKEN_HTMLCOMMENT_DELIM' , '--'     ); // HTML comment

define('XAR_TOKEN_CDATA_START'       , '[CDATA['); // CDATA start inside non markup section
define('XAR_TOKEN_CDATA_END'         , ']]'     ); // CDATA end marker

// Other
define('XAR_TOKEN_VAR_START'         , '$'    );          // Start of a variable
define('XAR_TOKEN_CI_DELIM'          , '#'    );          // Delimiter for variables, functions and other the CI stands for Code Item
define('XAR_NAMESPACE_PREFIX'        , 'xar'  );          // Our own default namespace prefix
define('XAR_FUNCTION_PREFIX'         , 'xar'  );          // Function prefix (used in check for allowed functions)
define('XAR_ROOTTAG_NAME'            , 'blocklayout');    // Default name of the root tag
define('XAR_NODES_LOCATION'          , 'includes/blnodes/'); // Where do we keep our nodes classes

/**
 * Defines for errors
 *
 */
define('XAR_BL_INVALID_TAG','INVALID_TAG');
define('XAR_BL_INVALID_ATTRIBUTE','INVALID_ATTRIBUTE');
define('XAR_BL_INVALID_SYNTAX','INVALID_SYNTAX');
define('XAR_BL_INVALID_ENTITY','INVALID_ENTITY');
define('XAR_BL_INVALID_FILE','INVALID_FILE');
define('XAR_BL_INVALID_INSTRUCTION','INVALID_INSTRUCTION');

define('XAR_BL_MISSING_ATTRIBUTE','MISSING_ATTRIBUTE');
define('XAR_BL_MISSING_PARAMETER','MISSING_PARAMETER');

define('XAR_BL_DEPRECATED_ATTRIBUTE','DEPRECATED_ATTRIBUTE');

/**
 * xarTpl__CompilerError
 *
 * For now just a stub class to a system exception
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__CompilerError extends SystemException
{
    function raiseError($msg)
    {
        // FIXME: is this usefull at all, if the compiler doesn't work, how are we going to show the exception ?
        xarErrorSet(XAR_SYSTEM_EXCEPTION,'COMPILER_ERROR',$msg);
    }
}

/**
 * xarTpl__ParserError
 *
 * class to hold parser errors
 *
 * @package blocklayout
 * @access private
 * @todo evaluate whether the exception needs to be a system exception
 * @todo ML for the error message?
 */
class xarTpl__ParserError extends SystemException
{
    function raiseError($type, $msg, $posInfo)
    {
        $msg = 'Template error in file '.$posInfo->fileName.
            ' at line '.$posInfo->line.
            ', column '.$posInfo->column.
            ":\n\n".$msg;
        $msg .= "\n\n" . $posInfo->lineText . "\n";
        $msg .= str_repeat('-', max(0,$posInfo->column - 1));
        $msg .= '^';
        // FIXME: evaluate whether this needs to be a system exception.
        xarErrorSet(XAR_SYSTEM_EXCEPTION,$type,$msg);
    }
}

/**
 * xarTpl__Compiler - the abstraction of the BL compiler
 *
 * The compiler holds the parser and the code generator as objects
 *
 * @package blocklayout
 * @access private
 * @todo should this be a singleton?
 */
class xarTpl__Compiler extends xarTpl__CompilerError
{
    var $parser;
    var $codeGenerator;

    function xarTpl__Compiler()
    {
        $this->parser =& new xarTpl__Parser();
        $this->codeGenerator =& new xarTpl__CodeGenerator();
    }

    function compileFile($fileName)
    {
        // The @ makes the code better to handle, leave it.
        if (!($fp = @fopen($fileName, 'r'))) {
            $this->raiseError("Cannot open template file '$fileName'.");
            return;
        }
        
        if ($fsize = filesize($fileName)) {
            $templateSource = fread($fp, $fsize);
        } else {
            $templateSource = '';
            while (!feof($fp)) {
                $templateSource .= fread($fp, 4096);
            }
        }
        
        fclose($fp);

        $this->parser->setFileName($fileName);
        return $this->compile($templateSource);
    }

    function compile(&$templateSource)
    {
        $documentTree = $this->parser->parse($templateSource);
        if (!isset($documentTree)) return; // throw back
        return $this->codeGenerator->generate($documentTree);
    }
}

/**
* xarTpl_PositionInfo
 *
 * Instance of this class record where we are doing what in the templates
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__PositionInfo extends xarTpl__ParserError
{
    var $fileName = '';
    var $line = 1;
    var $column = 1;
    var $lineText = '';
    
    function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }
}

/**
 * xarTpl__CodeGenerator
 *
 * part of the compiler, this generates the code for each tag found
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__CodeGenerator extends xarTpl__PositionInfo
{
    var $isPHPBlock = false;
    var $pendingExceptionsControl = false;
    var $code;

    function isPHPBlock()
    {
        return $this->isPHPBlock;
    }

    function setPHPBlock($isPHPBlock)
    {
        $code = '';
        // Only change when needed
        if($this->isPHPBlock != $isPHPBlock) {
            $this->isPHPBlock = $isPHPBlock;
            $code = ($isPHPBlock)? '<?php ' : '?>';
        }
        return $code;
    }

    function isPendingExceptionsControl()
    {
        return $this->pendingExceptionsControl;
    }

    function setPendingExceptionsControl($pendingExceptionsControl)
    {
        $this->pendingExceptionsControl = $pendingExceptionsControl;
    }

    function generate(&$documentTree)
    {
        // Start the code generation
        $this->code = '';
        $this->code = $this->generateNode($documentTree);
        if (!isset($this->code)) return; // throw back

        // This seems a bit strange, but we always want to end with return 
        // true at then end, even if we're not in a php block
        $this->code .= $this->setPHPBlock(true);
        $this->code .= " return true;" . $this->setPHPBlock(false);
        return $this->code;
    }

    function generateNode(&$node)
    {
        // Generating the code for a node consists of 3 parts in a recursive loop:
        // 1. render the begin tag
        // 2. render the children
        // 3. render the end tag.
        // If there are no children, we call the render method on the tag.
        if ($node->hasChildren() && isset($node->children) /*|| $node->hasText()*/) {
            //
            // PART 1: Handle the beginning of the node itself, start a php section if needed.
            //
            $startcode = $node->renderBeginTag();
            if (!isset($startcode)) return; // throw back
            $code = $startcode;

            //
            // PART 2: Handle each child below it.
            //
            foreach ($node->children as $child) {
                if ($child->isPHPCode()) {
                    $code .= $this->setPHPBlock(true);
                } elseif (!$node->needAssignment()) {
                    $code .= $this->setPHPBlock(false);
                }
                if ($node->needAssignment() || $node->needParameter()) {
                    if (!$child->isAssignable() && $child->tagName != 'TextNode') {
                        $this->raiseError(XAR_BL_INVALID_TAG,"The '".$nodeode->tagName."' tag cannot have children of type '".$child->tagName."'.", $child);
                        return;
                    }

                    if ($node->needAssignment()) {
                        $code .= ' = ';
                    }
                } elseif ($child->isAssignable()) {
                    $code .= 'echo ';
                }

                // Recursively do the children
                $childCode = $this->generateNode($child);
                if (!isset($childCode)) return; // throw back
                $code .= $childCode;

                // This is in the outer level of the current node, see what kind of node we're dealing with
                // here and whether it needs exceptions control
                if ($child->isAssignable() && !($node->needParameter()) || $node->needAssignment()) {
                    $code .= "; ";
                    if ($child->needExceptionsControl() || $this->isPendingExceptionsControl()) {
                        $code .= "if (xarCurrentErrorType() != XAR_NO_EXCEPTION) return false; ";
                        $this->setPendingExceptionsControl(false);
                    }
                } else {
                    if ($child->needExceptionsControl()) {
                        $this->setPendingExceptionsControl(true);
                    }
                }
            }

            //
            // PART 3: Handle the end rendering of the node
            //
            if ($node->isPHPCode()) {
                $code .= $this->setPHPBlock(true);
            }
            $endCode = $node->renderEndTag();
            if (!isset($endCode)) return; // throw back

            $code .= $endCode;

            // Other part: exception handling
            if (!$node->isAssignable() && ($node->needExceptionsControl())) {
                $code .= $this->setPHPBlock(true);
                $code .= "if (xarCurrentErrorType() != XAR_NO_EXCEPTION) return false; ";
                $this->setPendingExceptionsControl(false);
            }
        } else {
            // If there are no children or no text, we can render it as is.
            // Recursion end condition as well.
            $code = $node->render();
            if(!isset($code)) ;//xarLogVariable('offending node:', $node);
            // Either code must have a value, or an exception must be pending.
            assert('isset($code) || xarCurrentErrorType() != XAR_NO_EXCEPTION; /* The rendering code for a node is not working properly */');
            if (!isset($code))  return; // throw back
        }
        return $code;
    }
}

/**
 * xarTpl__Parser - the BL parser
 *
 * modelled as extension to the position info class,
 * parses a template source file and constructs a document tree
 *
 * @package blocklayout
 * @access private
 * @todo this is an xml parser type functionality, can't we use an xml parser for this?
 */
class xarTpl__Parser extends xarTpl__PositionInfo
{
    var $nodesFactory;
    var $tagNamesStack;
    var $tagIds;
    var $tagRootSeen;

    function xarTpl__Parser()
    {
        $this->nodesFactory =& new xarTpl__NodesFactory();
    }

    function parse(&$templateSource)
    {
        // <make sure we only have to deal with \n as CR tokens, replace \r\n and \r
        // Macintosh: \r, Unix: \n, Windows: \r\n
        $this->templateSource = str_replace(array('\r\n','\r'),'\n',$templateSource);

        // Initializing parse trace variables
        $this->line = 1; $this->column = 1; $this->pos = 0; $this->lineText = '';
        $this->tagNamesStack = array();  $this->tagIds = array(); $this->tagRootSeen=false;

        // Initializing the containers for template variables and the doctree
        $this->tplVars =& new xarTpl__TemplateVariables();
        $documentTree = $this->nodesFactory->createDocumentNode($this);

        // Parse the document tree
        $res = $this->parseNode($documentTree);
        if (!isset($res)) return; // throw back

        // Fill the tree with the parsed result and its variables and return
        $documentTree->children = $res;
        $documentTree->variables = $this->tplVars;

        return $documentTree;
    }

    /**
     * parseProcessingInstruction
     *
     * We've just identified a target for a processing instruction, handle it here.
     *
     * @access private
     * @todo deprecate the strange <?xar type PI over time, there are better ways for tpl vars
     */
    function parseProcessingInstruction($target)
    {
        $result = '';
        switch($target) {
            case 'xar': // <?xar processing instruction
                $variables = $this->parseHeaderTag();
                if (!isset($variables))  return; // throw back
                    
                foreach ($variables as $name => $value) $this->tplVars->set($name, $value);
                break;
            case 'xml': // <?xml header tag
                // Wind forward to first > and copy to output if we have seen the root tag, otherwise, just wind forward
                $between = $this->windTo(XAR_TOKEN_TAG_END);
                if(!isset($between)) return; // throw back
                    
                if(substr($between,-1) == XAR_TOKEN_PI_DELIM) { // ?
                    $output = XAR_TOKEN_TAG_START . XAR_TOKEN_PI_DELIM . $target . $between . $this->getNextToken();
                } else {
                    // Template error, found a > before the end
                    $this->raiseError(XAR_BL_INVALID_TAG,"The XML header ended prematurely, check the syntax", $this);
                    return;
                }    
                        
                // We do the exception check after parsing it, so we get usefull info in the error
                if($this->line != 1 && !$this->tagRootSeen) {
                    $this->raiseError(XAR_BL_INVALID_SYNTAX,'XML header can only be on the first line of the document',$this);
                    return;
                }
                        
                // Copy the header to the output
                if($this->tagRootSeen) {
                    if(ini_get('short_open_tag')) {
                        $result = "<?php echo $output; ?>";
                    }
                    $result .= "\n";
                }
                break;
            case 'php':
                // Do a specific error for php processing instruction
                $this->raiseError(XAR_BL_INVALID_TAG,"PHP code detected outside allowed syntax ", $this);
                return;
            default:
                // Anything else leads to an error, that includes the short form of the php tag (empty target)
                $this->raiseError(XAR_BL_INVALID_TAG,"Unknown processing instruction '<?$target' found",$this);
                return;
        }
        return $result;
    }
        
    function canBeChild(&$node)
    {
        if (!$node->hasChildren()) {
            $this->raiseError(XAR_BL_INVALID_TAG,"The '".$node->tagName."' tag cannot have children.", $node);
            return false;
        }
        return true;
    }
    
    function canHaveText(&$node)
    {
        if(!$node->hasText()) {
            $this->raiseError(XAR_BL_INVALID_TAG,"The '".$node->tagName."' tag cannot have text.", $node);
            return false;
        }
        return true;
    }
    
    function reverseXMLEntities($content)
    {
        return   str_replace(
                             array('&amp;', '&gt;', '&lt;', '&quot;'),
                             array('&', '>', '<', '"'),
                             $content);
    }
    
    
    /**
     * parseNode
     * 
     * top level parse function for a node
     *
     * @todo why only allow PI targets of size 3?
     */
    function parseNode(&$parent)
    {
        // Start of parsing a node, initialize our result variables
        $text = ''; $children = array();
        $token = $this->getNextToken();
        // Main parse loop
        while (isset($token)) {
            // At the start of parsing we can have:
            // <  ==> opening tag,  
            // &  ==> entity
            // #  ==> replacement (variable or function)
            switch ($token) {
                case XAR_TOKEN_TAG_START: // <
                    $nextToken = $this->getNextToken();
                    switch($nextToken) {
                        case XAR_TOKEN_PI_DELIM: // < ?
                            $res = $this->parseProcessingInstruction($this->getNextToken(3));
                            if(!isset($res)) return; //throw back
                            $token = $res;
                            break 2;
                        case 'x': // <x
                            if ($nextToken . $this->peek(3) == XAR_NAMESPACE_PREFIX . XAR_TOKEN_NS_DELIM) {
                                $xarToken = $this->getNextToken(3);
                                if(!isset($xarToken)) return;
                                // <xar: tag
                                if(!$this->canbeChild($parent)) return;
                                      
                                // Situation: [...text...]<xar:...
                                $trimmer='xmltrim'; 
                                // If we're in native php tags which always have xar children, trim it
                                $natives = array('set','ml','blockgroup');
                                if(in_array($parent->tagName, $natives,true)) $trimmer='trim';
                                if ($trimmer($text) != '') {
                                    if(!$this->canHaveText($parent)) return;
                                    $children[] =& $this->nodesFactory->createTextNode($trimmer($text), $this);
                                    $text = '';
                                }

                                // Handle Begin Tag
                                $res = $this->parseBeginTag();
                                if (!isset($res)) return; // throw back

                                list($tagName, $attributes, $closed) = $res;
                                // Check for uniqueness of id attribute
                                if (isset($attributes['id'])) {
                                    if (isset($this->tagIds[$attributes['id']])) {
                                        $this->raiseError(XAR_BL_INVALID_TAG,"Not unique id in '".$tagName."' tag.", $this);
                                        return;
                                    }
                                    if ($attributes['id'] == '') {
                                        $this->raiseError(XAR_BL_INVALID_TAG,"Empty id in '".$tagName."' tag.", $this);
                                        return;
                                    }
                                    $this->tagIds[$attributes['id']] = true;
                                }

                                $tplType = $this->tplVars->get('type');
                                if($tplType == 'module' && $tagName == XAR_ROOTTAG_NAME) {
                                    // root tag found in module template
                                    $this->raiseError(XAR_BL_INVALID_SYNTAX,
                                              'Root tag found in module template or before <?xar type="page" ?> instruction',$this);
                                    return;
                                }

                                if($tplType == 'page' && $tagName != XAR_ROOTTAG_NAME && !$this->tagRootSeen) {
                                    $this->raiseError(XAR_BL_INVALID_SYNTAX,"Found a  xar:$tagName tag before the xar:blocklayout tag, this is invalid",$this);
                                    return;
                                }

                                // Create the node we parsed.
                                $node = $this->nodesFactory->createTplTagNode($tagName, $attributes, $parent->tagName, $this);
                                if (!isset($node)) return; // throw back

                                if (!$closed) {
                                    array_push($this->tagNamesStack, $tagName);
                                    $res = $this->parseNode($node);
                                    if (!isset($res)) return; // throw back
                                    $node->children = $res;
                                }
                                $children[] = $node;
                                // Here we set token to an empty string so that $text .= $token will result in $text
                                $token = '';
                                break 2;
                            }
                            break;
                        case XAR_TOKEN_ENDTAG_START: 
                            // Check for xar end tag
                            if ($this->peek(4) == XAR_NAMESPACE_PREFIX . XAR_TOKEN_NS_DELIM) {
                                $xarToken = $this->getNextToken(4);
                                if(!isset($xarToken)) return;
                                // Situation: [...text...]</xar:...
                                $trimmer='xmltrim';
                                $natives = array('set', 'ml', 'mlvar','blockgroup');
                                if(in_array($parent->tagName, $natives,true)) $trimmer='trim';
                                if ($trimmer($text) != '') {
                                    if(!$this->canHaveText($parent)) return;
                                    $children[] =& $this->nodesFactory->createTextNode($trimmer($text), $this);
                                    $text = '';
                                }
                                // Handle End Tag
                                $tagName = $this->parseEndTag();
                                if (!isset($tagName)) return; // throw back

                                $stackTagName = array_pop($this->tagNamesStack);
                                if ($tagName != $stackTagName) {
                                    $this->raiseError(XAR_BL_INVALID_TAG,"Found closed '$tagName' tag where closed '$stackTagName' was expected.", $this);
                                    return;
                                }
                                return $children;
                            }
                            break;
                        case XAR_TOKEN_NONMARKUP_START:
                            $token .= $nextToken; // <!
                            $buildup=''; unset($identifier);unset($remember);
                            // Get all tokens till the first whitespace char, and check whether we found any tokens
                            $nextChar = $this->getNextToken();
                            while(trim($nextChar)) {
                                $buildup .= $nextChar;
                                switch($buildup) {
                                    case XAR_TOKEN_HTMLCOMMENT_DELIM:
                                        $identifier = XAR_TOKEN_HTMLCOMMENT_DELIM;
                                        break 2; // done
                                    case XAR_TOKEN_CDATA_START:
                                        // Treat it as text
                                        // FIXME: CDATA should really be skipped, but our RSS theme depends on the resolving inside
                                        $token = XAR_TOKEN_TAG_START . XAR_TOKEN_NONMARKUP_START .  $buildup;
                                        break 3;
                                }
                                $nextChar = $this->getNextToken();
                            }
                            if(!isset($identifier)) {
                                // Remember what was after the buildup
                                $remember = $nextChar;
                            }
                            // identifier is now a token or free form (in our case  -- or the first whitespace char)

                            // Get the rest of the non markup tag, recording along the way
                            $matchToken=''; $match = '';
                            $nextChar = $this->getNextToken();
                            if(isset($identifier)) {
                                while(isset($nextChar) && $matchToken . $nextChar != $identifier . XAR_TOKEN_TAG_END){
                                    $match .= $nextChar;
                                    // Match on the length of the identifier
                                    $nextChar = $this->getNextToken();
                                    $matchToken = substr($match,-1 * strlen($identifier));
                                }
                            }
                            // Forward to the end token
                            while(isset($nextChar) && $nextChar != XAR_TOKEN_TAG_END) {
                                $match .= $nextChar;
                                $nextChar = $this->getNextToken();
                            }

                            if(isset($identifier)) {
                                $tagrest = substr($match,0,-1 * strlen($identifier));
                            } else {
                                $tagrest = $match;
                                $matchToken = $remember;
                                $identifier = $remember;
                            }
                    
                            // Was it properly ended?
                            if($matchToken == $identifier && $nextChar == XAR_TOKEN_TAG_END) {
                                // the tag was properly ended.
                                $invalid = strpos($tagrest,$matchToken);
                                switch($identifier) {
                                    case XAR_TOKEN_HTMLCOMMENT_DELIM:
                                        // <!-- HTML comment, copy to output
                                        $token .= $identifier . $tagrest . $matchToken . $nextChar;
                                        break;
                                    default:
                                        // <!WHATEVER Something else ( <!DOCTYPE for example ) as long as it ends properly, we're happy
                                        $invalid = false;
                                        // Take the $tagrest and resolve stuff #...#
                                        $token .= $buildup . $identifier . $tagrest . $nextChar;
                                }
                                if($invalid) {
                                    $this->raiseError(XAR_BL_INVALID_TAG,
                                              "A non-markup tag (probably a comment) contains its identifier (".
                                              $matchToken.") in its contents. This is invalid XML syntax",$this);
                                    return;
                                }
                            } else {
                                xarLogMessage("[$token][$buildup][$identifier][$tagrest][$matchToken][$nextChar]");
                                $this->raiseError(XAR_BL_INVALID_TAG,
                                          "A non-markup tag (probably a comment) wasn't properly matched ('".
                                          $identifier."' vs. '". $matchToken ."') This is invalid XML syntax",$this);
                                return;
                            }
                            break 2;
                    } // end case

                    //<Dracos>  Stop tag embedding, ie <a href="<xar
                    // FIXME: does this still go bonkers on embedded javascript?
                    $between = $this->windTo(XAR_TOKEN_TAG_END);
                    if(!isset($between)) return;
                    if(strpos($between, XAR_TOKEN_TAG_START)) {
                        // There is a < in there
                        $this->raiseError(XAR_BL_INVALID_TAG,__LINE__ .": Found open tag before close tag.", $this);
                        return;
                    }
                    $this->stepBack(strlen($between)+1);
                    break;
                case XAR_TOKEN_ENTITY_START:
                    // Check for xar entity
                    if ($this->peek(4) == 'xar-') {
                        $nextToken = $this->getNextToken(4);
                        if(!isset($nextToken)) return;
                        if(!$this->canbeChild($parent)) return;

                        // Situation: [...text...]&xar-...
                        if (trim($text) != '') {
                            if(!$this->canHaveText($parent)) return;
                            $children[] = $this->nodesFactory->createTextNode(xmltrim($text), $this);
                            $text = '';
                        }
                        // Handle Entity
                        $res = $this->parseEntity();
                        if (!isset($res)) return; // throw back

                        list($entityType, $parameters) = $res;
                        $node = $this->nodesFactory->createTplEntityNode($entityType, $parameters, $this);
                        if (!isset($node)) return; // throw back

                        $children[] = $node;
                        $token = '';
                        break;
                    }
                    break;
                case XAR_TOKEN_CI_DELIM:
                    $nextToken = $this->getNextToken();

                    // Break out of processing if # is escaped as ##
                    if ($nextToken == XAR_TOKEN_CI_DELIM) break;
                
                    // Break out of processing if nextToken is (, because #(.) is used by MLS
                    if ($nextToken == '(') {
                        $token .= '(';
                        break;
                    }
                    $this->stepBack();
                
                    // Get what what is between #.....#
                    if ($nextToken == XAR_TOKEN_VAR_START || $nextToken == 'x') { // for href="#" for example
                        $between = $this->windTo(XAR_TOKEN_CI_DELIM);
                        if(!isset($between)) {
                            // set an exception and return
                            $this->raiseError(XAR_BL_INVALID_FILE,"Unexpected end of the file.", $this);
                            return; // throw back
                        }
                        $this->getNextToken(); // eat the matching #
                        $instruction = $between;
                    
                        if(!$this->canbeChild($parent)) return;

                        // Add text to parent, if applicable
                        // Situation: [...text...]#$....# or [...text...]#xarFunction()#
                        $trimmer='noop'; 
                        // FIXME: The above is wrong, should be xmltrim, 
                        // but otherwise the export of DD objects will look really ugly 
                        $natives = array('set','ml','mlvar');
                        if(in_array($parent->tagName,$natives,true)) $trimmer='trim';
                        if ($trimmer($text) != '') {
                            if(!$this->canHaveText($parent) && trim($text) != '') return;
                            $children[] = $this->nodesFactory->createTextNode($trimmer($text), $this);
                            $text = '';
                        }

                        // Replace XML entities with their ASCII equivalents.
                        // An XML parser would do this for us automatically.
                        $instruction = $this->reverseXMLEntities($instruction);
                    
                        // The following is a bit of a sledge-hammer approach. See bug 1273.
                        // TODO: parse the PHP so the semi-colon can be tested in context.
                        if (strpos($instruction, ';')) {
                            $this->raiseError(XAR_BL_INVALID_TAG, "Possible injected PHP detected in: $instruction", $this);
                            return;
                        }
                    
                        // Instruction is now set to $varname or xarFunction(.....)
                        $node = $this->nodesFactory->createTplInstructionNode($instruction, $this);
                        if (!isset($node)) return; // throw back

                        $children[] = $node;
                        $token = '';
                    } 
                    break;
            } // end switch
            // Once we get here, nothing in the switch caught the token, we copy verbatim to output.
            $text .= $token;
            // and get a new one
            $token = $this->getNextToken(1,true);
        } // end while
        
        // Add the final text as a text node 
        $trimmer = 'xmltrim';
        if ($trimmer($text) != '') {
            if(!$this->canHaveText($parent)) return;
            $children[] = $this->nodesFactory->createTextNode($trimmer($text),$this);
        }
        // Check if there is something left at the stack
        $stackTagName = array_pop($this->tagNamesStack);
        if(!empty($stackTagName)) {
            $this->raiseError(XAR_BL_INVALID_SYNTAX,"Reached end of file while tag '$stackTagName' was open",$this);
            return;
        }
        return $children;
    }
    
    function parseHeaderTag()
    {
        $variables = array();
        while (true) {
            $variable = $this->parseTagAttribute();
            if (!isset($variable)) return; // throw back

            if (is_string($variable)) {
                $exitToken = $variable;
                break;
            }
            $variables[$variable[0]] = $variable[1];
        }
        if ($exitToken != XAR_TOKEN_PI_DELIM) {
            $this->raiseError(XAR_BL_INVALID_TAG,"Invalid '$exitToken' character in header tag.", $this);
            return;
        }
        // Must parse the entire tag, we want to find > character
        while (true) {
            $token = $this->getNextToken();
            if (!isset($token)) return;

            if ($token == XAR_TOKEN_TAG_START) {
                $this->raiseError(XAR_BL_INVALID_TAG,"Unclosed tag.", $this);
                return;
            }
            if ($token == XAR_TOKEN_TAG_END) {
                break;
            }
        }
        return $variables;
    }

    function parseBeginTag()
    {
        $tagName = '';
        while (true) {
            $token = $this->getNextToken();
            if (!isset($token)) return;
            
            if ($token == XAR_TOKEN_TAG_START) {
                $this->raiseError(XAR_BL_INVALID_TAG,"Unclosed tag.", $this);
                return;
            }
            if ($token == ' ' || $token == XAR_TOKEN_TAG_END || $token == XAR_TOKEN_ENDTAG_START) {
                break;
            }
            $tagName .= $token;
        }
        if ($tagName == '') {
            $this->raiseError(XAR_BL_INVALID_TAG,"Unnamed tag.", $this);
            return;
        }
        $attributes = array();
        if ($token == ' ') {
            while (true) {
                $attribute = $this->parseTagAttribute();
                if (!isset($attribute)) return; // throw back

                if (is_string($attribute)) {
                    $exitToken = $attribute;
                    break;
                }
                $attributes[$attribute[0]] = $attribute[1];
            }
        } else {
            $exitToken = $token;
        }
        if ($exitToken != XAR_TOKEN_TAG_END) {
            // Must parse the entire tag, we want to find > character
            while (true) {
                $token = $this->getNextToken();
                if (!isset($token)) return;

                if ($token == XAR_TOKEN_TAG_START) {
                    $this->raiseError(XAR_BL_INVALID_TAG,"Unclosed tag.", $this);
                    return;
                }
                if ($token == XAR_TOKEN_TAG_END) {
                    break;
                }
            }
        }
        return array($tagName, $attributes, ($exitToken == XAR_TOKEN_ENDTAG_START) ? true : false);
    }

    function parseTagAttribute()
    {
        // Tag attribute
        $name = '';
        while (true) {
            $token = $this->getNextToken();
            if (!isset($token)) return;
        
            if ($token == '"' || $token == "'") {
                $quote = $token;
                $this->raiseError(XAR_BL_INVALID_TAG,"Invalid '$token' character in attribute name.", $this);
                return;
            } elseif ($token == XAR_TOKEN_TAG_START) {
                $this->raiseError(XAR_BL_INVALID_TAG,"Unclosed tag.", $this);
                return;
            } elseif ($token == XAR_TOKEN_TAG_END || $token == XAR_TOKEN_ENDTAG_START || $token == XAR_TOKEN_PI_DELIM) {
                if (trim($name) != '') {
                    $this->raiseError(XAR_BL_INVALID_TAG,"Invalid '$name' attribute.", $this);
                    return;
                }
                return $token;
            } elseif ($token == '=') {
                break;
            }
            $name .= $token;
        }
        $name = trim($name);
        if ($name == '') {
            $this->raiseError(XAR_BL_INVALID_ATTRIBUTE,"Unnamed attribute.", $this);
            return;
        }
        $value = '';
        $quote = '';
        $ok = false;
        while (true) {
            $token = $this->getNextToken();
            if (!isset($token)) return;
        
            if($token == XAR_TOKEN_TAG_END) {
                $this->raiseError(XAR_BL_INVALID_ATTRIBUTE,"Unclosed '$name' attribute.", $this);
                return;
            } elseif ($token == $quote) {
                break;
            }
            if ($ok) {
                $value .= $token;
            } else {
                if ($token == '"') {
                    $quote = '"';
                    $ok = true;
                } elseif ($token == "'") {
                    $quote = "'";
                    $ok = true;
                }
            }
        }
        // Replace XML entities with their ASCII equivalents.
        // An XML parser would do this for us automatically.
        $value = $this->reverseXMLEntities($value);
        return array($name, $value);
    }

    function parseEndTag()
    {
        // Tag name
        $tagName = '';
        while (true) {
            $token = $this->getNextToken();
            if (!isset($token)) return;
        
            if($token == XAR_TOKEN_TAG_START) {
                $this->raiseError(XAR_BL_INVALID_TAG,"Unclosed tag.", $this);
                return;
            } elseif ($token == XAR_TOKEN_TAG_END) {
                break;
            }
            $tagName .= $token;
        }
        $tagName = rtrim($tagName);
        if ($tagName == '') {
            $this->raiseError(XAR_BL_INVALID_TAG,"Unnamed tag.", $this);
            return;
        }
        return $tagName;
    }

    function parseEntity()
    {
        // Entity type
        $entityType = '';
        while (true) {
            $token = $this->getNextToken();
            if (!isset($token)) return;
            
            if($token == '-' || $token == XAR_TOKEN_ENTITY_END) {
                break;
            }
            $entityType .= $token;
        }
        if ($entityType == '') {
            $this->raiseError(XAR_BL_INVALID_ENTITY,"Untyped entity.", $this);
            return;
        }
        $parameters = array();
        if ($token == '-') {
            $parameter = '';
            while (true) {
                $token = $this->getNextToken();
                if (!isset($token)) return;
 
                if($token == XAR_TOKEN_ENTITY_END) {
                    if ($parameter == '') {
                        $this->raiseError(XAR_BL_INVALID_ENTITY,"Empty parameter.", $this);
                        return;
                    }
                    $parameters[] = $parameter;
                    break;
                } elseif ($token == '-') {
                    $parameters[] = $parameter;
                    $parameter = '';
                } else {
                    $parameter .= $token;
                }
            }
        }
        return array($entityType, $parameters);
    }

    function getNextToken($len = 1,$dontExcept = false)
    {
        $result = '';
        while($len >= 1) {
            $token = substr($this->templateSource, $this->pos, 1);
            // FIXME: We compare to 0 because substr() with "mbstring.func_overload = 7" settings
            // returns not false but 0 at the end of a template
            if ($token === false || $token == null) {
                // This line fixes a bug that happen when $len is > 1
                // and the file ends before the token has been read
                $this->pos += $len;
                if(!$dontExcept) {
                    $this->raiseError(XAR_BL_INVALID_FILE,"Unexpected end of the file.", $this);
                }
                return;
            }
            $this->lineText .= $token;

            $this->pos++; $this->column++;
            if ($token == "\n") {
                $this->line++;
                $this->column = 1;
                $this->lineText = '';
            }
            $result .= $token;
            $len--;
        }
        return $result;
    }
    
    // move the pointer to the position of the needle
    // returns the content wound over if successfull
    // returns '' if not found, pointer not changed
    // returns null when $token is null
    // FIXME: this is a temporary quick implementation for bug #3111
    // FIXME: this does a literal search on the needle, no smart finding of end tags
    function windTo($needle)
    {
        assert('strlen($needle) > 0; /* The search needle in parser->windTo has zero length */');
        $wound = '';
        $buffer = $this->getNextToken(strlen($needle));
        if(!isset($buffer)) return; // throw back
        $wound = $buffer;
        while($buffer!= $needle) {
            $next = $this->getNextToken();
            if(!isset($next)) {
                $this->stepBack(strlen($wound));
                return; // throw back
            }
            $buffer = substr($buffer, 1) . $next;
            $wound.= $next;
        }
        // We found the needle and we are are at the end of it
        // place the pointer right before it
        $this->stepBack(strlen($needle));
        $wound = substr($wound,0,-1*strlen($needle));
        return $wound;
    }

    function stepBack($len = 1)
    {
        $this->pos -= $len;
        $this->column -= $len;
        $this->lineText = substr($this->lineText, 0, strlen($this->lineText) - $len);
    }

    function peek($len = 1, $start = 0)
    {
        assert('$start >= 0; /* The start position for peeking needs to be zero or greater, a call to parser->peek was wrong */');
        if($start == 0) $start = $this->pos; // can't do this in param init

        $token = substr($this->templateSource, $start, $len);
        if ($token === false) return;
        return $token;
    }
}

/**
 * xarTpl__NodesFactory - class which constructs nodes in the document tree
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__NodesFactory extends xarTpl__ParserError
{

    function createTplTagNode($tagName, $attributes, $parentTagName, &$parser)
    {
        // If the root tag comes along, check if we already have it
        if($tagName == XAR_ROOTTAG_NAME && $parser->tagRootSeen) {
            $this->raiseError(XAR_BL_INVALID_SYNTAX,"The root tag can only occur once.", $parser);
            return;
        }

        // Otherwise we instantiate the right class
        $tagClass ='xarTpl__Xar' .$tagName.'Node';
        $tagfile = XAR_NODES_LOCATION . 'tags/' .strtolower($tagName) .'.php';
        
        // FIXME: sync the implementation of core / custom tags, handle them the same way
        if(file_exists($tagfile)) {
            include_once($tagfile);
            $node =& new $tagClass($parser, $tagName);
        } else {
            include_once(XAR_NODES_LOCATION .'tags/other.php');
            $node =& new xarTpl__XarOtherNode($parser, $tagName);
            if(!isset($node->tagobject)) unset($node);
        }

        if (isset($node)) {
            $node->parentTagName = $parentTagName;
            // FIXME: do sanity check on the values here? (like spaces and crs)
            $node->attributes = $attributes;
            return $node;
        }

        // If we get here, the tag doesn't exist so we raise a user exception
        $this->raiseError(XAR_BL_INVALID_TAG,"Cannot instantiate nonexistent tag '$tagName'",$parser);
        return;
    }

    function createTplEntityNode($entityType, $parameters, &$parser)
    {
        $entityClass = 'xarTpl__Xar'.$entityType.'EntityNode';
        $entityFile = XAR_NODES_LOCATION . 'entities/' .strtolower($entityType) . '.php';
        if(!class_exists($entityClass)) {
            include_once($entityFile);
        }
        $node =& new $entityClass($parser,'EntityNode');

        if (isset($node)) {
            $node->entityType = $entityType;
            $node->parameters = $parameters;
            return $node;
        }
        $this->raiseError(XAR_BL_INVALID_ENTITY,"Cannot instantiate nonexistent entity '$entityType'.", $parser);
        return;
    }

    function createTplInstructionNode($instruction, &$parser)
    {
        if ($instruction[0] == XAR_TOKEN_VAR_START) {
            $node =& new xarTpl__XarVarInstructionNode($parser, 'InstructionNode');
        } else {
            $node =& new xarTpl__XarApiInstructionNode($parser, 'InstructionNode');
        }

        if (isset($node)) {
            $node->instruction = $instruction;
            return $node;
        }

        $this->raiseError(XAR_BL_INVALID_INSTRUCTION,"Cannot instantiate nonexistent or invalid instruction '". XAR_TOKEN_CI_DELIM .
                          "$instruction" . XAR_TOKEN_CI_DELIM . "'.", $parser);
        return;
    }

    function createTextNode($content, &$parser)
    {
        $node =& new xarTpl__TextNode($parser, 'TextNode');
        $node->content = $content;  
        return $node;
    }

    function createDocumentNode(&$parser)
    {
        $node =& new xarTpl__DocumentNode($parser,'DocumentNode');
        return $node;
    }
}

/**
 * xarTpl__TemplateVariables
 *
 * Handle template variables
 *
 * @package blocklayout
 * @access private
 * @todo code the version number somewhere more central
 * @todo is the encoding fixed?
 *
 */
class xarTpl__TemplateVariables
{
    var $tplVars = array();

    function xarTpl__TemplateVariables()
    {
        // Fill defaults
        $this->tplVars['version'] = '1.0';
        $this->tplVars['encoding'] = 'us-ascii';
        $this->tplVars['type'] = 'module';
    }

    function get($name)
    {
        if (isset($this->tplVars[$name])) {
            return $this->tplVars[$name];
        }
        return '';
    }

    function set($name, $value)
    {
        $this->tplVars[$name] = $value;
    }
}

/**
 * xarTpl__ExpressionTransformer
 *
 * Transforms BL and php expressions from templates.
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__ExpressionTransformer
{
    /*
     * Replace the array and object notation.
     * This is the BLExpression grammar:
     * BLExpression ::= Variable | Variable '.' ArrayKey | Variable ':' Property
     * Variable ::= [a-zA-Z_] ([0-9a-zA-Z_])*
     * ArrayKey ::= Name | Name '.' ArrayKey | Name ':' Property
     * Property ::= Name | Name '.' ArrayKey | Name ':' Property
     * Name     ::= ([0-9a-zA-Z_])+
     */
    function transformBLExpression($blExpression)
    {
        // 'resolve' the dot and colon notation
        $identifiers = preg_split('/[.|:]/',$blExpression);
        $operators = preg_split('/[^.|^:]/',$blExpression,-1,PREG_SPLIT_NO_EMPTY);
        
        $numIdentifiers = count($identifiers);
        
        $expression = $identifiers[0];
        for ($i = 1; $i < $numIdentifiers; $i++) {
            if($operators[$i - 1] == '.') {
                if((substr($identifiers[$i],0,1) == XAR_TOKEN_VAR_START) || is_numeric($identifiers[$i])) {
                    $expression .= "[".$identifiers[$i]."]";
                } else {
                    $expression .= "['".$identifiers[$i]."']";
                }
            } elseif($operators[$i - 1] == ':') {
                $expression .= '->'.$identifiers[$i];
            }
        }
        return XAR_TOKEN_VAR_START . $expression;
    }

    function transformPHPExpression($phpExpression)
    {
        // This regular expression  must match the variables in the BLExpression grammar above
        // pass it to the resolver, check for exceptions, and replace it with the resolved
        // var name.
        // Let's dissect the expression so it's a bit more clear:
        //  1. /..../i      => we're matching in a case - insensitive  way what's between the /-es (FIXME: KEEP AN EYE ON THIS) 
        //  2. \\\$         => matches \$ which is an escaped $ in the string to match
        //  3. (            => this starts a captured subpattern - results in $matches[1]
        //  4.  [a-z_]      => matches a letter or underscore
        //  5.  [0-9a-z_]*  => matches a number, letter of underscore, zero or more occurrences
        //  6.  (?:         => start property access non-captured subpattern
        //  7.   :|\\.      => matches the colon or the dot notation
        //  8.   [$]{0,1}   => the array key or object member may be a variable
        //  9.   [0-9a-z_]+ => matches number,letter or underscore, one or more occurrences 
        // 10.  )           => matches right brace
        // 11.  *           => match zero or more occurences of the property access / array key notation (colon notation)
        // 12. )            => ends the current pattern
        // TODO: of course, if all this was between #...# it would be a lot easier ;-)
        // TODO: $a[$b]:c doesn't work properly should be: $a[$b]->c Is: $a[$b]:c
        // TODO: if variable array key or object member, make sure it starts with a letter
        if (preg_match_all("/\\\$([a-z_][0-9a-z_]*(?:[:|\\.][$]{0,1}[0-9a-z_]+)*)/i", $phpExpression, $matches)) {
            // Resolve BL expressions inside the php Expressions
            $numMatches = count($matches[0]);
            
            // Make sure we replace in order of descending length of the matches
            // to prevent overlapping matches to disturb eachother.
            // NOTE: only needed for php versions < 4.3.0 otherwise use OFFSET flag for preg_match_all
            usort($matches[0], array('xarTpl__ExpressionTransformer', 'rlensort'));
            usort($matches[1], array('xarTpl__ExpressionTransformer', 'rlensort'));
            
            for ($i = 0; $i < $numMatches; $i++) {
                $resolvedName =& xarTpl__ExpressionTransformer::transformBLExpression($matches[1][$i]);
                if (!isset($resolvedName)) return; // throw back

                $phpExpression = str_replace($matches[0][$i], $resolvedName, $phpExpression);
            }
        }

        $findLogic      = array(' eq ', ' ne ', ' lt ', ' gt ', ' id ', ' nd ', ' le ', ' ge ');
        $replaceLogic   = array(' == ', ' != ',  ' < ',  ' > ', ' === ', ' !== ', ' <= ', ' >= ');

        $phpExpression = str_replace($findLogic, $replaceLogic, $phpExpression);

        return $phpExpression;
    }
    
    function rlensort($a, $b) 
    {
        if(strlen($a) == strlen($b)) {
            return 0;
        }
        return (strlen($a) < strlen($b)) ? 1 : -1;
    }
}

/**
 * xarTpl__Node
 *
 * Base class for all nodes, sets the base properties, methods are
 * abstract and should be overridden by each specific node class
 *
 * @package blocklayout
 * hasChildren -> false
 * hasText -> false
 * isAssignable -> true
 * isPHPCode -> false
 * needAssignment -> false
 * needParameter -> false
 * needExceptionsControl -> false
 */
class xarTpl__Node extends xarTpl__PositionInfo
{
    var $tagName;   // This is an internal name of the node, not the actual tag name
    
    // What we're doing here is create an alias for the constructor, so
    // it derives properly. That way we decouple the class name from the 
    // constructor. Oh, the beauty of PHP :-(
    // Like this we can call parent::constructor(...) in the subclasses independent
    // of the base class.
    function xarTpl__Node(&$parser, $nodeName)
    {
        // If constructor is defined in subclass, that one is called!!
        $this->constructor($parser, $nodeName);
    }
    
    function constructor(&$parser, $nodeName)
    {
        $this->tagName  = $nodeName;
        $this->fileName = $parser->fileName;
        $this->line     = $parser->line;
        $this->column   = $parser->column;
        $this->lineText = $parser->lineText;
    }
    
    function render()
    {
        die('xarTpl__Node::render: abstract');
    }

    function renderBeginTag()
    {
        die('xarTpl__Node::renderBeginTag: abstract');
    }

    function renderEndTag()
    {
        die('xarTpl__Node::renderEndTag: abstract');
    }

    function hasChildren()
    {
        return false;
    }

    function hasText()
    {
        return false;
    }

    function isAssignable()
    {
        return true;
    }

    function isPHPCode()
    {
        return false;
    }

    function needAssignment()
    {
        return false;
    }

    function needParameter()
    {
        return false;
    }

    function needExceptionsControl()
    {
        return false;
    }
}

/**
 * xarTpl__DocumentNode
 *
 *
 * @package blocklayout
 * hasChildren -> true
 * hasText -> true
 * isAssignable -> false
 * isPHPCode -> false
 * needAssignment -> false
 * needParameter -> false
 * needExceptionsControl -> false
 */
class xarTpl__DocumentNode extends xarTpl__Node
{
    var $children;
    var $variables;

    function renderBeginTag()
    {
        return '';
    }

    function renderEndTag()
    {
        return '';
    }

    function hasChildren()
    {
        return true;
    }

    function hasText()
    {
        return true;
    }

    function isAssignable()
    {
        return false;
    }
}

/**
 * xarTpl__TextNode
 * hasChildren -> false
 * hasText -> false
 * isAssignable -> true
 * isPHPCode -> false
 * needAssignment -> false
 * needParameter -> false
 * needExceptionsControl -> false
 * @package blocklayout
 */
class xarTpl__TextNode extends xarTpl__Node
{
    var $content;

    function render()
    {
        return $this->content;
    }

    function isAssignable()
    {
        return false;
    }
}

/**
 * xarTpl__EntityNode
 * hasChildren -> false
 * hasText -> false
 * isAssignable -> true
 * isPHPCode -> true
 * needAssignment -> false
 * needParameter -> false
 * needExceptionsControl -> false
 * @package blocklayout
 */
class xarTpl__EntityNode extends xarTpl__Node
{
    var $entityType;
    var $parameters;
    
    function isPHPCode()
    {
        return true;
    }
}

/**
 * xarTpl__InstructionNode
 * hasChildren -> false
 * hasText -> false
 * isAssignable -> true
 * isPHPCode -> true
 * needAssignment -> false
 * needParameter -> false
 * needExceptionsControl -> false
 * @package blocklayout
 */
class xarTpl__InstructionNode extends xarTpl__Node
{
    var $instruction;

    function isPHPCode()
    {
        return true;
    }
}

/**
 * xarTpl__XarVarInstructionNode
 *
 * models variables in the template, treats them as php expressions
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarVarInstructionNode extends xarTpl__InstructionNode
{
    function render()
    {
        if (strlen($this->instruction) <= 1) {
            $this->raiseError(XAR_BL_INVALID_INSTRUCTION,'Invalid variable reference instruction.', $this);
            return;
        }
        // FIXME: Can we pre-determine here whether a variable exist?
        $instruction = xarTpl__ExpressionTransformer::transformPHPExpression($this->instruction);
        if (!isset($instruction)) return; // throw back

        return $instruction;
    }
}

/**
 * xarTpl__XarApiInstructionNode
 *
 * API function node, treated as php expression
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarApiInstructionNode extends xarTpl__InstructionNode
{
    function render()
    {
        if (strlen($this->instruction) <= 1) {
            $this->raiseError(XAR_BL_INVALID_INSTRUCTION,'Invalid API reference instruction.', $this);
        }
        $instruction = xarTpl__ExpressionTransformer::transformPHPExpression($this->instruction);
        if (!isset($instruction)) return; // throw back
        
        $funcName = substr($instruction, 0, strpos($instruction, '('));
        if(!function_exists($funcName)) {
            $this->raiseError(XAR_BL_INVALID_INSTRUCTION,'Invalid API reference instruction or invalid function syntax.', $this);
            return;
        }
        return $instruction;
    }
}

/**
 * xarTpl__TplTagNode
 *
 * Base class for tag nodes
 *
 * hasChildren -> false
 * hasText -> false
 * isAssignable -> true
 * isPHPCode -> true
 * needAssignment -> false
 * needParameter -> false
 * needExceptionsControl -> false
 * @package blocklayout
 */
class xarTpl__TplTagNode extends xarTpl__Node
{
    var $attributes;
    var $parentTagName;
    var $children;
    
    function isPHPCode()
    {
        return true;
    }
}


/**
 * Compresses space for output generation
 *
 * A helper function which compresses space around an input string.
 * This function regards 'space' in the xml sense i.e.:
 * - multiple spaces are equivalent to one
 * - only 'outside space' is considered, not space 'inside' the input
 * - when multiple whitespace chars are found, the first is returned
 * - cr's are preserved
 *
 * As the 'whitespace' problem is really unsolvable (by me) isolate it
 * here. If someone finds a solution, here's where it should happen
 *
 * @access  protected
 * @param   string $input String for which to compress space
*/
function xmltrim($input='')
{    
    // Let's first determine if there is space at all.
    $hasleftspace = (strlen(ltrim($input)) != strlen($input));
    $hasrightspace = (strlen(rtrim($input)) != strlen($input));
    if($hasleftspace && $hasrightspace && trim($input) =='') {
        // There was more than one space, but only space, only return the first and 
        // the carriage returns
        $hasleftspace = true;
        $hasrightspace= false;
    }
    // Isolate the left and the right space
    $leftspace  = $hasleftspace  ? substr($input,0,1) : '';
    $rightspace = $hasrightspace ? substr($input,-1) : '';
    
    // Make sure we consider the right rest of the input string
    if($hasleftspace) $input = substr($input,1);
    if($hasrightspace) $input = substr($input,0,-1);
    
    // Make 'almost right' 
    $input = $leftspace . trim($input,' ') . $rightspace;
    // Finish it
    $input = str_replace(array(" \n","\n "),array("\n","\n"),$input);
    
    return $input;
}

/**
 * This doesn't do anything on purpose, please leave it in
 *
 */
function noop($input)
{
    return $input;
}

?>