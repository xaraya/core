<?php
/**
 * File: $Id: s.xarBLCompiler.php 1.71 03/07/05 11:35:12+02:00 marcel@hsdev.com $
 *
 * BlockLayout Template Engine Compiler
 *
 * @package blocklayout
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Marco Canini <marco@xaraya.com>
 * @author Paul Rosania  <paul@xaraya.com>
 * @author Marcel van der Boom <marcel@xaraya.com>
 * @author Marty Vance <dracos@xaraya.com>
 * @author Garrett Hunter <garrett@blacktower.com>
 */


/**
 * Defines for comment specifiers
 *
 */
define('XAR_TOKEN_BL_COMMENT', 1);
define('XAR_TOKEN_BL_COMMENT_OPEN', '<!---');
define('XAR_TOKEN_BL_COMMENT_CLOSE', '--->');
define('XAR_TOKEN_HTML_COMMENT', 2);
define('XAR_TOKEN_HTML_COMMENT_OPEN', '<!--');
define('XAR_TOKEN_HTML_COMMENT_CLOSE', '-->');

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
    function xarTpl__CompilerError($msg)
    {
        $this->SystemException($msg);
    }
}

/**
 * xarTpl__ParserError
 *
 * class to hold parser errors, the constructor makes up
 * the msg to pass on as the exception message.
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__ParserError extends SystemException
{
    function xarTpl__ParserError($msg, $posInfo)
    {
        $msg = 'Template error in file '.$posInfo->fileName.
            ' at line '.$posInfo->line.
            ', column '.$posInfo->column.
            ":\n\n".$msg;
        $msg .= "\n\n" . $posInfo->lineText . "\n";
        if ($posInfo->column - 1 > 0) {
            $msg .= str_repeat('-', $posInfo->column - 3);
        }
        $msg .= '^';
        $this->SystemException($msg);
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
class xarTpl__PositionInfo
{
    var $fileName = '';
    var $line = 1;
    var $column = 1;
    var $lineText = '';
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
class xarTpl__Compiler
{
    var $parser;
    var $codeGenerator;

    function xarTpl__Compiler()
    {
        $this->parser = new xarTpl__Parser();
        $this->codeGenerator = new xarTpl__CodeGenerator();
    }

    function compileFile($fileName)
    {
        // The @ makes the code better to handle, leave it.
        if (!($fp = @fopen($fileName, 'r'))) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'COMPILER_ERROR',
                            new xarTpl__CompilerError("Cannot open template file '$fileName'."));
            return;
        }
        $templateSource = fread($fp, filesize($fileName));

        $this->parser->setFileName($fileName);
        return $this->compile($templateSource);
    }

    function compile($templateSource)
    {
        $documentTree = $this->parser->parse($templateSource);
        if (!isset($documentTree)) {
            return; // throw back
        }
        return $this->codeGenerator->generate($documentTree);
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
class xarTpl__CodeGenerator
{
    var $isPHPBlock = false;
    var $pendingExceptionsControl = false;

    function isPHPBlock()
    {
        return $this->isPHPBlock;
    }

    function setPHPBlock($isPHPBlock)
    {
        $this->isPHPBlock = $isPHPBlock;
    }

    function isPendingExceptionsControl()
    {
        return $this->pendingExceptionsControl;
    }

    function setPendingExceptionsControl($pendingExceptionsControl)
    {
        $this->pendingExceptionsControl = $pendingExceptionsControl;
    }

    function generate($documentTree)
    {
        if ($documentTree->variables->get('type') == 'page') {
            $resolver =& xarTpl__SpecialVariableNamesResolver::instance();
            // Register special variables for templates of type page
            $resolver->push('tpl:pageTitle', '$_bl_page_title');
            $resolver->push('tpl:additionalStyles', '$_bl_additional_styles');
            $resolver->push('tpl:headJavaScript', '$_bl_head_javascript');
            $resolver->push('tpl:bodyJavaScript', '$_bl_body_javascript');
        }

        $code = $this->generateNode($documentTree);
        if (!isset($code)) {
            return; // throw back
        }

        if (!$this->isPHPBlock()) {
            $code .= "<?php ";
            $this->setPHPBlock(true);
        }
        if ($this->isPHPBlock()) {
            // MrB: removed the returns, are stripped anyway
            $code .= "return true;?>";
            $this->setPHPBlock(false);
        }
        //xarLogMessage('generate code: '.$code, XARLOG_LEVEL_ERROR);
        return $code;
    }

    function generateNode($node)
    {
        //xarLogMessage('generateNode '.$node->tagName, XARLOG_LEVEL_ERROR);
        //if ($node->hasChildren() && $node->children != NULL /*|| $node->hasText()*/) {
        if ($node->hasChildren() && isset($node->children) /*|| $node->hasText()*/) {
            if ($node->isPHPCode() && !$this->isPHPBlock()) {
                $code .= "<?php ";
                $this->setPHPBlock(true);
            }
            $code = $node->renderBeginTag();
            if (!isset($code)) {
                return; // throw back
            }
            $checkNode = $node;
            foreach ($node->children as $child) {
                if ($child->isPHPCode() && !$this->isPHPBlock()) {
                    $code .= "<?php ";
                    $this->setPHPBlock(true);
                } elseif (!$child->isPHPCode() && $this->isPHPBlock() && !$checkNode->needAssignment()) {
                    $code .= "?>";
                    $this->setPHPBlock(false);
                }
                //xarLogVariable('child', $child, XARLOG_LEVEL_ERROR);
                if ($checkNode->needAssignment() || $checkNode->needParameter()) {
                    if (!$child->isAssignable() && $child->tagName != 'TextNode') {
                        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                                        new xarTpl__ParserError("The '".$checkNode->tagName."' tag cannot have children of type '".$child->tagName."'.", $child));
                        return;
                    }

                    if ($checkNode->needAssignment()) {
                        $code .= ' = ';
                    }
                    //$checkNode = $child;
                } elseif ($child->isAssignable()) {
                    $code .= 'echo ';
                }
                $childCode = $this->generateNode($child);
                if (!isset($childCode)) {
                    return; // throw back
                }
                // Commented out the code that will patch a security hole in xar:set
                // We need to see if there is anyone using xar:set with php/xaraya functions
                //if ($child->tagName != 'TextNode' || !$checkNode->needAssignment()) {
                    $code .= $childCode;
                //} else {
                    //$code .= "'" . strtr($childCode, array("\\" => "\\\\", "'" => "\\'")) . "'";
                //}
                if ($child->isAssignable() && !($checkNode->needParameter()) || $checkNode->needAssignment()) {
                    //xarLogVariable('checkNode', $checkNode, XARLOG_LEVEL_ERROR);
                    //xarLogMessage('here', XARLOG_LEVEL_ERROR);
                    $code .= "; ";
                    if ($child->needExceptionsControl() || $this->isPendingExceptionsControl()) {
                        //xarLogMessage('exception control 1', XARLOG_LEVEL_ERROR);
                        $code .= "if (xarExceptionMajor() != XAR_NO_EXCEPTION) return false; ";
                        $this->setPendingExceptionsControl(false);
                    }
                } else {

                    //xarLogVariable('pass here', $child->tagName, XARLOG_LEVEL_ERROR);
                    if ($child->needExceptionsControl()) {
                        //xarLogVariable('pendingExceptionsControl', $child->tagName, XARLOG_LEVEL_ERROR);
                        $this->setPendingExceptionsControl(true);
                    }
                }

                //$checkNode = $child;
            }
            if ($node->isPHPCode() && !$this->isPHPBlock()) {
                $code .= "<?php ";
                $this->setPHPBlock(true);
            }
            $endCode = $node->renderEndTag();
            if (!isset($endCode)) {
                return; // throw back
            }
            $code .= $endCode;
            if (!$node->isAssignable() && ($node->needExceptionsControl() /*&& $this->isPendingExceptionsControl()*/)) {
                if (!$this->isPHPBlock()) {
                    $code .= "<?php ";
                    $this->setPHPBlock(true);
                }
                //xarLogVariable('final control', $node->tagName, XARLOG_LEVEL_ERROR);
                //xarLogVariable('final control', $node->needExceptionsControl(), XARLOG_LEVEL_ERROR);
                $code .= "if (xarExceptionMajor() != XAR_NO_EXCEPTION) return false; ";
                $this->setPendingExceptionsControl(false);
            }
        } else {
            // If there are no children or no text, we can render it as is.
            $code = $node->render();
            assert('isset($code); /* The rendering code for a node is not working properly */');
            if (!isset($code))  return; // throw back
        }
        //xarLogMessage('exiting generateNode', XARLOG_LEVEL_ERROR);
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

    function xarTpl__Parser()
    {
        $this->nodesFactory = new xarTpl__NodesFactory();
    }

    function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

    function parse($templateSource)
    {
        //xarLogVariable('templateSource', $templateSource, XARLOG_LEVEL_ERROR);
        // <garrett> make sure we only have to deal with \n as CR tokens, replace \r\n
        // <MrB> : Macintosh: \r
        //         Unix     :  \n
        //         Windows  :  \r\n
        $this->templateSource = str_replace('\r\n','\n',$templateSource);

        $this->line = 1;
        $this->column = 1;
        $this->pos = 0;
        $this->lineText = '';

        $this->tagNamesStack = array();
        $this->tagIds = array();

        $this->tplVars = new xarTpl__TemplateVariables();

        $documentTree = $this->nodesFactory->createDocumentNode($this);

        $res = $this->parseNode($documentTree);
        if (!isset($res)) {
            return; // throw back
        }
        $documentTree->children = $res;
        $documentTree->variables = $this->tplVars;

        //xarLogVariable('documentTree', $documentTree, XARLOG_LEVEL_ERROR);
        return $documentTree;
    }

    function parseNode($parent) {
        $children = array();
        $text = '';
        while (true) {
            //xarLogMessage('parseNode', XARLOG_LEVEL_ERROR);
            $token = $this->getNextToken();
            $nextToken = '';
            if (!isset($token)) {
                break;
            }
            switch ($token) {
                //
                // Check for begin tag (<)
                //
            case '<':
                $nextToken = $this->getNextToken();
                //
                // Check for header tag (<?xar)
                //
                if ($nextToken == '?') {
                    $nextToken = $this->getNextToken(3);
                    if ($nextToken == 'xar') {
                        // <?xar header tag
                        // Handle Header Tag
                        $variables = $this->parseHeaderTag();
                        if (!isset($variables)) {
                            return; // throw back
                        }
                        foreach ($variables as $name => $value) {
                            $this->tplVars->set($name, $value);
                        }
                        // Here we set token to an empty string so that $text .= $token will result in $text
                        $token = '';
                        break;
                    }
                    elseif ($nextToken == 'xml') {
                        // <?xml header tag
                        // <Dracos> No idea how to handle this atm
                        // <Mrb> This is why we need <xar:blocklayout> as the root tag
                        //       in the theme, anything before the <xar:blocklayout>
                        //       will be copied verbatim to the output.
                        //       (well, almost verbatim)

                        // Wind forward and copy to output

                        $foundEndXmlHeader=false;
                        $copy = '';
                        while(!$foundEndXmlHeader) {
                            $peek = $this->getNextToken(1);
                            if($peek == '?') {
                                $end = $this->getNextToken();
                                if($end == '>') {
                                    $foundEndXmlHeader = true;
                                }
                            } else {
                                $copy .= $peek;
                            }
                        }

                        // We do the exception check here, so the output is already parsed.
                        if($this->line != 1) {
                            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_SYNTAX',
                                            new xarTpl__ParserError('XML header can only be on the first line of the document',$this));
                            $token ='';
                            // Don't return here, do the rest of the document?
                            break;
                        }

                        // Copy the header to the output
                        $short_open_allowed = ini_get('short_open_tag');
                        if($short_open_allowed) {
                            $token = "<?php echo '<?xml " . $copy . "?>';?>";
                        } else {
                            $token = "<?xml " . $copy . "?>";
                        }
                        break;
                    }
                    // <Dracos>  Embedded php killer
                    elseif ($nextToken == 'php') {
                        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                                        new xarTpl__ParserError("PHP code detected outside allowed syntax ", $this));
                        return;
                    }
                    else {
                        $this->stepBack(3);
                        $nextToken = $this->getNextToken(1);
                        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                                        new xarTpl__ParserError("PHP code detected outside allowed syntax", $this));
                        return;
                    }
                    $this->stepBack(3);
                    //
                    // Check for xar tag (<xar:)
                    //
                } elseif ($nextToken == 'x') {
                    $nextToken = $this->getNextToken(3);
                    if ($nextToken == 'ar:') {
                        // <xar: tag
                        if (!$parent->hasChildren()) {
                            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                                            new xarTpl__ParserError("The '".$parent->tagName."' tag cannot have children.", $parent));
                            return;
                        }
                        // Add text to parent
                        if ($text != '') {
                            if ($parent->hasText()) {
                                $node = $this->nodesFactory->createTextNode($text, $this);
                                $children[] = $node;
                            } elseif (trim($text) != '') {
                                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                                                new xarTpl__ParserError("The '".$parent->tagName."' tag cannot have text.", $parent));
                                return;
                            }
                            $text = '';
                        }

                        // Handle Begin Tag
                        $res = $this->parseBeginTag();
                        if (!isset($res)) {
                            return; // throw back
                        }
                        list($tagName, $attributes, $closed) = $res;
                        // Check for uniqueness of id attribute
                        if (isset($attributes['id'])) {
                            if (isset($this->tagIds[$attributes['id']])) {
                                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                                                new xarTpl__ParserError("Not unique id in '".$tagName."' tag.", $this));
                                return;
                            }
                            if ($attributes['id'] == '') {
                                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                                                new xarTpl__ParserError("Empty id in '".$tagName."' tag.", $this));
                                return;
                            }
                            $this->tagIds[$attributes['id']] = true;
                        }
                        $node = $this->nodesFactory->createTplTagNode($tagName, $attributes, $parent->tagName, $this);
                        if (!isset($node)) {
                            return; // throw back
                        }
                        //xarLogVariable('node', $node, XARLOG_LEVEL_ERROR);
                        if (!$closed) {
                            array_push($this->tagNamesStack, $tagName);
                            $res = $this->parseNode($node);
                            if (!isset($res)) {
                                return; // throw back
                            }
                            $node->children = $res;
                        }
                        $children[] = $node;
                        // Here we set token to an empty string so that $text .= $token will result in $text
                        $token = '';
                        break;
                    }
                    $this->stepBack(3);

                    //
                    // Check for end tag (</)
                    //
                } elseif ($nextToken == '/') {
                    $nextToken = $this->getNextToken();
                    //
                    // Check for xar end tag
                    //
                    if ($nextToken == 'x') {
                        $nextToken = $this->getNextToken(3);
                        if ($nextToken == 'ar:') {
                            // </xar: tag
                            // Add text to parent
                            if ($text != '') {
                                if ($parent->hasText()) {
                                    $node = $this->nodesFactory->createTextNode($text, $this);
                                    $children[] = $node;
                                } elseif (trim($text) != '') {
                                    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                                                    new xarTpl__ParserError("The '".$parent->tagName."' tag cannot have text.", $parent));
                                    return;
                                }
                                $text = '';
                            }
                            // Handle End Tag
                            $tagName = $this->parseEndTag();
                            if (!isset($tagName)) {
                                return; // throw back
                            }
                            $stackTagName = array_pop($this->tagNamesStack);
                            if ($tagName != $stackTagName) {
                                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                                                new xarTpl__ParserError("Found closed '$tagName' tag where close '$stackTagName' was expected.", $this));
                                return;
                            }
                            return $children;
                        }
                        $this->stepBack(3);
                    }
                    $this->stepBack(1);
                }
                //
                // Check for comments or doctype
                //
                elseif ($nextToken == '!') {
                    // <garett> handle both <!-- and <!--- comment types
                    // (html comments are passed through, BL comments are stripped out)

                    /**
                     * quick check to see if this is comment candidate
                     */
                    $temptoken = $this->getNextToken(1);
                    if ($temptoken == '-') {

                        /**
                         * Rewind back to '<' character to symplify handling of BL comments
                         */
                        $this->stepBack(3); // puts us back at the '<'

                        $commentTag = '';
                        $closingTag = '';

                        /**
                         * Try grabbing the entire token
                         */
                        $temptoken = $this->getNextToken(5);
                        if ($temptoken == XAR_TOKEN_BL_COMMENT_OPEN) {
                            $commentTag = XAR_TOKEN_BL_COMMENT;
                            $closingTag = XAR_TOKEN_BL_COMMENT_CLOSE;
                        } elseif (substr($temptoken,0,4) == XAR_TOKEN_HTML_COMMENT_OPEN) {
                            $commentTag = XAR_TOKEN_HTML_COMMENT;
                            $closingTag = XAR_TOKEN_HTML_COMMENT_CLOSE;
                        }

                        /**
                         * Rewind to the beginning of the token for consistency
                         */
                        $this->stepBack(5);

                        /**
                         * We clear the token here in the event the token turns out
                         * to be a BL comment, which are stripped out of compiled source
                         */
                        $token = '';

                        $foundClosingTag = FALSE;
                        while (!$foundClosingTag) {
                            $temptoken = $this->getNextToken(1);

                            /**
                             * Check for end of file
                             */
                            if (!isset($temptoken)) {
                                $foundClosingTag = TRUE;
                            } elseif ($temptoken == '-') {
                                /**
                                 * Is this the start of a closing tag?
                                 */
                                $endtag = $temptoken . $this->getNextToken(strlen($closingTag)-1);

                                if ($endtag == $closingTag) {
                                    $foundClosingTag = TRUE;
                                    $temptoken = $endtag;
                                } else {
                                    $this->stepBack(strlen($closingTag)-1);
                                }
                            }

                            /**
                             * We rebuild the token in order to avoid evaluating #$foo#
                             * inside comments
                             */
                            if ($commentTag == XAR_TOKEN_HTML_COMMENT) {
                                $token .= $temptoken;
                            }

                        } // END while
                    } else { // end if '-'
                        /**
                         * It's not a comment tag ignore & continue on
                         */
                        $this->stepBack(2);
                    }
                    break;
                } // end elseif

                //<Dracos>  Stop tag embedding, ie <a href="<xar
                $tagcounter = 0;
                while(1){
                    $tagtoken = $this->getNextToken();
                    $tagcounter++;
                    if($tagtoken == '>'){
                        break;
                    }
                    // FIXME: this goes bonkers on embedded javascript
                    if($tagtoken == '<'){
                        xarLogVariable('parent tag',$parent);
                        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                                        new xarTpl__ParserError("Found open tag before close tag.", $this));
                        return;
                    }
                }
                $this->Stepback($tagcounter);
                $this->stepBack(1);
                // xarLogVariable('token', $token, XARLOG_LEVEL_ERROR);
                break;
                //
                // Check for xar entity
                //
                //
            case '&':
                $nextToken = $this->getNextToken(4);
                if ($nextToken == 'xar-') {
                    if (!$parent->hasChildren()) {
                        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                                        new xarTpl__ParserError("The '".$parent->tagName."' tag cannot have children.", $parent));
                        return;
                    }
                    // Add text to parent
                    if ($text != '') {
                        if ($parent->hasText()) {
                            $node = $this->nodesFactory->createTextNode($text, $this);
                            $children[] = $node;
                        } elseif (trim($text) != '') {
                            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                                            new xarTpl__ParserError("The '".$parent->tagName."' tag cannot have text.", $parent));
                            return;
                        }
                        $text = '';
                    }
                    // Handle Entity
                    $res = $this->parseEntity();
                    if (!isset($res)) {
                        return; // throw back
                    }
                    list($entityType, $parameters) = $res;
                    $node = $this->nodesFactory->createTplEntityNode($entityType, $parameters, $this);
                    if (!isset($node)) {
                        return; // throw back
                    }
                    $children[] = $node;
                    $token = '';
                    break;
                }
                $this->stepBack(4);
                break;
            case '#':
                $nextToken = $this->getNextToken(1);

                // Break out of processing if # is escaped as ##
                // TODO <Dracos>:  Rip this out when all the templates are stripped of ##
                if ($nextToken == '#') {
                    break;
                }
                // Break out of processing if nextToken is (, because #(.) is used by MLS
                if ($nextToken == '(') {
                    $token .= '(';
                    break;
                }
                // We expect a variable after the # now or a function
                if ($nextToken == '$' || $nextToken == 'x') {
                    // Check if we have a function in here
                    if($nextToken == 'x'){
                        $temptoken = $nextToken . $this->getNextToken(2);
                        if($temptoken == 'xar'){
                            $tagcounter = 0;
                            $func = 'xar';
                            while(1) {
                                $tagtoken = $this->getNextToken();
                                $tagcounter++;
                                if($tagtoken == '(' || $tagtoken == '#'){ break; }
                                $func .= $tagtoken;
                            }
                            // Only allow xar* functions
                            if(!function_exists($func) && substr($func,0,3) != 'xar'){
                                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                                                new xarTpl__ParserError("Invalid or disallowed API call: $func", $this));
                                return;
                            }
                            $this->stepBack($tagcounter + 2);
                        }
                    }
                    if (!$parent->hasChildren()) {
                        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                                        new xarTpl__ParserError("The '".$parent->tagName."' tag cannot have children.", $parent));
                        return;
                    }
                    // Add text to parent
                    if ($text != '') {
                        if ($parent->hasText()) {
                            $node = $this->nodesFactory->createTextNode($text, $this);
                            $children[] = $node;
                        } elseif (trim($text) != '') {
                            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                                            new xarTpl__ParserError("The '".$parent->tagName."' tag cannot have text.", $parent));
                            return;
                        }
                        $text = '';
                    }
                    $instruction = $nextToken;
                    $distance = 0;
                    while (true) {
                        $nextToken = $this->getNextToken(1);
                        $distance++;
                        if (!isset($token)) {
                            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_FILE',
                                            new xarTpl__ParserError("Unexpected end of the file.", $this));
                            return;
                        } elseif ($nextToken == '#') {
                            // We seem to be at the end, stop here.
                            break;
                            // end patch
                        }
                        elseif ($this->peek() == chr(10)) {
                            $this->stepBack($distance);
                            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                                            new xarTpl__ParserError("Misplaced '#' character. To print the literal '#', use '##'.", $this));
                            return;
                        }
                        $instruction .= $nextToken;
                    }
                    if(strpos($instruction, ';')){
                        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                                        new xarTpl__ParserError("Injected PHP detected in: $instruction", $this));
                        return;
                    }
                    // Instruction is now set to $varname of xFunction(.....)
                    $node = $this->nodesFactory->createTplInstructionNode($instruction, $this);
                    if (!isset($node)) {
                        return; // throw back
                    }
                    $children[] = $node;
                    $token = '';
                    break;
                }
                else {
                    $this->stepBack();
                    break;
                }
            } // end switch
            $text .= $token;
        } // end while
        if ($text != '') {
            if (!$parent->hasText()) {
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                                new xarTpl__ParserError("The '".$parent->tagName."' tag cannot have text inside.", $parent));
                return;
            }
            $node = $this->nodesFactory->createTextNode($text, $this);
            $children[] = $node;
        }
        return $children;
    }

    function parseHeaderTag() {
        $variables = array();
        while (true) {
            $variable = $this->parseTagAttribute();
            if (!isset($variable)) {
                return; // throw back
            }
            if (is_string($variable)) {
                $exitToken = $variable;
                break;
            }
            $variables[$variable[0]] = $variable[1];
        }
        if ($exitToken != '?') {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                            new xarTpl__ParserError("Invalid '$exitToken' character in header tag.", $this));
            return;
        }
        // Must parse the entire tag, we want to find > character
        while (true) {
            $token = $this->getNextToken();
            if (!isset($token)) {
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_FILE',
                                new xarTpl__ParserError("Unexpected end of the file.", $this));
                return;
            }
            if ($token == '<') {
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                                new xarTpl__ParserError("Unclosed tag.", $this));
                return;
            }
            if ($token == '>') {
                break;
            }
        }
        return $variables;
    }

    function parseBeginTag() {
        //xarLogMessage('parseBeginTag', XARLOG_LEVEL_ERROR);
        // Tag name
        $tagName = '';
        while (true) {
            $token = $this->getNextToken();
            if (!isset($token)) {
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_FILE',
                                new xarTpl__ParserError("Unexpected end of the file.", $this));
                return;
            }
            if ($token == '<') {
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                                new xarTpl__ParserError("Unclosed tag.", $this));
                return;
            }
            if ($token == ' ' || $token == '>' || $token == '/') {
                break;
            }
            $tagName .= $token;
        }
        if ($tagName == '') {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                            new xarTpl__ParserError("Unnamed tag.", $this));
            return;
        }
        $attributes = array();
        if ($token == ' ') {
            while (true) {
                $attribute = $this->parseTagAttribute();
                if (!isset($attribute)) {
                    return; // throw back
                }
                if (is_string($attribute)) {
                    $exitToken = $attribute;
                    break;
                }
                $attributes[$attribute[0]] = $attribute[1];
            }
        } else {
            $exitToken = $token;
        }
        if ($exitToken != '>') {
            // Must parse the entire tag, we want to find > character
            while (true) {
                $token = $this->getNextToken();
                if (!isset($token)) {
                    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_FILE',
                                    new xarTpl__ParserError("Unexpected end of the file.", $this));
                    return;
                }
                if ($token == '<') {
                    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                                    new xarTpl__ParserError("Unclosed tag.", $this));
                    return;
                }
                if ($token == '>') {
                    break;
                }
            }
        }
        return array($tagName, $attributes, ($exitToken == '/') ? true : false);
    }

    function parseTagAttribute() {
        //xarLogMessage('parseTagAttribute', XARLOG_LEVEL_ERROR);
        // Tag attribute
        $name = '';
        while (true) {
            $token = $this->getNextToken();
            if (!isset($token)) {
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_FILE',
                                new xarTpl__ParserError("Unexpected end of the file.", $this));
                return;
            } elseif ($token == '"' || $token == "'") {
                $quote = $token;
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                                new xarTpl__ParserError("Invalid '$token' character in attribute name.", $this));
                return;
            } elseif ($token == '<') {
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                                new xarTpl__ParserError("Unclosed tag.", $this));
                return;
            } elseif ($token == '>' || $token == '/' || $token == '?') {
                if (trim($name) != '') {
                    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                                    new xarTpl__ParserError("Invalid '$name' attribute.", $this));
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
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_ATTRIBUTE',
                            new xarTpl__ParserError("Unnamed attribute.", $this));
            return;
        }
        $value = '';
        $quote = '';
        $ok = false;
        while (true) {
            $token = $this->getNextToken();
            if (!isset($token)) {
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_FILE',
                                new xarTpl__ParserError("Unexpected end of the file.", $this));
                return;
            } elseif ($token == '>') {
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_ATTRIBUTE',
                                new xarTpl__ParserError("Unclosed '$name' attribute.", $this));
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
        return array($name, $value);
    }

    function parseEndTag() {
        //xarLogMessage('parseEndTag', XARLOG_LEVEL_ERROR);
        // Tag name
        $tagName = '';
        while (true) {
            $token = $this->getNextToken();
            if (!isset($token)) {
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_FILE',
                                new xarTpl__ParserError("Unexpected end of the file.", $this));
                return;
            } elseif ($token == '<') {
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                                new xarTpl__ParserError("Unclosed tag.", $this));
                return;
            } elseif ($token == '>') {
                break;
            }
            $tagName .= $token;
        }
        $tagName = rtrim($tagName);
        if ($tagName == '') {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                            new xarTpl__ParserError("Unnamed tag.", $this));
            return;
        }
        return $tagName;
    }

    function parseEntity() {
        //xarLogMessage('parseEndTag', XARLOG_LEVEL_ERROR);
        // Entity type
        $entityType = '';
        while (true) {
            $token = $this->getNextToken();
            if (!isset($token)) {
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_FILE',
                                new xarTpl__ParserError("Unexpected end of the file.", $this));
                return;
            } elseif ($token == '-' || $token == ';') {
                break;
            }
            $entityType .= $token;
        }
        if ($entityType == '') {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_ENTITY',
                            new xarTpl__ParserError("Untyped entity.", $this));
            return;
        }
        $parameters = array();
        if ($token == '-') {
            $parameter = '';
            while (true) {
                $token = $this->getNextToken();
                if (!isset($token)) {
                    xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_FILE',
                                   new xarTpl__ParserError("Unexpected end of the file.", $this));
                    return;
                } elseif ($token == ';') {
                    if ($parameter == '') {
                        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_ENTITY',
                                       new xarTpl__ParserError("Empty parameter.", $this));
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

    function getNextToken($len = 1)
    {
        $token = substr($this->templateSource, $this->pos, 1);
        if ($token === false) {
            // This line fixes a bug that happen when $len is > 1
            // and the file ends before the token has been read
            $this->pos += $len;
            return;
        }
        $this->lineText .= $token;

        $this->pos++;
        $this->column++;
        if ($token == "\n") {
            $this->line++;
            $this->column = 0;
            $this->lineText = '';
        }
        if ($len != 1) {
            $token .= $this->getNextToken($len - 1);
        }
        //xarLogVariable('token', $token, XARLOG_LEVEL_ERROR);

        return $token;
    }

    function stepBack($len = 1)
    {
        $this->pos -= $len;
        $this->column -= $len;
        $this->lineText = substr($this->lineText, 0, strlen($this->lineText) - $len);
    }

    function peek($len = 1, $start = '')
    {
        if ($start == '') {
            $start = $this->pos;
        }
        if ($start < 0) {
            $start = 0;
        }

        $token = substr($this->templateSource, $start, 1);
        if ($token === false) {
            return;
        }
        //$this->lineText .= $token;

        // <MrB> The \r\n combo shouldn't happen anymore, as we filter that out,
        // commented it out for now.
//         if ($token == "\r") {
//             if (substr($this->templateSource, $start + 1, 1) == "\n") {
//                 // Check for \r\n
//                 $start++;
//             }
//             $token = "\n";
//         }
//         $start++;
        //$this->column++;
        /*if ($token == "\n") {
            $this->line++;
            $this->column = 0;
            $this->lineText = '';
        }*/
        if ($len != 1) {
            $token .= $this->peek($len - 1, $start);
        }
        //xarLogVariable('token', $token, XARLOG_LEVEL_ERROR);

        return $token;
    }
}

/**
 * xarTpl__NodesFactory - class which constructs nodes in the document tree
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__NodesFactory
{

    function createTplTagNode($tagName, $attributes, $parentTagName, $parser)
    {
        // Core tags
        switch ($tagName) {
            case 'var':
                $node = new xarTpl__XarVarNode();
                break;
            case 'loop':
                $node = new xarTpl__XarLoopNode();
                break;
            case 'sec':
                $node = new xarTpl__XarSecNode();
                break;
            // marco: this should be deleted right, it's not in spec
            case 'ternary':
                $node = new xarTpl__XarTernaryNode();
                break;
            case 'if':
                $node = new xarTpl__XarIfNode();
                break;
            case 'elseif':
                $node = new xarTpl__XarElseifNode();
                break;
            case 'else':
                $node = new xarTpl__XarElseNode();
                break;
            case 'while':
                $node = new xarTpl__XarWhileNode();
                break;
            case 'for':
                $node = new xarTpl__XarForNode();
                break;
            case 'foreach':
                $node = new xarTpl__XarForEachNode();
                break;
            case 'block':
                $node = new xarTpl__XarBlockNode();
                break;
            case 'blockgroup':
                $node = new xarTpl__XarBlockGroupNode();
                break;
            case 'ml':
                $node = new xarTpl__XarMlNode();
                break;
            case 'mlkey':
                $node = new xarTpl__XarMlkeyNode();
                break;
            case 'mlstring':
                $node = new xarTpl__XarMlstringNode();
                break;
            case 'mlvar':
                $node = new xarTpl__XarMlvarNode();
                break;
            case 'comment':
                $node = new xarTpl__XarCommentNode();
                break;
            case 'module':
                $node = new xarTpl__XarModuleNode();
                break;
            case 'event':
                $node = new xarTpl__XarEventNode();
                break;
            case 'template':
                $node = new xarTpl__XarTemplateNode();
                break;
            case 'set':
                $node = new xarTpl__XarSetNode();
                break;
            case 'break':
                $node = new xarTpl__XarBreakNode();
                break;
            case 'continue':
                $node = new xarTpl__XarContinueNode();
                break;
          // <Dracos>  Widgets begin here

            default:
                // FIXME: check if this is how you want to support module-registered tags
                $node = new xarTpl__XarOtherNode($tagName);
                break;
        }
        if (isset($node)) {
            $node->tagName = $tagName;
            $node->parentTagName = $parentTagName;
            $node->fileName = $parser->fileName;
            $node->line = $parser->line;
            $node->column = $parser->column;
            $node->lineText = $parser->lineText;
            $node->attributes = $attributes;
            return $node;
        }
        // FIXME: how do you handle new tags registered by module developers ?
        // TODO: is xarTplRegisterTag still supposed to work for this ?
        // If we get here, the tag doesn't exist so we raise a user exception
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                       new xarTpl__ParserError("Cannot instantiate nonexistent tag '$tagName'.", $parser));
        return;
    }

    function createTplEntityNode($entityType, $parameters, $parser)
    {
        switch ($entityType) {
            case 'var':
                $node = new xarTpl__XarVarEntityNode();
                break;
            case 'config':
                $node = new xarTpl__XarConfigEntityNode();
                break;
            case 'mod':
                $node = new xarTpl__XarModEntityNode();
                break;
            case 'session':
                $node = new xarTpl__XarSessionEntityNode();
                break;
            case 'modurl':
                $node = new xarTpl__XarModurlEntityNode();
                break;
            case 'url':
                $node = new xarTpl__XarUrlEntityNode();
                break;
            case 'baseurl':
                $node = new xarTpl__XarBaseurlEntityNode();
                break;
        }
        if (isset($node)) {
            $node->tagName = 'EntityNode';
            $node->entityType = $entityType;
            $node->fileName = $parser->fileName;
            $node->line = $parser->line;
            $node->column = $parser->column;
            $node->lineText = $parser->lineText;
            $node->parameters = $parameters;
            return $node;
        }
        // FIXME: how do you handle new entities registered by module developers ?
        // TODO: how do you register new entities in the first place ?
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_ENTITY',
                       new xarTpl__ParserError("Cannot instantiate nonexistent entity '$entityType'.", $parser));
        return;
    }

    function createTplInstructionNode($instruction, $parser)
    {
        if ($instruction[0] == '$') {
            $node = new xarTpl__XarVarInstructionNode();
        } else {
            $node = new xarTpl__XarApiInstructionNode();
        }

        if (isset($node)) {
            $node->tagName = 'InstructionNode';
            $node->fileName = $parser->fileName;
            $node->line = $parser->line;
            $node->column = $parser->column;
            $node->lineText = $parser->lineText;
            $node->instruction = $instruction;
            return $node;
        }

        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_INSTRUCTION',
                       new xarTpl__ParserError("Cannot instantiate nonexistent instruction '#$instruction#'.", $parser));
        return;
    }

    function createTextNode($content, $parser)
    {
        $node = new xarTpl__TextNode();
        $node->tagName = 'TextNode';
        $node->content = $content;
        $node->fileName = $parser->fileName;
        $node->line = $parser->line;
        $node->column = $parser->column;
        $node->lineText = $parser->lineText;
        return $node;
    }

    function createDocumentNode($parser)
    {
        $node = new xarTpl__DocumentNode();
        $node->tagName = 'DocumentNode';
        $node->fileName = $parser->fileName;
        return $node;
    }
}

/**
 * xarTpl__SpecialVariableNameResolver
 *
 * This resolves special variables in the template to their real values
 * a mapping is used to keep this information.
 * This class is a singleton
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__SpecialVariableNamesResolver
{
    var $varsMapping = array();

    function &instance() {
        static $instance = NULL;
        if (!isset($instance)) {
            $instance = new xarTpl__SpecialVariableNamesResolver();
        }
        return $instance;
    }

    function push($specialVarName, $realVarName)
    {
        if (!isset($this->varsMapping[$specialVarName])) {
            $this->varsMapping[$specialVarName] = array();
        }
        array_push($this->varsMapping[$specialVarName], $realVarName);
    }

    function pop($specialVarName)
    {
        array_pop($this->varsMapping[$specialVarName]);
    }

    function resolve($specialVarName, $posInfo)
    {
        if (!isset($this->varsMapping[$specialVarName])) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_SPECIALVARIABLE',
                           new xarTpl__ParserError("Invalid use of '$specialVarName' special variable.", $posInfo));
            return;
        }
        return $this->varsMapping[$specialVarName][count($this->varsMapping[$specialVarName]) - 1];
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
     * Replaces special variables and changes the array notation.
     * This is the BLExpression grammar:
     * BLExpression ::= Variable | Variable '.' ArrayKey
     * Variable ::= Name | SpecialVariable
     * SpecialVariable ::= Name ':' Name | Name ':' Name ':' Name
     * ArrayKey ::= Name | Name '.' ArrayKey
     * Name ::= [a-zA-Z_] ([0-9a-zA-Z_])*
     */
    function transformBLExpression($blExpression)
    {
        $chunks = explode('.', $blExpression);
        $expression = $chunks[0];
        // Check for special variable
        if (strpos($expression, ':') !== false) {
            // Special varriable

            // Get xarTpl__SpecialVariableNamesResolver instance
            $resolver =& xarTpl__SpecialVariableNamesResolver::instance();
            $expression = $resolver->resolve($expression, $this);
            if (!isset($expression)) {
                return; // throw back
            }
        } else {
            $expression = '$'.$expression;
        }
        for ($i = 1; $i < count($chunks); $i++) {
            $expression .= "['".$chunks[$i]."']";
        }
        return $expression;
    }

    function transformPHPExpression($phpExpression)
    {
        // This regular expression  must match the special variables $foo:bar construct
        // pass it to the resolver, check for exceptions, and replace it with the resolved
        // var name.
        // Let's dissect the expression so it's a bit more clear:
        //  1. /..../i      => we're matching in a case - insensitive  way what's betwteen the /-es
        //  2. \\\$         => matches \$ which is and escaped $ in the string to match
        //  3. (            => this starts a subpattern
        //  4.  [a-z_]      => matches a letter or underscore
        //  5.  [0-9a-z_]*  => matches a number, letter of underscore, zero or more occurrences
        //  6.  (           => starts a subpattern
        //  7.   :          => matches the colon
        //  8.   [0-9a-z_]+ => matches number,letter or underscore, one or more occurrences
        //  9.  )           => matches right brace
        // 10.  {1,2}       => the whole previous subpattern may  appear min. 1 and max 2 times
        // 11. )            => ends the current pattern
        if (preg_match_all("/\\\$([a-z_][0-9a-z_]*(:[0-9a-z_]+){1,2})/i", $phpExpression, $matches)) {
            // Get xarTpl__SpecialVariableNamesResolver instance
            $resolver =& xarTpl__SpecialVariableNamesResolver::instance();
            for ($i = 0; $i < count($matches[0]); $i++) {
                $resolvedName = $resolver->resolve($matches[1][$i], $this);
                if (!isset($resolvedName)) {
                    return; // throw back
                }
                $phpExpression = str_replace($matches[0][$i], $resolvedName, $phpExpression);
            }
        }

        $findLogic      = array(' eq ', ' ne ', ' lt ', ' gt ', ' id ', ' nd ', ' le ', ' ge ');

        $replaceLogic   = array(' == ', ' != ',  ' < ',  ' > ', ' === ', ' !== ', ' <= ', ' >= ');
        $phpExpression = str_replace($findLogic, $replaceLogic, $phpExpression);

        return $phpExpression;
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
    var $tagName;

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
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_INSTRUCTION',
                           new xarTpl__ParserError('Invalid variable reference instruction.', $this));
            return;
        }
        $instruction = xarTpl__ExpressionTransformer::transformPHPExpression($this->instruction);
        if (!isset($instruction)) {
            return; // throw back
        }
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
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_INSTRUCTION',
                           new xarTpl__ParserError('Invalid API reference instruction.', $this));
        }
        $instruction = xarTpl__ExpressionTransformer::transformPHPExpression($this->instruction);
        if (!isset($instruction)) {
            return; // throw back
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
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MISSING_PARAMETER',
                           new xarTpl__ParserError('Parameters mismatch in &xar-var entity.', $this));
            return;
        }
        $name = xarTpl__ExpressionTransformer::transformBLExpression($this->parameters[0]);
        if (!isset($name)) {
            return; // throw back
        }

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
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MISSING_PARAMETER',
                           new xarTpl__ParserError('Parameters mismatch in &xar-config entity.', $this));
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
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MISSING_PARAMETER',
                           new xarTpl__ParserError('Parameters mismatch in &xar-mod entity.', $this));
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
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MISSING_PARAMETER',
                           new xarTpl__ParserError('Parameters mismatch in &xar-session entity.', $this));
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
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MISSING_PARAMETER',
                           new xarTpl__ParserError('Parameters mismatch in &xar-modurl entity.', $this));
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
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MISSING_PARAMETER',
                           new xarTpl__ParserError('Parameters mismatch in &xar-url entity.', $this));
            return;
        }
        $module = $this->parameters[0];
        if ($module == '') {
            $tplVars =& xarTpl__TemplateVariables::instance();
            $module = $tplVars->get('module');
            if (empty($module)) {
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MISSING_PARAMETER',
                               new xarTpl__ParserError('Empty module parameter in &xar-url entity.', $this));
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
        extract($this->attributes);

        if (!isset($name)) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MISSING_ATTRIBUTE',
                            new xarTpl__ParserError('Missing \'name\' attribute in <xar:var> tag.', $this));
            return;
        }

        if (!isset($scope)) {
            $scope = 'local';
        }

        switch ($scope) {
        case 'config':
            return "xarConfigGetVar('".$name."')";
        case 'session':
            return "xarSessionGetVar('".$name."')";
        case 'module':
            if (!isset($module)) {
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MISSING_ATTRIBUTE',
                                new xarTpl__ParserError('Missing \'module\' attribute in <xar:var> tag.', $this));
                return;
            }
            return "xarModGetVar('".$module."', '".$name."')";
        case 'theme':
            if (!isset($themeName)) {
                $themeName = xarCore_getSiteVar('BL.DefaultTheme');
            }
            return "xarThemeGetVar('".$themeName."', '".$name."')";
        case 'local':
            $name = xarTpl__ExpressionTransformer::transformPHPExpression($name);
            if (!isset($name)) {
                return; // throw back
            }
            return $name;
        default:
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_ATTRIBUTE',
                            new xarTpl__ParserError('Invalid value for \'local\' attribute in <xar:var> tag.', $this));
            return;
        }
    }

    function needExceptionsControl()
    {
        if (!isset($this->attributes['scope'])) {
            return false;
        }
        return ($this->attributes['scope'] == 'module' || $this->attributes['scope'] == 'config');
    }
}

