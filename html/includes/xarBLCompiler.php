<?php
/**
 * File: $Id$
 *
 * BlockLayout Template Engine Compiler
 *
 * @package blocklayout
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.org
 * @author Marco Canini <m.canini@libero.it>, Paul Rosania
*/









/**
 *
 *
 *
 * @package blocklayout
 */
class xarTpl__CompilerError extends DefaultUserException
{
    function xarTpl__CompilerError($msg)
    {
        $this->DefaultUserException($msg);
    }
}

/**
 *
 * 
 * @package blocklayout
 */
class xarTpl__ParserError extends DefaultUserException
{
    function xarTpl__ParserError($msg, $posInfo)
    {
        $msg = 'Template error in file '.$posInfo->fileName.
               ' at line '.$posInfo->line.
               ', column '.$posInfo->column.
               ': '.$msg;
        $msg .= "\n" . $posInfo->lineText . "\n";
        if ($posInfo->column - 1 > 0) {
            $msg .= str_repeat('-', $posInfo->column - 3);
        }
        $msg .= '^';
        $this->DefaultUserException($msg);
    }
}

/**
 *
 * 
 * @package blocklayout
 */
class xarTpl__PositionInfo
{
    var $fileName = '';
    var $line = 1;
    var $column = 1;
    var $lineText = '';
}

/**
 *
 * 
 * @package blocklayout
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
        if (!($fp = @fopen($fileName, 'r'))) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'CompilerError',
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
 *
 * 
 * @package blocklayout
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
            $code .= "\nreturn true;\n?>";
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
                } elseif (!$child->isPHPCode() && $this->isPHPBlock()) {
                    $code .= "?>";
                    $this->setPHPBlock(false);
                }
                //xarLogVariable('child', $child, XARLOG_LEVEL_ERROR);
                if ($checkNode->needAssignment() || $checkNode->needParameter()) {
                    if (!$child->isAssignable()) {
                        xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
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
                $code .= $childCode;
                if ($child->isAssignable() && !(/*$checkNode->needAssignment() ||*/ $checkNode->needParameter())) {
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
            $code = $node->render();
            if (!isset($code)) {
                return; // throw back
            }
        }
        //xarLogMessage('exiting generateNode', XARLOG_LEVEL_ERROR);
        return $code;
    }
}

