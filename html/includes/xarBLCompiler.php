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
define('XAR_TOKEN_VAR_START'         , '$'    );       // Start of a variable
define('XAR_TOKEN_CI_DELIM'          , '#'    );       // Delimiter for variables, functions and other the CI stands for Code Item
define('XAR_NAMESPACE_PREFIX'        , 'xar'  );       // Our own default namespace prefix
define('XAR_FUNCTION_PREFIX'         , 'xar'  );       // Function prefix (used in check for allowed functions)
define('XAR_ROOTTAG_NAME'            , 'blocklayout'); // Default name of the root tag

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
        
        // FIXME: sync the implementation of core / custom tags, handle them
        // the same way
        if(class_exists($tagClass)) {
            $node =& new $tagClass($parser, $tagName);
        } else {
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
 * xarTpl__XarVarEntityNode
 *
 * Variable entities, treated as BL expression
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarVarEntityNode extends xarTpl__EntityNode
{
    function render()
    {
        if (count($this->parameters) != 1) {
            $this->raiseError(XAR_BL_MISSING_PARAMETER,'Parameters mismatch in &xar-var entity.', $this);
            return;
        }
        $name = xarTpl__ExpressionTransformer::transformBLExpression($this->parameters[0]);
        if (!isset($name)) return; // throw back

        return $name;
    }
}

/**
 * xarTpl__XarConfigEntityNode
 *
 * Configuration entities, treated as BL expression, basically
 * a wrapping to xarConfigGetVar()
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarConfigEntityNode extends xarTpl__EntityNode
{
    function render()
    {
        if (count($this->parameters) != 1) {
            $this->raiseError(XAR_BL_MISSING_PARAMETER,'Parameters mismatch in &xar-config entity.', $this);
            return;
        }
        $name = $this->parameters[0];
        return "xarConfigGetVar('".$name."')";
    }

    function needExceptionsControl()
    {
        return true;
    }
}

/**
 * xarTpl__XarModEntityNode
 *
 * Module variables entities, basically wraps xarModGetVar($module,$varname)
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarModEntityNode extends xarTpl__EntityNode
{
    function render()
    {
        if (count($this->parameters) != 2) {
            $this->raiseError(XAR_BL_MISSING_PARAMETER,'Parameters mismatch in &xar-mod entity.', $this);
            return;
        }
        $module = $this->parameters[0];
        $name = $this->parameters[1];
        return "xarModGetVar('".$module."', '".$name."')";
    }

    function needExceptionsControl()
    {
        return true;
    }
}

/**
 * xarTpl__XarSessionEntityNode
 *
 * Session variables entities, wrapps xarSessionGetVar()
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarSessionEntityNode extends xarTpl__EntityNode
{
    function render()
    {
        if (count($this->parameters) != 1) {
            $this->raiseError(XAR_BL_MISSING_PARAMETER,'Parameters mismatch in &xar-session entity.', $this);
            return;
        }
        $name = $this->parameters[0];
        return "xarSessionGetVar('".$name."')";
    }
}

/**
 * xarTpl__XarModUrlEntityNode
 *
 * Module url entities, wraps xarModUrl(module, type, func)
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarModurlEntityNode extends xarTpl__EntityNode
{
    function render()
    {
        if (count($this->parameters) != 3) {
            $this->raiseError(XAR_BL_MISSING_PARAMETER,'Parameters mismatch in &xar-modurl entity.', $this);
            return;
        }
        $module = $this->parameters[0];
        $type = $this->parameters[1];
        $func = $this->parameters[2];
        return "xarModURL('".$module."', '".$type."', '".$func."')";
    }
}

/**
 * xarTpl_XarUrlEntityNode
 *
 * More generic than ModUrlEntityNode, supports args
 * this wraps xarModURL('$module', '$type', '$func'$args)
 *
 * @package blocklayout
 * @access private
 * @todo model this class and the xarTpl__XarModUrlEntityNode as parent/derived pair.
 */
class xarTpl__XarUrlEntityNode extends xarTpl__EntityNode
{
    function render()
    {
        if (count($this->parameters) < 3) {
            $this->raiseError(XAR_BL_MISSING_PARAMETER,'Parameters mismatch in &xar-url entity.', $this);
            return;
        }
        $module = $this->parameters[0];
        if ($module == '') {
            $tplVars =& xarTpl__TemplateVariables::instance();
            $module = $tplVars->get('module');
            if (empty($module)) {
                $this->raiseError(XAR_BL_MISSING_PARAMETER,'Empty module parameter in &xar-url entity.', $this);
                return;
            }
        }
        $type = $this->parameters[1];
        if ($type == '') {
            $type = 'user';
        }
        $func = $this->parameters[2];
        if ($func == '') {
            $func = 'main';
        }
        $args = '';
        if (isset($this->parameters[3])) {
            $args = ', $'.$this->parameters[3];
        }
        return "xarModURL('$module', '$type', '$func'$args)";
    }
}

/**
 * xarTpl__XarBaseUrlEntityNode
 *
 * wraps xarServerGetBaseURL()
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarBaseurlEntityNode extends xarTpl__EntityNode
{
    function render()
    {
        return "xarServerGetBaseURL()";
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
 * xarTpl__XarVarNode: <xar:var> tag class
 *
 *
 * @package blocklayout
 */
class xarTpl__XarVarNode extends xarTpl__TplTagNode
{
    function render()
    {
        $scope = 'local';
        $prep = false;
        extract($this->attributes);

        if (!isset($name)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'name\' attribute in <xar:var> tag.', $this);
            return;
        }
        
        $prefix = ''; $postfix = '';
        if(strtolower($prep) == 'true') {
            $prep = true;
            $prefix = "xarVarPrepForDisplay(";
            $postfix = ")";
        }

        // Allow specifying name="test" and name="$test" and deprecate the $ form over time
        $name = str_replace(XAR_TOKEN_VAR_START,'',$name);

        switch ($scope) {
            case 'config':
                $value = "xarConfigGetVar('".$name."')";
                break;
            case 'session':
                $value = "xarSessionGetVar('".$name."')";
                break;
            case 'user':
                $value = "xarUserGetVar('".$name."')";
                break;
            case 'module':
                if (!isset($module)) {
                    $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'module\' attribute in <xar:var> tag.', $this);
                    return;
                }
                $value = "xarModGetVar('".$module."', '".$name."')";
                break;
            case 'theme':
                if (!isset($themeName)) {
                    $themeName = xarModGetVar('themes', 'default');
                }
                $value = "xarThemeGetVar('".$themeName."', '".$name."')";
                break;
            case 'local':
                // Resolve the name, note that this works for both name="test" and name="$test"
                $value = xarTpl__ExpressionTransformer::transformPHPExpression(XAR_TOKEN_VAR_START . $name);
                if (!isset($value)) return; // throw back
                break;
            default:
                $this->raiseError(XAR_BL_INVALID_ATTRIBUTE,'Invalid value for \'scope\' attribute in <xar:var> tag.', $this);
                return;
            }
            return $prefix . $value . $postfix;
    }

    function needExceptionsControl()
    {
        if (!isset($this->attributes['scope'])) {
            return false;
        }
        return ($this->attributes['scope'] == 'module' ||
                $this->attributes['scope'] == 'config' ||
                $this->attributes['scope'] == 'user');
    }
}