/**
 * xarTpl__XarLoopNode: <xar:loop> tag class
 *
 * @package blocklayout
 */
class xarTpl__XarLoopNode extends xarTpl__TplTagNode
{
    function loopCounter($operator = NULL)
    {
        static $loopCounter = 0;
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
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MISSING_ATTRIBUTE',
                           new xarTpl__ParserError('Missing \'name\' attribute in <xar:loop> tag.', $this));
            return;
        }

        if (isset($prefix)) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION,'DEPRECATED_ATTRIBUTE',
                            new xarTpl__ParserError('Use of deprecated \'prefix\' attribute in <xar:loop> tag.',$this));
            return;
        }

        $name = xarTpl__ExpressionTransformer::transformPHPExpression($name);
        if (!isset($name)) {
            return; // throw back
        }

        // Increment the loopCounter and retrieve its new value
        $loopCounter = xarTpl__XarLoopNode::loopCounter('++');
        // Get xarTpl__SpecialVariableNamesResolver instance
        $resolver =& xarTpl__SpecialVariableNamesResolver::instance();
        // Register special variables
        $resolver->push('loop:item', '$_bl_loop_item'.$loopCounter);
        $resolver->push('loop:key', '$_bl_loop_key'.$loopCounter);
        $resolver->push('loop:index', '$_bl_loop_index'.$loopCounter);
        $resolver->push('loop:number', '$_bl_loop_number'.$loopCounter);

        if (isset($id)) {
            // Register special variables for tag id
            $resolver->push("loop:$id:item", '$_bl_loop_item'.$loopCounter);
            $resolver->push("loop:$id:key", '$_bl_loop_key'.$loopCounter);
            $resolver->push("loop:$id:index", '$_bl_loop_index'.$loopCounter);
            $resolver->push("loop:$id:number", '$_bl_loop_number'.$loopCounter);
        }

        $output = '$_bl_loop_index'.$loopCounter." = 0; ";
        $output .= '$_bl_loop_number'.$loopCounter." = 1; ";
        $output .= 'foreach ('.$name.' as $_bl_loop_key'.$loopCounter.' => $_bl_loop_item'.$loopCounter.") { ";

        if(!isset($id))
            $prefix = '_bl_loop_'.$loopCounter;
        else
            $prefix = '_bl_loop_'.$id;
        $output .= 'extract($_bl_loop_item'.$loopCounter.", EXTR_PREFIX_ALL, '$prefix'); ";

        return $output;
    }

    function renderEndTag()
    {
        // Decrement the loopCounter
        // $loopCounter is the new value + 1
        $loopCounter = xarTpl__XarLoopNode::loopCounter('--') + 1;

        // Get xarTpl__SpecialVariableNamesResolver instance
        $resolver =& xarTpl__SpecialVariableNamesResolver::instance();
        // Register special variables
        $resolver->pop('loop:item');
        $resolver->pop('loop:key');
        $resolver->pop('loop:index');
        $resolver->pop('loop:number');

        $output = '$_bl_loop_index'.$loopCounter."++; ";
        $output .= '$_bl_loop_number'.$loopCounter."++; ";
        $output .= "} ";
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
        extract($this->attributes);

        if (!isset($mask)) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MISSING_ATTRIBUTE',
                           new xarTpl__ParserError('Missing \'mask\' attribute in <xar:sec> tag.', $this));
            return;
        }

        if (!isset($catch)) {
            $catch = 1;
        } else {
            if ($catch == 'true') {
                $catch = 1;
            } elseif ($catch == 'false') {
                $catch = 0;
            } else {
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_ATTRIBUTE',
                new xarTpl__ParserError('Invalid \'catch\' attribute in <xar:sec> tag.'.
                                        ' \'catch\' must be boolean (true or false).', $this));
                return;
            }
        }


        if (isset($component)) {
            $component = xarTpl__ExpressionTransformer::transformPHPExpression($component);
        } else {
            $component = '';
        }

        if (isset($instance)) {
            $instance = xarTpl__ExpressionTransformer::transformPHPExpression($instance);
        } else {
            $instance = '';
        }

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
 * xarTpl__XarTernaryNode: <xar:ternary> tag class
 *
 * Wraps (condition) ? ops : otherops;
 *
 * @package blocklayout
 * @deprecated
 */