/**
 *
 * 
 * @package blocklayout
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
        $this->templateSource = $templateSource;
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
                        $this->stepBack(3);
                    //
                    // Check for xar tag (<xar:)
                    //
                    } elseif ($nextToken == 'x') {
                        $nextToken = $this->getNextToken(3);
                        if ($nextToken == 'ar:') {
                            // <xar: tag
                            //xarLogMessage('found '.$nextToken, XARLOG_LEVEL_ERROR);
                            if (!$parent->hasChildren()) {
                                xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
                                               new xarTpl__ParserError("The '".$parent->tagName."' tag cannot have children.", $parent));
                                return;
                            }
                            // Add text to parent
                            if ($text != '') {
                                if ($parent->hasText()) {
                                    $node = $this->nodesFactory->createTextNode($text, $this);
                                    $children[] = $node;
                                } elseif (trim($text) != '') {
                                    xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
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
                                    xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
                                                   new xarTpl__ParserError("Not unique id in '".$tagName."' tag.", $this));
                                    return;
                                }
                                if ($attributes['id'] == '') {
                                    xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
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
                    // Check for widget tag (<widget:)
                    //
                    } elseif ($nextToken == 'w') {
                        $nextToken = $this->getNextToken(6);
                        if ($nextToken == 'idget:') {
                            // <widget: tag
                            if (!$parent->hasChildren()) {
                                xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
                                               new xarTpl__ParserError("The '".$parent->tagName."' tag cannot have children.", $parent));
                                return;
                            }
                            // Add text to parent
                            if ($text != '') {
                                if ($parent->hasText()) {
                                    $node = $this->nodesFactory->createTextNode($text, $this);
                                    $children[] = $node;
                                } elseif (trim($text) != '') {
                                    xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
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
                                    xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
                                                   new xarTpl__ParserError("Not unique id in '".$tagName."' tag.", $this));
                                    return;
                                }
                                if ($attributes['id'] == '') {
                                    xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
                                                   new xarTpl__ParserError("Empty id in '".$tagName."' tag.", $this));
                                    return;
                                }
                                $this->tagIds[$attributes['id']] = true;
                            }
                            $node = $this->nodesFactory->createWidgetNode($tagName, $attributes, /*$parent->tagName,*/ $this);
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
                        $this->stepBack(6);
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
                                //xarLogMessage('found </pnt:', XARLOG_LEVEL_ERROR);
                                // Add text to parent
                                if ($text != '') {
                                    if ($parent->hasText()) {
                                        $node = $this->nodesFactory->createTextNode($text, $this);
                                        $children[] = $node;
                                    } elseif (trim($text) != '') {
                                        xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
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
                                    xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
                                                   new xarTpl__ParserError("Found closed '$tagName' tag where close '$stackTagName' was expected.", $this));
                                    return;
                                }
                                return $children;
                            }
                            $this->stepBack(3);
                    //
                    // Check for widget end tag (</widget:)
                    //
                        } elseif ($nextToken == 'w') {
                            $nextToken = $this->getNextToken(6);
                            if ($nextToken == 'idget:') {
                                // </widget: tag
                                // Add text to parent
                                if ($text != '') {
                                    if ($parent->hasText()) {
                                        $node = $this->nodesFactory->createTextNode($text, $this);
                                        $children[] = $node;
                                    } elseif (trim($text) != '') {
                                        xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
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
                                    xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
                                                   new Tpl__ParserError("Found closed '$tagName' tag where close '$stackTagName' was expected.", $this));
                                    return;
                                }
                                return $children;
                            }
                            $this->stepBack(6);
                        }
                        $this->stepBack(1);
                    }
                    $this->stepBack(1);
                    //xarLogVariable('token', $token, XARLOG_LEVEL_ERROR);
                    break;
                    //
                    // Check for xar entity
                    //
                case '&':
                    $nextToken = $this->getNextToken(4);
                    if ($nextToken == 'xar-') {
                        if (!$parent->hasChildren()) {
                            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
                                           new xarTpl__ParserError("The '".$parent->tagName."' tag cannot have children.", $parent));
                            return;
                        }
                        // Add text to parent
                        if ($text != '') {
                            if ($parent->hasText()) {
                                $node = $this->nodesFactory->createTextNode($text, $this);
                                $children[] = $node;
                            } elseif (trim($text) != '') {
                                xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
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
                    if ($nextToken == '#') {
                        break;
                    }
                    // Break out of processing if nextToken is (, because #(.) is used by MLS
                    if ($nextToken == '(') {
                        $token .= '(';
                        break;
                    }

                    if ($nextToken != '$' && $nextToken != 'x') { 
                        xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
                                       new xarTpl__ParserError("Misplaced '#' character. To print the literal '#', use '##'.", $this));
                        return;
                    }
                
                    if (!$parent->hasChildren()) {
                        xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
                                       new xarTpl__ParserError("The '".$parent->tagName."' tag cannot have children.", $parent));
                        return;
                    }
                    // Add text to parent
                    if ($text != '') {
                        if ($parent->hasText()) {
                            $node = $this->nodesFactory->createTextNode($text, $this);
                            $children[] = $node;
                        } elseif (trim($text) != '') {
                            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
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
                            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidFile',
                                           new xarTpl__ParserError("Unexpected end of the file.", $this));
                            return;
                        } elseif ($nextToken == '#') {
                            $nextToken = $this->getNextToken(1);
                            if ($nextToken != '#') {
                                $this->stepBack(1);
                                break;
                            }
                            $instruction .= $nextToken;
                        } elseif ($this->peek() == chr(10)) {
                            $this->stepBack($distance);
                            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
                                       new xarTpl__ParserError("Misplaced '#' character. To print the literal '#', use '##'.", $this));
                            return;
                        }
                        $instruction .= $nextToken;
                    }

                    $node = $this->nodesFactory->createTplInstructionNode($instruction, $this);
                    if (!isset($node)) {
                        return; // throw back
                    }
                    $children[] = $node;
                    $token = '';
                    break;
            }
            $text .= $token;
            //xarLogVariable('text', $text, XARLOG_LEVEL_ERROR);
        }
        if ($text != '') {
            if (!$parent->hasText()) {
                xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
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
            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
                           new xarTpl__ParserError("Invalid '$exitToken' character in header tag.", $this));
            return;
        }
        // Must parse the entire tag, we want to find > character
        while (true) {
            $token = $this->getNextToken();
            if (!isset($token)) {
                xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidFile',
                               new xarTpl__ParserError("Unexpected end of the file.", $this));
                return;
            }
            if ($token == '<') {
                xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
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
                xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidFile',
                               new xarTpl__ParserError("Unexpected end of the file.", $this));
                return;
            }
            if ($token == '<') {
                xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
                               new xarTpl__ParserError("Unclosed tag.", $this));
                return;
            }
            if ($token == ' ' || $token == '>' || $token == '/') {
                break;
            }
            $tagName .= $token;
        }
        if ($tagName == '') {
            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
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
                    xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidFile',
                                   new xarTpl__ParserError("Unexpected end of the file.", $this));
                    return;
                }
                if ($token == '<') {
                    xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
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
                xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidFile',
                               new xarTpl__ParserError("Unexpected end of the file.", $this));
                return;
            } elseif ($token == '"' || $token == "'") {
                $quote = $token;
                xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
                               new xarTpl__ParserError("Invalid '$token' character in attribute name.", $this));
                return;
            } elseif ($token == '<') {
                xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
                               new xarTpl__ParserError("Unclosed tag.", $this));
                return;
            } elseif ($token == '>' || $token == '/' || $token == '?') {
                if (trim($name) != '') {
                    xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
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
            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidAttribute',
                           new xarTpl__ParserError("Unnamed attribute.", $this));
            return;
        }
        $value = '';
        $quote = '';
        $ok = false;
        while (true) {
            $token = $this->getNextToken();
            if (!isset($token)) {
                xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidFile',
                               new xarTpl__ParserError("Unexpected end of the file.", $this));
                return;
            } elseif ($token == '>') {
                xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidAttribute',
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
                xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidFile',
                               new xarTpl__ParserError("Unexpected end of the file.", $this));
                return;
            } elseif ($token == '<') {
                xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
                               new xarTpl__ParserError("Unclosed tag.", $this));
                return;
            } elseif ($token == '>') {
                break;
            }
            $tagName .= $token;
        }
        $tagName = rtrim($tagName);
        if ($tagName == '') {
            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
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
                xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidFile',
                               new xarTpl__ParserError("Unexpected end of the file.", $this));
                return;
            } elseif ($token == '-' || $token == ';') {
                break;
            }
            $entityType .= $token;
        }
        if ($entityType == '') {
            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidEntity',
                           new xarTpl__ParserError("Untyped entity.", $this));
            return;
        }
        $parameters = array();
        if ($token == '-') {
            $parameter = '';
            while (true) {
                $token = $this->getNextToken();
                if (!isset($token)) {
                    xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidFile',
                                   new xarTpl__ParserError("Unexpected end of the file.", $this));
                    return;
                } elseif ($token == ';') {
                    if ($parameter == '') {
                        xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidEntity',
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

        if ($token == "\r") {
            if (substr($this->templateSource, $this->pos + 1, 1) == "\n") {
                // Check for \r\n
                $this->pos++;
            }
            $token = "\n";
        }
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
    
    function peek($len = 1, $start = -1)
    {
        if ($start == -1) {
            $start = $this->pos;
        }
        
        $token = substr($this->templateSource, $start, 1);
        if ($token === false) {
            return;
        }
        //$this->lineText .= $token;

        if ($token == "\r") {
            if (substr($this->templateSource, $start + 1, 1) == "\n") {
                // Check for \r\n
                $start++;
            }
            $token = "\n";
        }
        $start++;
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
 *
 * 
 * @package blocklayout
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
           // marco: inlude was replaced by template right?
            case 'include':
                $node = new xarTpl__XarIncludeNode();
                break;
            case 'template':
                $node = new xarTpl__XarTemplateNode();
                break;
            case 'set':
                $node = new xarTpl__XarSetNode();
                break;
            default:
                // FIXME: check if this is how you want to support module-registered tags
                $node = new xarTpl__XarOtherNode();
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
        //If we get here, the tag doesn't exist so we raise a user exception
        xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
                       new xarTpl__ParserError("Cannot instantiate unexistent tag '$tagName'.", $parser));
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
        xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidEntity',
                       new xarTpl__ParserError("Cannot instantiate unexistent entity '$entityType'.", $parser));
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
        
        xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidInstruction',
                       new xarTpl__ParserError("Cannot instantiate non-existent instruction '#$instruction#'.", $parser));
        return;
    }

    function createWidgetNode($widgetName, $attributes, $parser)
    {
        switch ($widgetName) {
            case 'modlink':
                $node = new xarTpl__WidgetModlink();
                break;
            case 'postfield':
                $node = new xarTpl__WidgetPostfield();
                break;
        }
        if (isset($node)) {
            $node->tagName = $widgetName;
            //$node->parentTagName = $parentTagName;
            $node->fileName = $parser->fileName;
            $node->line = $parser->line;
            $node->column = $parser->column;
            $node->lineText = $parser->lineText;
            $node->attributes = $attributes;
            return $node;
        }
        //If we get here, the tag doesn't exist so we raise a user exception
        xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
                       new xarTpl__ParserError("Cannot instantiate unexistent widget '$widgetName'.", $parser));
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
 *
 * 
 * @package blocklayout
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
            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidSpecialVariable',
                           new xarTpl__ParserError("Invalid use of '$specialVarName' special variable.", $posInfo));
            return;
        }
        return $this->varsMapping[$specialVarName][count($this->varsMapping[$specialVarName]) - 1];
    }
}