/**
 * xarTpl__XarLoopNode: <xar:loop> tag class
 *
 * @package blocklayout
 *
 * @todo why do we need both loop:number and loop:index? i think loop:number should refer to the loop number
 * 
 */
class xarTpl__XarLoopNode extends xarTpl__TplTagNode
{
    function loopCounter($operator = NULL)
    {
        static $loopCounter = 0;
        static $loopStack = array();
        if (isset($operator)) {
            if ($operator == '++') {
                $loopCounter++;
            } else {
                // $operator == --
                $loopCounter--;
            }
        }
        return $loopCounter;
    }

    function renderBeginTag()
    {
        extract($this->attributes);

        if (!isset($name)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'name\' attribute in <xar:loop> tag.', $this);
            return;
        }

        if (isset($prefix)) {
            $this->raiseError(XAR_BL_DEPRECATED_ATTRIBUTE,'Use of deprecated \'prefix\' attribute in <xar:loop> tag.',$this);
            return;
        }

        $name = xarTpl__ExpressionTransformer::transformPHPExpression($name);
        if (!isset($name)) return; // throw back

        // Increment the loopCounter and retrieve its new value
        // NOTE: class method!
        $loopCounter = xarTpl__XarLoopNode::loopCounter('++');
        
        $loopName ='$loop_'.$loopCounter;
        $idpart ='';
        if(isset($id)) {
            // Make the id property point to the same loop so loop:id:index etc. works too
            $idpart = $loopName.'->'.$id.'=&'.$loopName.'; $loop->'.$id.'=& '.$loopName.'->'.$id.';';
        }
        $output = $loopName.'->index=-1; '.$loopName.'->number='.$loopCounter.';
                  foreach ('.$name.' as '.$loopName.'->key => '.$loopName.'->item ) {
                       $loop->index = '.$loopName.'->index++;
                       $loop->key   = '.$loopName.'->key; 
                       $loop->item  =& '.$loopName.'->item; 
                       $loop->number= '.$loopName.'->number;
                       '. $idpart;
        return $output;
    }