class xarTpl__XarTernaryNode extends xarTpl__TplTagNode
{
    function renderBeginTag()
    {
        extract($this->attributes);

        if (!isset($condition)) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MISSING_ATTRIBUTE',
                           new xarTpl__ParserError('Missing \'condition\' attribute in <xar:ternary> tag.', $this));
            return;
        }

        if (count($this->children) != 3 || $this->children[1]->tagName != 'else') {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                           new xarTpl__ParserError('Missing subexpressions or \'else\' tag in <xar:ternary> tag.', $this));
            return;
        }

        $condition = xarTpl__ExpressionTransformer::transformPHPExpression($condition);
        if (!isset($condition)) {
            return; // throw back
        }

        return "($condition) ? ";
    }

    function renderEndTag()
    {
        return '';
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
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MISSING_ATTRIBUTE',
                           new xarTpl__ParserError('Missing \'condition\' attribute in <xar:if> tag.', $this));
            return;
        }

        $condition = xarTpl__ExpressionTransformer::transformPHPExpression($condition);
        if (!isset($condition)) {
            return; // throw back
        }

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
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MISSING_ATTRIBUTE',
                           new xarTpl__ParserError('Missing \'condition\' attribute in <xar:elseif> tag.', $this));
            return;
        }

        $condition = xarTpl__ExpressionTransformer::transformPHPExpression($condition);
        if (!isset($condition)) {
            return; // throw back
        }

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
 * Takes care of the "} else {" construct for both if, else and ternary tags
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
            case 'ternary':
                $output = " : ";
                break;
            default:
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                           new xarTpl__ParserError("The <xar:else> tag cannot be placed under '".$this->parentTagName."' tag.", $this));
                return;
        }
        return $output;
    }

    function isAssignable()
    {
        return ($this->parentTagName == 'ternary');
    }

    function needParameter()
    {
        return ($this->parentTagName == 'ternary');
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
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MISSING_ATTRIBUTE',
                           new xarTpl__ParserError('Missing \'condition\' attribute in <xar:while> tag.', $this));
            return;
        }

        $condition = xarTpl__ExpressionTransformer::transformPHPExpression($condition);
        if (!isset($condition)) {
            return; // throw back
        }

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
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MISSING_ATTRIBUTE',
                           new xarTpl__ParserError('Missing \'start\' attribute in <xar:for> tag.', $this));
            return;
        }

        if (!isset($test)) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MISSING_ATTRIBUTE',
                           new xarTpl__ParserError('Missing \'test\' attribute in <xar:for> tag.', $this));
            return;
        }

        if (!isset($iter)) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MISSING_ATTRIBUTE',
                           new xarTpl__ParserError('Missing \'iter\' attribute in <xar:for> tag.', $this));
            return;
        }

        $start = xarTpl__ExpressionTransformer::transformPHPExpression($start);
        if (!isset($start)) {
            return; // throw back
        }
        $test = xarTpl__ExpressionTransformer::transformPHPExpression($test);
        if (!isset($test)) {
            return; // throw back
        }
        $iter = xarTpl__ExpressionTransformer::transformPHPExpression($iter);
        if (!isset($iter)) {
            return; // throw back
        }

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
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MISSING_ATTRIBUTE',
                           new xarTpl__ParserError('Missing \'in\' attribute in <xar:foreach> tag.', $this));
            return;
        }

        if (!array($in)) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_ATTRIBUTE',
                           new xarTpl__ParserError('Invalid \'in\' attribute in <xar:foreach> tag. \'in\' must be an array', $this));
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
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MISSING_ATTRIBUTE',
                           new xarTpl__ParserError('Missing \'key\' or \'value\' attribute in <xar:foreach> tag.', $this));
            return;
        }
    }

    function renderEndTag()
    {
        if(isset($this->attr_value) && isset($this->attr_key)) 
            return "} @$this->attr_value = $this->valsavename; @$this->attr_key = $this->keysavename; ";
        if(isset($this->attr_value))
            return "} @$this->attr_value = $this->valsavename; ";
        if(isset($this->attr_key))
            return "} @$this->attr_key = $this->keysavename; ";

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
 * @package blocklayout
 * @access private
 */
class xarTpl__XarBlockNode extends xarTpl__TplTagNode
{
    function renderBeginTag()
    {
        extract($this->attributes);

        if (!isset($name)) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MISSING_ATTRIBUTE',
                           new xarTpl__ParserError('Missing \'name\' attribute in <xar:block> tag.', $this));
            return;
        }

        if (!isset($module)) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MISSING_ATTRIBUTE',
                           new xarTpl__ParserError('Missing \'module\' attribute in <xar:block> tag.', $this));
            return;
        }

        if (!isset($content)) {
            $content = '';
        }

        if (!isset($title)) {
            $title = '';
        }

        if (!isset($template)) {
            $template = '';
        }

        // If the block instance attribute is specified in the tag, render it directly 
        // NOTE: $id is also an attribute, but that is an id attribute in XML sense, not in DB sense
        if (isset($instance)) {
            return "xarBlock_renderBlock(array('bid' => '$instance'))";
        } else {
            $instance = 0;
        }

        // Calculate block ID - theme dependent
        // FIXME: 
        // <marco>    What is this for?
        // <mikespub> for block caching, perhaps ? Note that there's not necessarily a unique id here !
        // <mrb>      i could not figure out where this is used, but $id is optional attribute, so that is not
        //            always set. The bid is never used.
        $bid = md5(xarTplGetThemeName().$instance);
        
        // TODO: allow designers to fill in or override the settings defined in the (serialized) blockinfo['content']
        if (isset($this->children) && count($this->children) > 0) {
            $contentNode = $this->children[0];
            if (isset($contentNode)) {
                $content = trim(addslashes($contentNode->render()));
            }
        }

        $this->children = array();


        // TODO: check it, use xarVar_addSlashes instead of addslashes
        return "xarBlock_render(array('module' => '$module', 'type' => '$name', 'bid' => '$bid',
                                     'title' => \"".addslashes($title)."\", 'content' => '$content',
                                     '_bl_template' => '$template'))";
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
 */