/**
 *
 * 
 * @package blocklayout
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
 *
 * 
 * @package blocklayout
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
        // FIXME: <marco> Paul, please, check this regular expression, it must match $(foo:bar)
        // pass it to the resolver, check for exceptions, and replace it with the resolved
        // var name.
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
        
        $findLogic      = array(' eq ', ' neq ', ' lt ', ' gt ', ' id ', ' nid ', ' lte ', ' gte ');
        $replaceLogic   = array(' == ', ' != ',  ' < ',  ' > ', ' === ', ' !== ', ' <= ', ' >= ');
        $phpExpression = str_replace($findLogic, $replaceLogic, $phpExpression);
        
        return $phpExpression;
    }
}

/**
 * xarTpl__Node
 *
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
 * isAssignable -> false
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
 *
 * 
 * @package blocklayout
 */
class xarTpl__XarVarInstructionNode extends xarTpl__InstructionNode
{
    function render()
    {
        if (strlen($this->instruction) <= 1) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidInstruction',
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
 *
 * 
 * @package blocklayout
 */
class xarTpl__XarApiInstructionNode extends xarTpl__InstructionNode
{
    function render()
    {
        if (strlen($this->instruction) <= 1) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidInstruction',
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
 *
 * 
 * @package blocklayout
 */
class xarTpl__XarVarEntityNode extends xarTpl__EntityNode
{
    function render()
    {
        if (count($this->parameters) != 1) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingParameter',
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
 *
 * 
 * @package blocklayout
 */
class xarTpl__XarConfigEntityNode extends xarTpl__EntityNode
{
    function render()
    {
        if (count($this->parameters) != 1) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingParameter',
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
 *
 * 
 * @package blocklayout
 */
class xarTpl__XarModEntityNode extends xarTpl__EntityNode
{
    function render()
    {
        if (count($this->parameters) != 2) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingParameter',
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
 *
 * 
 * @package blocklayout
 */
class xarTpl__XarSessionEntityNode extends xarTpl__EntityNode
{
    function render()
    {
        if (count($this->parameters) != 1) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingParameter',
                           new xarTpl__ParserError('Parameters mismatch in &xar-session entity.', $this));
            return;
        }
        $name = $this->parameters[0];
        return "xarSessionGetVar('".$name."')";
    }
}

/**
 *
 * 
 * @package blocklayout
 */
class xarTpl__XarModurlEntityNode extends xarTpl__EntityNode
{
    function render()
    {
        if (count($this->parameters) != 3) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingParameter',
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
 *
 * 
 * @package blocklayout
 */
class xarTpl__XarUrlEntityNode extends xarTpl__EntityNode
{
    function render()
    {
        if (count($this->parameters) < 3) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingParameter',
                           new xarTpl__ParserError('Parameters mismatch in &xar-url entity.', $this));
            return;
        }
        $module = $this->parameters[0];
        if ($module == '') {
            $tplVars =& xarTpl__TemplateVariables::instance();
            $module = $tplVars->get('module');
            if (empty($module)) {
                xarExceptionSet(XAR_USER_EXCEPTION, 'MissingParameter',
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
 *
 * 
 * @package blocklayout
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
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingAttribute',
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
                    xarExceptionSet(XAR_USER_EXCEPTION, 'MissingAttribute',
                                   new xarTpl__ParserError('Missing \'module\' attribute in <xar:var> tag.', $this));
                    return;
                }
                return "xarModGetVar('".$module."', '".$name."')";
            case 'local':
                $name = xarTpl__ExpressionTransformer::transformPHPExpression($name);
                if (!isset($name)) {
                    return; // throw back
                }
                return $name;
            default:
                xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidAttribute',
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
 *
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
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingAttribute',
                           new xarTpl__ParserError('Missing \'name\' attribute in <xar:loop> tag.', $this));
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

        if (!isset($prefix)) {
            $output .= 'extract($_bl_loop_item'.$loopCounter.", EXTR_OVERWRITE); ";
        } else {
            $output .= 'extract($_bl_loop_item'.$loopCounter.", EXTR_PREFIX_ALL, '$prefix'); ";
        }

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
 *
 * 
 * @package blocklayout
 */
class xarTpl__XarSecNode extends xarTpl__TplTagNode
{
    function renderBeginTag()
    {
        extract($this->attributes);

        if (!isset($realm)) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingAttribute',
                           new xarTpl__ParserError('Missing \'realm\' attribute in <xar:sec> tag.', $this));
            return;
        }

        if (!isset($component)) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingAttribute',
                           new xarTpl__ParserError('Missing \'component\' attribute in <xar:sec> tag.', $this));
            return;
        }

        if (!isset($instance)) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingAttribute',
                           new xarTpl__ParserError('Missing \'instance\' attribute in <xar:sec> tag.', $this));
            return;
        }

        $levelNames = array('NONE', 'OVERVIEW', 'READ', 'COMMENT', 'MODERATE',
                            'EDIT', 'ADD', 'DELETE', 'ADMIN');
        if (!isset($level)) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingAttribute',
                           new xarTpl__ParserError('Missing \'level\' attribute in <xar:sec> tag.', $this));
            return;
        }
        if (!in_array($level, $levelNames)) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidAttribute',
                           new xarTpl__ParserError("Invalid value '$level' for 'level' attribute in <xar:sec> tag.", $this));
            return;
        }

        return "if (xarSecAuthAction($realm, '$component', '$instance', ACCESS_$level)) { ";
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
 *
 * 
 * @package blocklayout
 */
class xarTpl__XarTernaryNode extends xarTpl__TplTagNode
{
    function renderBeginTag()
    {
        extract($this->attributes);

        if (!isset($condition)) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingAttribute',
                           new xarTpl__ParserError('Missing \'condition\' attribute in <xar:ternary> tag.', $this));
            return;
        }

        if (count($this->children) != 3 || $this->children[1]->tagName != 'else') {
            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
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
 *
 * 
 * @package blocklayout
 */
class xarTpl__XarIfNode extends xarTpl__TplTagNode
{
    function renderBeginTag()
    {
        extract($this->attributes);

        if (!isset($condition)) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingAttribute',
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
 *
 * 
 * @package blocklayout
 */
class xarTpl__XarElseifNode extends xarTpl__TplTagNode
{
    function render()
    {
        extract($this->attributes);

        if (!isset($condition)) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingAttribute',
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
 *
 * 
 * @package blocklayout
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
                xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
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
 *
 * 
 * @package blocklayout
 */
class xarTpl__XarWhileNode extends xarTpl__TplTagNode
{
    function renderBeginTag()
    {
        extract($this->attributes);

        if (!isset($condition)) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingAttribute',
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
 *
 * 
 * @package blocklayout
 */
class xarTpl__XarForNode extends xarTpl__TplTagNode
{
    function renderBeginTag()
    {
        extract($this->attributes);

        if (!isset($start)) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingAttribute',
                           new xarTpl__ParserError('Missing \'start\' attribute in <xar:for> tag.', $this));
            return;
        }

        if (!isset($test)) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingAttribute',
                           new xarTpl__ParserError('Missing \'test\' attribute in <xar:for> tag.', $this));
            return;
        }

        if (!isset($iter)) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingAttribute',
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
 *
 * 
 * @package blocklayout
 */
class xarTpl__XarForEachNode extends xarTpl__TplTagNode
{
    function renderBeginTag()
    {
        extract($this->attributes);

        if (!isset($in)) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingAttribute',
                           new xarTpl__ParserError('Missing \'in\' attribute in <xar:foreach> tag.', $this));
            return;
        }

        if (!array($in)) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidAttribute',
                           new xarTpl__ParserError('Invalid \'in\' attribute in <xar:foreach> tag. \'in\' must be an array', $this));
            return;
        }

        if (!isset($value)) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingAttribute',
                           new xarTpl__ParserError('Missing \'value\' attribute in <xar:foreach> tag.', $this));
            return;
        }
        
        if (isset($key)) {
            return "foreach ($in as $key => $value) { ";
        }
        
        return "foreach ($in as $value) { ";
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
 *
 * 
 * @package blocklayout
 */
class xarTpl__XarBlockNode extends xarTpl__TplTagNode
{
    function renderBeginTag()
    {
        extract($this->attributes);

        if (!isset($name)) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingAttribute',
                           new xarTpl__ParserError('Missing \'name\' attribute in <xar:block> tag.', $this));
            return;
        }

        if (!isset($module)) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingAttribute',
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

        // Calculate block ID - theme dependent
        // FIXME: <marco> What is this for?
        $bid = md5(xarTplGetThemeName().$id);

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
 *
 * 
 * @package blocklayout
 *
 */
class xarTpl__XarBlockGroupNode extends xarTpl__TplTagNode
{
    function renderBeginTag()
    {
        extract($this->attributes);

        if (!isset($template)) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingAttribute',
                           new xarTpl__ParserError('Must have \'template\' attribute in open <xar:blockgroup> tag.', $this));
            return;
        }
        
        if (isset($name)) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
                       new xarTpl__ParserError('Cannot have \'name\' attribute in open <xar:blockgroup> tag.', $this));
            return;
        }
        
        return "\$_bl_blockgroup_template = 'a$template';";
    }
    