    function renderEndTag()
    {
        // Decrement the loopCounter and retrieve its new value
        $previousLoop = xarTpl__XarLoopNode::loopCounter('--');
        $output = '} ';
        if($previousLoop >= 1 ) {
            $output .= '$loop = $loop_'.$previousLoop.';'; 
        } 
        return $output;
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
 * xarTpl__XarSecNode: <xar:sec> tag class
 *
 * @package blocklayout
 */
class xarTpl__XarSecNode extends xarTpl__TplTagNode
{
    function renderBeginTag()
    {
        $catch = 'true';  // Catch exceptions by default
        $component = '';  // Component is empty by default
        $instance = '';   // Instance is empty by default
        extract($this->attributes);

        if (!isset($mask)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'mask\' attribute in <xar:sec> tag.', $this);
            return;
        }

        if ($catch == 'true') {
            $catch = 1;
        } elseif ($catch == 'false') {
            $catch = 0;
        } else {
            $this->raiseError(XAR_BL_INVALID_ATTRIBUTE,'Invalid \'catch\' attribute in <xar:sec> tag.'.
                              ' \'catch\' must be boolean (true or false).', $this);
            return;
        }

        $component = xarTpl__ExpressionTransformer::transformPHPExpression($component);
        $instance = xarTpl__ExpressionTransformer::transformPHPExpression($instance);

        return "if (xarSecurityCheck(\"$mask\", $catch, \"$component\", \"$instance\")) { ";
    }

    function renderEndTag()
    {
        return "} ";
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

    function needExceptionsControl()
    {
        return true;
    }
}


/**
 * xarTpl__XarIfNode : <xar:if> tag class
 *
 * @package blocklayout
 */
class xarTpl__XarIfNode extends xarTpl__TplTagNode
{
    function renderBeginTag()
    {
        extract($this->attributes);

        if (!isset($condition)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'condition\' attribute in <xar:if> tag.', $this);
            return;
        }

        $condition = xarTpl__ExpressionTransformer::transformPHPExpression($condition);
        if (!isset($condition)) return; // throw back

        return "if ($condition) { ";
    }

    function renderEndTag()
    {
        return "} ";
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
 * xarTpl__XarElseIfNode: <xar:elseif> tag class
 *
 * Takes care of ean } elseif(condition) { construct
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarElseifNode extends xarTpl__TplTagNode
{
    function render()
    {
        extract($this->attributes);

        if (!isset($condition)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'condition\' attribute in <xar:elseif> tag.', $this);
            return;
        }

        $condition = xarTpl__ExpressionTransformer::transformPHPExpression($condition);
        if (!isset($condition)) return; // throw back

        return "} elseif ($condition) { ";
    }

    function isAssignable()
    {
        return false;
    }
}

/**
 * xarTpl__XarElseNode: <xar:else/> tag class
 *
 * Takes care of the "} else {" construct for both if and else tags
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarElseNode extends xarTpl__TplTagNode
{
    function render()
    {
        switch ($this->parentTagName) {
            case 'if':
            case 'sec':
                $output = "} else { ";
                break;
            default:
                $this->raiseError(XAR_BL_INVALID_TAG,"The <xar:else> tag cannot be placed under '".$this->parentTagName."' tag.", $this);
                return;
        }
        return $output;
    }

    function isAssignable()
    {
        return false;
    }

    function needParameter()
    {
        return false;
    }
}

/**
 * xarTpl__XarWhileNode: <xar:while> tag class
 *
 * takes care of the "while(condition) {" construct
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarWhileNode extends xarTpl__TplTagNode
{
    function renderBeginTag()
    {
        extract($this->attributes);

        if (!isset($condition)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'condition\' attribute in <xar:while> tag.', $this);
            return;
        }

        $condition = xarTpl__ExpressionTransformer::transformPHPExpression($condition);
        if (!isset($condition)) return; // throw back

        return "while ($condition) { ";
    }

    function renderEndTag()
    {
        return "} ";
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
 * xarTpl__XarForNode: <xar:for> tag class
 *
 * Takes care of the "for(start, test, iteration) {"  construct
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarForNode extends xarTpl__TplTagNode
{
    function renderBeginTag()
    {
        extract($this->attributes);

        if (!isset($start)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'start\' attribute in <xar:for> tag.', $this);
            return;
        }

        if (!isset($test)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'test\' attribute in <xar:for> tag.', $this);
            return;
        }

        if (!isset($iter)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'iter\' attribute in <xar:for> tag.', $this);
            return;
        }

        $start = xarTpl__ExpressionTransformer::transformPHPExpression($start);
        if (!isset($start)) return; // throw back

        $test = xarTpl__ExpressionTransformer::transformPHPExpression($test);
        if (!isset($test)) return; // throw back

        $iter = xarTpl__ExpressionTransformer::transformPHPExpression($iter);
        if (!isset($iter)) return; // throw back

        return "for ($start; $test; $iter) { ";
    }

    function renderEndTag()
    {
        return "} ";
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
 * xarTpl__XarForEachNode: <xar:foreach> tag class
 *
 * Takes care of the "foreach($array as $key=>$value) { " construct
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarForEachNode extends xarTpl__TplTagNode
{
    var $attr_value = null; // properties to hold the values of any values which might have the same name in
    var $attr_key = null;   // the scope of the foreach loop.
    var $keysavename = null;
    var $valsavename = null;

    function renderBeginTag()
    {
        extract($this->attributes);

        if (!isset($in)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'in\' attribute in <xar:foreach> tag.', $this);
            return;
        }

        if (!array($in)) {
            $this->raiseError(XAR_BL_INVALID_ATTRIBUTE,'Invalid \'in\' attribute in <xar:foreach> tag. \'in\' must be an array', $this);
            return;
        }

        $in = xarTpl__ExpressionTransformer::transformPHPExpression($in);
        // Create a save scope for the attributes using line and column as semi unique identifiers.
        // Note that this is only applicable on merged templates (as in: non existent in current code)
        // it's merely preparation for the one xar compile scenario
        // FIXME: keep an eye on the columns and line number, that they do *not* refer to the original template, but to
        //        the one representation one.
        if(isset($key))
            $this->keysavename = '$_bl_ks_' . substr($key,1) . '_' . $this->line . '_' . $this->column;
        if(isset($value))
            $this->valsavename = '$_bl_vs_' . substr($value,1) . '_' .$this->line .'_' . $this->column;

        if (isset($key) && isset($value)) {
            $this->attr_value = $value;
            $this->attr_key = $key;
            return "if(isset($value)) $this->valsavename = $value; if(isset($key)) $this->keysavename = $key; foreach ($in as $key => $value) { ";
        } elseif (isset($value)) {
            $this->attr_value = $value;
            return "if(isset($value)) $this->valsavename = $value; foreach ($in as $value) { ";
        } elseif (isset($key)) {
            $this->attr_key = $key;
            return "if(isset($key)) $this->keysavename = $key; foreach (array_keys($in) as $key) { ";
        } else {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'key\' or \'value\' attribute in <xar:foreach> tag.', $this);
            return;
        }
    }

    function renderEndTag()
    {
        if(isset($this->attr_value) && isset($this->attr_key))
            return "} if (isset($this->valsavename)) $this->attr_value = $this->valsavename; if (isset($this->keysavename)) $this->attr_key = $this->keysavename; ";
        if(isset($this->attr_value))
            return "} if (isset($this->valsavename)) $this->attr_value = $this->valsavename; ";
        if(isset($this->attr_key))
            return "} if (isset($this->keysavename)) $this->attr_key = $this->keysavename; ";

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
 * xarTpl__XarBlockNode: <xar:block> tag class
 *
 * Tag summary:
 *   Mandatory attributes: either 'instance' or ('module' and 'type')
 *   Optional attributes: 'title', 'template', 'name', 'state'
 *   Other attributes: all remaining, collected into an array
 *   Tag content: not supported for the present time
 * @package blocklayout
 * @access private
 * @todo try to get rid of the dependency with xarVar.php (xarVar_addslashes)
 */
class xarTpl__XarBlockNode extends xarTpl__TplTagNode
{
    var $blockgrouptemplate = NULL;
    
    
    function renderBeginTag()
    {
        extract($this->attributes);

        if (empty($instance) && (empty($module) || empty($type))) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE, 'Tag <xar:block> requires either an \'instance\' or both a \'module\' and \'type\' tag.', $this);
            return;
        }

        // Collect the remaining attributes together.
        $content = $this->attributes;

        // Remove the attributes that are handled outside the content.
        foreach(array('instance', 'module', 'type', 'name', 'title', 'template', 'state') as $std_attribute) {
            if (isset($content[$std_attribute])) {
                $$std_attribute = '"' . xarVar_addSlashes($content[$std_attribute]) . '"';
                unset ($content[$std_attribute]);
            } else {
                $$std_attribute = 'NULL';
            }
        }

        // PHP code for the block parameter override array.
        foreach($content as $attr_name => $attr_value) {
            $content[$attr_name] = '\'' . $attr_name . '\'=>"' . xarVar_addSlashes($attr_value) . '"';
        }
        $override = 'array(' . implode(', ', $content) . ')';

        // Code for rendering the block tag.
        // Use double-quotes so variables can be expanded within the attributes
        // for more dynamic blocks.
        $blockgrouptemplate = isset($this->blockgrouptemplate) ? $this->blockgrouptemplate : '';
        $code = <<<EOT
        xarBlock_renderBlock(
            array(
                    'instance' => $instance,
                    'module' => $module,
                    'type' => $type,
                    'name' => $name,
                    'title' => $title,
                    'template' => $template,
                    // Allow the box template to be set from a xar:blockgroup tag.
                    'box_template' => ('$blockgrouptemplate'),
                    'state' => $state,
                    'content' => $override
            )
        )
EOT;
        return $code;

        // TODO: what shall we do about the content?
        // Ideally we could have child tags to supply content not appropriate to attributes.
        if (isset($this->children) && count($this->children) > 0) {
            $contentNode = $this->children[0];
            if (isset($contentNode)) {
                $content = trim(addslashes($contentNode->render()));
            }
        }
    }