class xarTpl__XarBlockGroupNode extends xarTpl__TplTagNode
{
    function renderBeginTag()
    {
        extract($this->attributes);

        if (!isset($template)) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MISSING_ATTRIBUTE',
                           new xarTpl__ParserError('Must have \'template\' attribute in open <xar:blockgroup> tag.', $this));
            return;
        }

        if (isset($name)) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                       new xarTpl__ParserError('Cannot have \'name\' attribute in open <xar:blockgroup> tag.', $this));
            return;
        }

        return "\$_bl_blockgroup_template = '$template';";
    }

    function renderEndTag()
    {
        return 'unset($_bl_blockgroup_template);';
    }

    function render()
    {
        extract($this->attributes);

        if (!isset($name)) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MISSING_ATTRIBUTE',
                           new xarTpl__ParserError('Missing \'name\' attribute in <xar:blockgroup> tag.', $this));
            return;
        }

        if (isset($template)) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                       new xarTpl__ParserError('Cannot have \'template\' attribute in closed <xar:blockgroup/> tag.', $this));
            return;
        }

        return "xarBlock_renderGroup('$name')";
    }

    function hasChildren()
    {
        return true;
    }

    function needExceptionsControl()
    {
        return true;
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
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                           new xarTpl__ParserError('Missing mlkey and mlstring tags in <xar:ml> tag.', $this));
            return;
        }
        $mlNode = $this->children[0];
        if (!isset($mlNode)) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                           new xarTpl__ParserError('Missing \'mlkey\' and \'mlstring\' tags in <xar:ml> tag.', $this));
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
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                               new xarTpl__ParserError("The '".$this->tagName."' tag cannot have children of type '".$node->tagName."'.", $node));
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
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                           new xarTpl__ParserError('Missing the key inside <xar:mlkey> tag.', $this));
            return;
        }
        if (count($this->attributes) != 0) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                           new xarTpl__ParserError('The <xar:mlkey> tag takes no attributes.', $this));
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
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                           new xarTpl__ParserError('Missing content in <xar:mlkey> tag.', $this));
            return;
        }

        return "xarMLByKey('$key'";
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
    function render()
    {
        // return $this->renderBeginTag() . $this->renderEndTag();
        // Dracos: copying exception checking here...it isn't getting checked in renderBeginTag() for some reason
        // Dracos: this is not the right fix for bug 229, but it works for now
        if (count($this->attributes) != 0) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                           new xarTpl__ParserError('The <xar:mlstring> tag takes no attributes.', $this));
            return;
        }
        $output = $this->renderBeginTag();
        if(!empty($output)){
            return $output . $this->renderEndTag();
        }
        else {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                           new xarTpl__ParserError('Missing the string inside <xar:mlstring> tag.', $this));
            return;
        }
    }

    function renderBeginTag()
    {
        $string = '';

        // Dracos:  these two ifs are never true????
        if (count($this->children) == 0) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                           new xarTpl__ParserError('Missing the string inside <xar:mlstring> tag.', $this));
            return;
        }
        if (count($this->attributes) != 0) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                           new xarTpl__ParserError('The <xar:mlstring> tag takes no attributes.', $this));
            return;
        }
        // Children are only of text type
        foreach($this->children as $node) {
            $string .= $node->render();
        }
        $string = trim($string);
        if ($string == '') {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                           new xarTpl__ParserError('Missing content in <xar:mlstring> tag.', $this));
            return;
        }
        return "xarML(\"".xarVar_addslashes($string)."\"";
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
        // MrB: in main the check is > 1, the check below is better, let it be in the merge
        if (count($this->children) != 1) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                           new xarTpl__ParserError('The <xar:mlvar> tag can contain only one child tag.', $this));
            return;
        }
        // MrB: in main there is no check, this is better, let it be in the merge
        if (count($this->attributes) != 0) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                           new xarTpl__ParserError('The <xar:mlvar> tag takes no attributes.', $this));
            return;
        }

        $codeGenerator = new xarTpl__CodeGenerator();
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
/*
    function hasText()
    {
        return true;
    }
*/
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
 * @todo let this class or derived ones also handle <!-- and <!---
 */