    function renderEndTag()
    {
        return 'unset($_bl_blockgroup_template);';
    }
    
    function render()
    {
        extract($this->attributes);

        if (!isset($name)) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingAttribute',
                           new xarTpl__ParserError('Missing \'name\' attribute in <xar:blockgroup> tag.', $this));
            return;
        }
        
        if (isset($template)) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
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
 *
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
            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
                           new xarTpl__ParserError('Missing mlkey and mlstring tags in <xar:ml> tag.', $this));
            return;
        }
        $mlNode = $this->children[0];
        if (!isset($mlNode)) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
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
                xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
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
 *
 * 
 * @package blocklayout
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
            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
                           new xarTpl__ParserError('Missing the key inside <xar:mlkey> tag.', $this));
            return;
        }
        if (count($this->attributes) != 0) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
                           new xarTpl__ParserError('The <xar:mlkey> tag takes no attributes.', $this));
            return;
        }
        // Children are only of text type
        foreach($this->children as $node) {
            $key .= $node->render();
        }
        $key = trim($key);
        if ($key == '') {
            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
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
 *
 * 
 * @package blocklayout
 */
class xarTpl__XarMlstringNode extends xarTpl__TplTagNode
{
    function render()
    {
        return $this->renderBeginTag() . $this->renderEndTag();
    }

    function renderBeginTag()
    {
        $string = '';
        if (count($this->children) == 0) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
                           new xarTpl__ParserError('Missing the string inside <xar:mlstring> tag.', $this));
            return;
        }
        if (count($this->attributes) != 0) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
                           new xarTpl__ParserError('The <xar:mlstring> tag takes no attributes.', $this));
            return;
        }
        // Children are only of text type
        foreach($this->children as $node) {
            $string .= $node->render();
        }
        $string = trim($string);
        if ($string == '') {
            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
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
 *
 * 
 * @package blocklayout
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
            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
                           new xarTpl__ParserError('The <xar:mlvar> tag can contain only one child tag.', $this));
            return;
        }
        if (count($this->attributes) != 0) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
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
 *
 * 
 * @package blocklayout
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
 *
 * 
 * @package blocklayout
 */