    function renderEndTag()
    {
        return '';
    }

    function render()
    {
        return $this->renderBeginTag();
    }

    function needExceptionsControl()
    {
        return true;
    }

    function hasText()
    {
        return true;
    }
}

/**
 * xarTpl__XarBlockGroupNode: <xar:blockgroup> tag class
 *
 * @package blocklayout
 * @access private
 * @todo the renderbegintag use of semicolons looks weird, why is that?
 */
class xarTpl__XarBlockGroupNode extends xarTpl__TplTagNode
{
    var $template = NULL;
    var $assignable = true;
    
    
    function renderBeginTag()
    {
        extract($this->attributes);

        if (isset($name)) {
            $this->raiseError(XAR_BL_INVALID_TAG,'Cannot have \'name\' attribute in open <xar:blockgroup> tag.', $this);
            return;
        }

        // Template attribute is optional.
        $code ='\'\';';
        // If a grouptemplate is set, notify the children
        // Note that we are just in time here to notify the children that a
        // blockgroup template is going to be used. 
        if (isset($template)) {            
            $children =& $this->children; 
            for($i=0;$i<count($children); $i++) {
                $children[$i]->blockgrouptemplate = $template;
            }
        }
        return $code;
    }

    function renderEndTag()
    {
        return '';
    }