class xarTpl__XarCommentNode extends xarTpl__TplTagNode
{
    function renderBeginTag()
    {
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
 * @package blocklayout
 * @access private
 */
class xarTpl__XarModuleNode extends xarTpl__TplTagNode
{
    function render()
    {
        extract($this->attributes);

        if (!isset($main)) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MISSING_ATTRIBUTE',
                           new xarTpl__ParserError('Missing \'main\' attribute in <xar:module> tag.', $this));
            return;
        }

        return '$_bl_mainModuleOutput';
    }
}

/**
 * xarTpl__XarEventNode: <xar:event> tag class
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarEventNode extends xarTpl__TplTagNode
{
    function render()
    {
        extract($this->attributes);

        if (!isset($name)) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MISSING_ATTRIBUTE',
                           new xarTpl__ParserError('Missing \'name\' attribute in <xar:event> tag.', $this));
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
        extract($this->attributes);

        if (!isset($file)) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MISSING_ATTRIBUTE',
                           new xarTpl__ParserError('Missing \'file\' attribute in <xar:template> tag.', $this));
            return;
        }

        if (!isset($type)) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MISSING_ATTRIBUTE',
                           new xarTpl__ParserError('Missing \'type\' attribute in <xar:template> tag.', $this));
            return;
        }

        if (!isset($subdata)) {
            $subdata = '$_bl_data';
        }

        if ($type == 'theme') {
            return "xarTpl_includeThemeTemplate('$file', $subdata)";
        } elseif ($type == 'module') {
            return "xarTpl_includeModuleTemplate(\$_bl_module_name, '$file', $subdata)";
        } else {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_ATTRIBUTE',
                           new xarTpl__ParserError("Invalid value '$type' for 'type' attribute in <xar:template> tag.", $this));
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
 */