class xarTpl__XarModuleNode extends xarTpl__TplTagNode
{
    function render()
    {
        extract($this->attributes);

        if (!isset($main)) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingAttribute',
                           new xarTpl__ParserError('Missing \'main\' attribute in <xar:module> tag.', $this));
            return;
        }

        return '$_bl_mainModuleOutput';
    }
}

/**
 *
 * 
 * @package blocklayout
 */
class xarTpl__XarEventNode extends xarTpl__TplTagNode
{
    function render()
    {
        extract($this->attributes);

        if (!isset($name)) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingAttribute',
                           new xarTpl__ParserError('Missing \'name\' attribute in <xar:event> tag.', $this));
            return;
        }

        return "xarEvt_fire('$name')";
    }

    function isAssignable()
    {
        return false;
    }
}

/**
 *
 * 
 * @package blocklayout
 */
class xarTpl__XarIncludeNode extends xarTpl__TplTagNode
{
    function render()
    {
        extract($this->attributes);

        xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
                           new xarTpl__ParserError('The <xar:include> tag has been deprecated, you must use <xar:template>.', $this));
        return;

    }
}

/**
 *
 * 
 * @package blocklayout
 */
class xarTpl__XarTemplateNode extends xarTpl__TplTagNode
{
    function render()
    {
        extract($this->attributes);

        if (isset($file)) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidAttribute',
                           new xarTpl__ParserError('The \'file\' attribute has been deprecated, use \'name\' instead.', $this));
            return;
        }

        if (!isset($name)) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingAttribute',
                           new xarTpl__ParserError('Missing \'name\' attribute in <xar:template> tag.', $this));
            return;
        }

        if (!isset($type)) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingAttribute',
                           new xarTpl__ParserError('Missing \'type\' attribute in <xar:template> tag.', $this));
            return;
        }

        if (!isset($subdata)) {
            $subdata = '$_bl_data';
        }

        if ($type == 'theme') {
            return "xarTpl_includeThemeTemplate('$name', $subdata)";
        } elseif ($type == 'module') {
            return "xarTpl_includeModuleTemplate(\$_bl_module_name, '$name', $subdata)";
        } else {
            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidAttribute',
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
 *
 * 
 * @package blocklayout
 */
class xarTpl__XarSetNode extends xarTpl__TplTagNode
{
    function renderBeginTag()
    {
        extract($this->attributes);

        if (!isset($name)) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'MissingAttribute',
                           new xarTpl__ParserError('Missing \'name\' attribute in <xar:set> tag.', $this));
            return;
        }

        if (count($this->children) != 1) {
            xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
                           new xarTpl__ParserError('The <xar:set> tag can contain only one child tag.', $this));
            return;
        }

        return '$'.$name;
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
}