    function render()
    {
        extract($this->attributes);

        if (!isset($name)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'name\' attribute in <xar:blockgroup> tag.', $this);
            return;
        }

        if (isset($template)) {
            return 'xarBlock_renderGroup("' . xarVar_addSlashes($name) . '", "' . xarVar_addSlashes($template) . '")';
        } else {
            return 'xarBlock_renderGroup("' . xarVar_addSlashes($name) . '")';
        }
    }

    function hasChildren()
    {
        return true;
    }

    function needExceptionsControl()
    {
        return true;
    }
    function isAssignable() 
    {
        // FIXME: this should be true on closed form and false on open form, but 
        // we only know that while generating the code into the template, so we
        // need to reorganize that a bit
        // Let's make this a variable for now
        return $this->assignable;
    }
}

/**
 * xarTpl__XarMlNode: <xar:ml> tag class
 *
 * @package blocklayout
 */
class xarTpl__XarMlNode extends xarTpl__TplTagNode
{
    function renderBeginTag()
    {
        if (isset($this->cachedOutput)) {
            return $this->cachedOutput;
        }

        if (count($this->children) == 0 ||
           ($this->children[0]->tagName != 'mlkey' &&
            $this->children[0]->tagName != 'mlstring')) {
            $this->raiseError(XAR_BL_INVALID_TAG,'Missing mlkey and mlstring tags in <xar:ml> tag.', $this);
            return;
        }
        $mlNode = $this->children[0];
        if (!isset($mlNode)) {
            $this->raiseError(XAR_BL_INVALID_TAG,'Missing \'mlkey\' and \'mlstring\' tags in <xar:ml> tag.', $this);
            return;
        }
        $params = '';
        foreach($this->children as $node) {
            if ($node->tagName == 'mlkey' ||
                $node->tagName == 'mlstring' ||
                $node->tagName == 'mlcomment') {
                continue;
            }
            if ($node->tagName != 'mlvar') {
                $this->raiseError(XAR_BL_INVALID_TAG,"The '".$this->tagName."' tag cannot have children of type '".$node->tagName."'.", $node);
                return;
            }
            $params .= $node->render();
        }
        $output = $mlNode->renderBeginTag() . $params . $mlNode->renderEndTag();

        $this->cachedOutput = $output;
        // Need to delete our children since this tag has specific knowledge about
        // its children and need to behave properly, so it renders in a custom way,
        // and caches the result.
        $this->children = array();

        return $output;
    }

    function renderEndTag()
    {
        return '';
    }

    function hasChildren()
    {
        return true;
    }
}