class xarTpl__XarSetNode extends xarTpl__TplTagNode
{
    function render()
    {
        return '';
    }

    function renderBeginTag()
    {
        extract($this->attributes);

        if (!isset($name)) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'MISSING_ATTRIBUTE',
                           new xarTpl__ParserError('Missing \'name\' attribute in <xar:set> tag.', $this));
            return;
        }

        /*
        if (count($this->children) != 1) {
            xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'INVALID_TAG',
                           new xarTpl__ParserError('The <xar:set> tag can contain only one child tag.', $this));
            return;
        }
        */

        return $name;
    }

    function renderEndTag()
    {
        return '';
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
        extract($this->attributes);

        if(isset($depth) && $depth > 1){
            $output = " break $depth; ";
        } else {
            $output = " break; ";
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
 * xarTpl__XarContinueNode: <xar:continue/> tag class
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarContinueNode extends xarTpl__TplTagNode
{
    function render()
    {
        extract($this->attributes);

        if(isset($depth) && $depth > 1){
            $output = " continue $depth; ";
        } else {
            $output = " continue; ";
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
 * xarTpl__XarOtherNode: handle module registered tags
 *
 * @package blocklayout
 * @access private
 * @todo improve the flexibility for registered tags/foreign tags
 * @todo add the possibility to be 'relaxed', just ignoring unknown tags?
 */
class xarTpl__XarOtherNode extends xarTpl__TplTagNode
{
    var $tagobject;

    function xarTpl__XarOtherNode($tagName)
    {
        xarLogMessage("Constructing custom tag: $tagName");
        $this->tagobject = xarTplGetTagObjectFromName($tagName);
        if(!isset($this->tagobject)) {
            // Unset the node so the callee can except
            $this = NULL;;
        }
    }
    
    function render()
    {
        assert('isset($this->tagobject); /* The tagobject should have been set when constructing */');
        if (!xarTplCheckTagAttributes($this->tagName, $this->attributes)) return;
        // let xarTemplate worry about calling the right function :)
        return $this->tagobject->callHandler($this->attributes);
    }

    function isAssignable()
    {
        return false;
    }

    function isPHPCode()
    {
        return true;
    }
}
?>