/**
 *
 * 
 * @package blocklayout
 * @todo FIXME: check if this is how we want to support module-registered tags
 */
class xarTpl__XarOtherNode extends xarTpl__TplTagNode
{
    function render()
    {
        $that = xarTplGetTagObjectFromName($this->tagName);
        if (!isset($that)) {
            return;
        }
        if (!xarTplCheckTagAttributes($this->tagName, $this->attributes)) return;
        // FIXME: we need the type somewhere in tag registration too
        xarModAPILoad($that->_module);
        $func = $that->_handler;
        if (!function_exists($func)) {
            xarModAPILoad($that->_module,'admin');
        }
        return $func($this->attributes);
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


/**
 * xarTpl__TplWidgetNode
 * hasChildren -> true
 * hasText -> false
 * isAssignable -> false
 * isPHPCode -> true
 * needAssignment -> false
 * needParameter -> false
 * needExceptionsControl -> false
 * @package blocklayout
 */
class xarTpl__TplWidgetNode extends xarTpl__TplTagNode
{
    function render()
    {
        return '';
    }

    function renderBeginTag()
    {
        return $this->render();
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
        return false;
    }


    function isAssignable()
    {
        return false;
    }

    function isPHPCode()
    {
        return true;
    }

    function getAttributesInfo()
    {
        static $attrs = array('id' => 'string');
        return $attrs;
    }

    function collectAttributes()
    {
        $attributes = array();
        foreach ($this->getAttributesInfo() as $attrName => $attrType) {
            switch ($attrType) {
                case 'string':
                    $attributes[$attrName] = "'" . $this->attributes[$attrName] . "'";
            }
        }
        foreach($this->children as $node) {
            if ($node->tagName == 'attribute') {
                // TODO
                $attributes[$node->attributes['name']] = $node->getValue();
            }
        }
        return $attributes;
    }

    function dumpArray($var, $depth = 0)
    {
        if ($depth > 32) { 
            return "'Recursive Depth Exceeded'";
        }

        $str = 'array(';

        foreach($var as $key => $value) {
            $str .= "'$key' => ";
            if (is_array($value)) {
                $str .= $this->dumpArray($value, $depth + 1) . ", ";
            } elseif (is_string($value)) {
                $str .= "'" . addslashes($value) . "', ";
            } elseif (is_numeric($value)) {
                $str .= $value . ", ";
            } elseif (is_null($value)) {
                $str .= 'NULL, ';
            } elseif (is_bool($value)) {
                $str .= ($value) ? 'true, ' : 'false, ';
            }
        }
        $str = substr($str, 0, -2);
        $str .= ')';

        return $str;
    }
}

/**
 *
 * 
 * @package blocklayout
 */
class xarTpl__WidgetAttribute extends xarTpl__TplWidgetNode
{
    function getValue()
    {
        if (isset($this->cachedValue)) {
            return $this->cachedValue;
        }

        if (isset($this->attributes['value'])) {
            $value = xarTpl__ExpressionTransformer::transformPHPExpression($this->attributes['value']);
            if (!isset($value)) {
                return; // throw back
            }
        } else {
            if (count($this->children) != 1) {
                xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
                               new xarTpl__ParserError('The <widget:attribute> tag can contain only one child tag.', $this));
                return;
            }

            $codeGenerator = new xarTpl__CodeGenerator();
            $codeGenerator->setPHPBlock(true);

            $value = $codeGenerator->generateNode($this->children[0]);
        }
        $this->cachedValue = $value;
        return $value;
    }

    function needParameter()
    {
        return true;
    }
}

/**
 *
 * 
 * @package blocklayout
 */
class xarTpl__WidgetModlink extends xarTpl__TplWidgetNode
{
    function getAttributesInfo()
    {
        $attributes = array('module' => 'string', 'type' => 'string', 'func' => 'string', 'label' => 'string');
        $attributes = array_merge(xarTpl__TplWidgetNode::getAttributesInfo(), $attributes);
        return $attributes;
    }