/**
 * xarTpl__XarMlKeyNode: <xar:mlkey> tag class
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarMlkeyNode extends xarTpl__TplTagNode
{
    function render()
    {
        return $this->renderBeginTag() . $this->renderEndTag();
    }

    function renderBeginTag()
    {
        $key = '';

        if (count($this->children) == 0) {
            $this->raiseError(XAR_BL_INVALID_TAG,'Missing the key inside <xar:mlkey> tag.', $this);
            return;
        }
        if (count($this->attributes) != 0) {
            $this->raiseError(XAR_BL_INVALID_TAG,'The <xar:mlkey> tag takes no attributes.', $this);
            return;
        }
        // Children of mlkey are only of text type (the text to be translated)
        // so this goes to TextNode render
        // MrB: isn't there always 1 child here?
        foreach($this->children as $child) {
            $key .= $child->render();
        }

        // FIXME: bug#45 makes this into a parse error if we don't
        //        add slashes here.
        // 1. can't be done in xarMLKey-> too late
        // 2. we can test for it above and raise an exception if we don't
        //    want to allow unescaped quotes in templates (unfriendly but right)
        //    (offer developer to use xarMLString instead)
        // 3. we can silently escape the key -> problem transferred to translators
        // FIXME: chose 3 for now, out of laziness.
        $key = trim(addslashes($key));
        if ($key == '') {
            $this->raiseError(XAR_BL_INVALID_TAG,'Missing content in <xar:mlkey> tag.', $this);
            return;
        }

        return "xarMLByKey(\"$key\"";
    }

    function renderEndTag()
    {
        return ")";
    }

    function hasText()
    {
        return true;
    }
}

/**
 * xarTpl__XarMlStringNode: <xar:mlstring> tag class
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarMlstringNode extends xarTpl__TplTagNode
{
    var $_rightspace;

    function render()
    {
        // return $this->renderBeginTag() . $this->renderEndTag();
        // Dracos: copying exception checking here...it isn't getting checked in renderBeginTag() for some reason
        // Dracos: this is not the right fix for bug 229, but it works for now
        if (count($this->attributes) != 0) {
            $this->raiseError(XAR_BL_INVALID_TAG,'The <xar:mlstring> tag takes no attributes.', $this);
            return;
        }
        $output = $this->renderBeginTag();
        if(!empty($output)){
            return $output . $this->renderEndTag();
        }
        else {
            $this->raiseError(XAR_BL_INVALID_TAG,'Missing the string inside <xar:mlstring> tag.', $this);
            return;
        }
    }

    function renderBeginTag()
    {
        $string = '';

        // Dracos:  these two ifs are never true????
        if (count($this->children) == 0) {
            $this->raiseError(XAR_BL_INVALID_TAG,'Missing the string inside <xar:mlstring> tag.', $this);
            return;
        }
        if (count($this->attributes) != 0) {
            $this->raiseError(XAR_BL_INVALID_TAG,'The <xar:mlstring> tag takes no attributes.', $this);
            return;
        }
        // Children are only of text type
        foreach($this->children as $node) {
            $string .= $node->render();
        }
        // Problem here is that we *do* want trimming for translation, but *not* for the displaying as
        // they may be very relevant. Only one space is relevant though.
        // TODO: this is an XML rule (whitespace collapsing), might not apply is we're going for other output formats
        // TODO: it's now getting a bit insane not using a XML parser, this is the kind of mess we need to deal with now
        $leftspace = (strlen(ltrim($string)) != strlen($string)) ? ' ' : '';
        $this->_rightspace =(strlen(rtrim($string)) != strlen($string)) ? ' ' : '';
        $totranslate = trim($string);
        if ($totranslate == '') {
            $this->raiseError(XAR_BL_INVALID_TAG,'Missing content in <xar:mlstring> tag.', $this);
            return;
        }
        
        return "'$leftspace' . " . 'xarML(\'' . str_replace("'","\'",$totranslate) . "'";
    }

    function renderEndTag()
    {
        return ") . '" . $this->_rightspace ."'";
    }

    function hasText()
    {
        return true;
    }
}

/**
 * xarTpl__XarMlVarNode: <xar:mlvar> tag class
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarMlvarNode extends xarTpl__TplTagNode
{
    function renderBeginTag()
    {
        return '';
    }

    function renderEndTag()
    {
        return '';
    }

    function render()
    {
        if (isset($this->cachedOutput)) {
            return $this->cachedOutput;
        }

        if (count($this->children) != 1) {
            $this->raiseError(XAR_BL_INVALID_TAG,'The <xar:mlvar> tag can contain only one child tag.', $this);
            return;
        }

        if (count($this->attributes) != 0) {
            $this->raiseError(XAR_BL_INVALID_TAG,'The <xar:mlvar> tag takes no attributes.', $this);
            return;
        }

        $codeGenerator =& new xarTpl__CodeGenerator();
        $codeGenerator->setPHPBlock(true);

        $output = ', ';
        $output .= $codeGenerator->generateNode($this->children[0]);
        $this->cachedOutput = $output;
        return $output;
    }

    function hasChildren()
    {
        return true;
    }

    function needParameter()
    {
        return true;
    }
}

/**
 * xarTpl__XarCommentNode: <xar:comment> tag class
 *
 * @package blocklayout
 * @access private
 * @todo let this class or derived ones also handle <!--
 */
class xarTpl__XarCommentNode extends xarTpl__TplTagNode
{
    function constructor(&$parser,$tagName)
    {
        parent::constructor($parser, $tagName);
        // Completely skip the contents of the tag
        // FIXME: This is a temporary solution for bug #3111
        $res = $parser->windTo(XAR_TOKEN_TAG_START . XAR_TOKEN_ENDTAG_START. XAR_NAMESPACE_PREFIX . XAR_TOKEN_NS_DELIM .'comment'. XAR_TOKEN_TAG_END);
    }
    function renderBeginTag()
    {
        // Clear the children array
        // FIXME: while ignoring it in the output, the content is still parsed which can result in
        // errors. A solution would be to wrap in cdata sections then, but then parsing should really not be done
        // meaning that our current RSS solution breaks
        $this->children = array();
        
        
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

    function isPHPCode()
    {
        return false;
    }

    function isAssignable()
    {
        return false;
    }
}

/**
 * xarTpl__XarModuleNode: <xar:module> tag class
 *
 * This is used in <xar:module main="true" /> as placeholder for the main module output,
 * or in <xar:module main="false" module="mymodule" type="mytype" func="myfunc" args="$args" />
 * or <xar:module main="false" module="mymodule" type="mytype" func="$somefunc" numitems="10" whatever="$this" ... />
 * to insert the result of another module function call in a template...
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarModuleNode extends xarTpl__TplTagNode
{
    function render()
    {
        extract($this->attributes);

        if (!isset($main)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'main\' attribute in <xar:module> tag.', $this);
            return;
        }

        if (empty($module)) {
            return '$_bl_mainModuleOutput';
        } else {
        // CHECKME: check attribute handling
            $args = $this->attributes;
            unset($args['main']);
            unset($args['module']);
            $module = xarTpl__ExpressionTransformer::transformPHPExpression($module);
            if (!empty($type)) {
                $type = xarTpl__ExpressionTransformer::transformPHPExpression($type);
                unset($args['type']);
            } else {
                $type = 'user';
            }
            if (!empty($func)) {
                $func = xarTpl__ExpressionTransformer::transformPHPExpression($func);
                unset($args['func']);
            } else {
                $func = 'main';
            }
        // TODO: improve handling of extra arguments if necessary
            if (isset($args['args']) && substr($args['args'],0,1) == XAR_TOKEN_VAR_START) {
                return 'xarModFunc("'.$module.'", "'.$type.'", "'.$func.'", '.$args['args'].')';
            } elseif (count($args) > 0) {
                $out = 'xarModFunc("'.$module.'", "'.$type.'", "'.$func.'", array(';
                foreach ($args as $key => $val) {
                    $out .= "'$key' => ";
                    if (substr($val,0,1) == XAR_TOKEN_VAR_START) {
                        $out .= $val . ', ';
                    } else {
                        $out .= "'$val', ";
                    }
                }
                $out = substr($out,0,-2) . '))';
                return $out;
            } else {
                return 'xarModFunc("'.$module.'", "'.$type.'", "'.$func.'")';
            }
        }
    }
}

/**
 * xarTpl__XarEventNode: <xar:event> tag class
 *
 * @package blocklayout
 * @access private
 * @todo Events are triggered by core only, how does this tag fit in?
 */
class xarTpl__XarEventNode extends xarTpl__TplTagNode
{
    function render()
    {
        extract($this->attributes);

        if (!isset($name)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'name\' attribute in <xar:event> tag.', $this);
            return;
        }

        return "xarEvt_trigger('$name')";
    }

    function isAssignable()
    {
        return false;
    }
}


/**
 * xarTpl__XarTemplateNode: <xar:template> tag class
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarTemplateNode extends xarTpl__TplTagNode
{
    function render()
    {
        $subdata = '$_bl_data';  // Subdata defaults to the data of the current template
        $type = 'module';        // Default type is module included template.
        extract($this->attributes);

        // File attribute is mandatory
        if (!isset($file)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'file\' attribute in <xar:template> tag.', $this);
            return;
        }

        // Resolve the file attribute
        $file = xarTpl__ExpressionTransformer::transformPHPExpression($file);
        if (!isset($file)) {
            return;
        }

        // Resolve subdata attribute
        $subdata = xarTpl__ExpressionTransformer::transformPHPExpression($subdata);

        switch($type) {
        case 'theme':
            return "xarTpl_includeThemeTemplate(\"$file\", $subdata)";
            break;
        case 'module':
            // Module attribute is optional
            if(!isset($module)) {
                // No module attribute specified, determine it
                // The module which needs to be passed in needs to come from the location of the
                // template which holds the tag, not the active module although they will be the same
                // in most cases. If the active module would be passed in, this would break when
                // calling API functions from other modules which in turn use a template (rare, but possible,
                // like generating xml with blocklayout). By passing in the modulename which holds the
                // template, we make sure that the include resolves to the right file.
                $patharray = explode('/',dirname($this->fileName));
                // We need the value after 'modules' always, whether the container is overridden
                foreach($patharray as $patharrayid => $patharrayname) {
                    if ($patharrayname == 'modules') {
                        $module = $patharray[$patharrayid+1];
                        break;
                    }
                }
            }
            // Resolve the module attribute
            $module = xarTpl__ExpressionTransformer::transformPHPExpression($module);

            return "xarTpl_includeModuleTemplate(\"$module\", \"$file\", $subdata)";
            break;
        case 'system':
            // Tpl Include which cannot be overridden (for xml data for example), file is relative wrt containing file.
            $tplFile = dirname($this->fileName) . '/' . $file;
            return "xarTplFile(\"$tplFile\",$subdata)";
            break;
        default:
            $this->raiseError(XAR_BL_INVALID_ATTRIBUTE,"Invalid value '$type' for 'type' attribute in <xar:template> tag.", $this);
            return;
        }
    }

    function needExceptionsControl()
    {
        return true;
    }
}

/**
 * xarTpl__XarSetNode: <xar:set> tag class
 *
 * @package blocklayout
 * @access private
 * @todo look at supporting xar:set name="$myarray['key']" again
 */