    function render()
    {
        $attributes = $this->collectAttributes();

        $modName = $attributes['module'];
        $modType = $attributes['type'];
        $funcName = $attributes['func'];
        $label = $attributes['label'];

        $args = array();
        foreach($this->children as $node) {
            if ($node->tagName == 'attribute') {
                continue;
            }
            if ($node->tagName != 'postfield') {
                xarExceptionSet(XAR_USER_EXCEPTION, 'InvalidTag',
                               new xarTpl__ParserError("The '".$this->tagName."' tag cannot have children of type '".$node->tagName."'.", $node));
                return;
            }
            // Node is xarTpl__WidgetPostfield
            $args = array_merge($args, $node->collectAttributes());
        }

        $output = "\$_bl_tplData = array('url' => xarModURL($modName, $modType, $funcName, " . $this->dumpArray($args) . "),".
                                        "'label' => '" . addslashes($label) . "',".
                                        "'attributes' => ''); ";
        $output .= "echo xarTpl_renderWidget('modlink', \$_bl_tplData);";
        //$output = "echo xarTpl_renderWidget('modlink', array());";
        return $output;
    }
}

/**
 *
 * 
 * @package blocklayout
 */
class xarTpl__WidgetPostfield extends xarTpl__TplWidgetNode
{
    function getAttributesInfo()
    {
        $attributes = array('name' => 'string', 'value' => 'BLExpression');
        $attributes = array_merge(xarTpl__TplWidgetNode::getAttributesInfo(), $attributes);
        return $attributes;
    }
}

?>