class xarTpl__XarSetNode extends xarTpl__TplTagNode
{
    var $_name;

    function render()
    {
        return '';
    }

    function renderBeginTag()
    {
        extract($this->attributes);

        if (!isset($name)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'name\' attribute in <xar:set> tag.', $this);
            return;
        }
        // Allow specifying name="test" and name="$test" and deprecate the $ form over time
        $this->_name = str_replace(XAR_TOKEN_VAR_START,'',$name);

        return XAR_TOKEN_VAR_START . $this->_name;
    }

    function renderEndTag()
    {
        /**
         *  Register the variable in the bl_data array so it's passed to included templates
         *  see the xar:template tag how this will work and bug 1120 for all the details
         */
        // FIXME: add some checking whether $name already is a template variable
        return ' $_bl_data[\''.$this->_name.'\'] = '. XAR_TOKEN_VAR_START . $this->_name.';';
    }

    function isAssignable()
    {
        return false;
    }

    function hasChildren()
    {
        return true;
    }

    function needAssignment()
    {
        return true;
    }

    function hasText()
    {
        return true;
    }
}

/**
 * xarTpl__XarBreakNode: <xar:break/> tag class
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarBreakNode extends xarTpl__TplTagNode
{
    function render()
    {
        $depth = 1;
        extract($this->attributes);
        return " break $depth; ";
    }

    function isAssignable()
    {
        return false;
    }

    function needParameter()
    {
        return false;
    }
}

/**
 * xarTpl__XarContinueNode: <xar:continue/> tag class
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarContinueNode extends xarTpl__TplTagNode
{
    function render()
    {
        $depth = 1;
        extract($this->attributes);
        return  " continue $depth; ";
    }

    function isAssignable()
    {
        return false;
    }

    function needParameter()
    {
        return false;
    }
}


/**
 * xarTpl__XarOtherNode: handle module registered tags
 *
 * @package blocklayout
 * @access private
 * @todo improve the flexibility for registered tags/foreign tags
 * @todo add the possibility to be 'relaxed', just ignoring unknown tags?
 * @todo find a way to add renderbegin and renderend methods so custom tags can have open form
 * @todo should expression resolving for attributes be done here or in the handler?
 */
class xarTpl__XarOtherNode extends xarTpl__TplTagNode
{
    var $tagobject;

    function constructor(&$parser, $tagName)
    {
        xarLogMessage("Constructing custom tag: $tagName");
        parent::constructor($parser, $tagName);
        $this->tagobject = xarTplGetTagObjectFromName($tagName);
    }

    function render()
    {
        assert('isset($this->tagobject); /* The tagobject should have been set when constructing */');
        if (!xarTplCheckTagAttributes($this->tagName, $this->attributes)) return;
        return $this->tagobject->callHandler($this->attributes);
    }

    function isAssignable()
    {
        return $this->tagobject->isAssignable();
    }

    function isPHPCode()
    {
        return $this->tagobject->isPHPCode();
    }

    function hasText()
    {
        return $this->tagobject->hasText();
    }

    function needAssignment()
    {
        return $this->tagobject->needAssignement();
    }

    function hasChildren()
    {
        return $this->tagobject->hasChildren();
    }

    function needParameter()
    {
        return $this->tagobject->needParameter();
    }

    function needExceptionsControl()
    {
        return $this->tagobject->needExceptionsControl();
    }

}

/**
 * xarTpl__XarBlocklayoutNode : blocklayouts root tag
 *
 * xar:blocklayout is the root tage for the blocklayout xml dialect
 *
 * @package blocklayout
 * @access  private
 * @todo check if we are in a page template, and whether we already have the root tag
 */
class xarTpl__XarBlocklayoutNode extends xarTpl__TplTagNode
{
    function constructor(&$parser,$tagName)
    {
        parent::constructor($parser, $tagName);
        $parser->tagRootSeen = true; // Ladies and gentlemen, we got him!
    }

    function hasChildren()
    {
        return true;
    }

    function hasText()
    {
        return true;
    }


    function renderBeginTag()
    {
        $content = 'text/html'; // Default content type
        extract($this->attributes);
        if(!isset($version)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'version\' attribute in <xar:blocklayout> tag.', $this);
            return;
        }

        // Literally copy the content type, charset is determined by MLS
        // FIXME: this explicitly limits to one locale per page, do we want that?
        $headercode = '
            $_bl_locale  = xarMLSGetCurrentLocale();
            $_bl_charset = xarMLSGetCharsetFromLocale($_bl_locale);
            header("Content-Type: ' . $content . '; charset=$_bl_charset");';
        return $headercode;
    }

    function renderEndTag()
    {
        return ' ';
    }

    function isAssignable()
    {
        return false;
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