<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Marco Canini
// Purpose of file: Block Layout Template Engine Compiler
// ----------------------------------------------------------------------

class pnTpl__CompilerError extends DefaultUserException
{
    function pnTpl__CompilerError($msg)
    {
        $this->DefaultUserException($msg);
    }
}

class pnTpl__ParserError extends DefaultUserException
{
    function pnTpl__ParserError($msg, $posInfo)
    {
        $msg = 'Template error in file '.$posInfo->fileName.
               ' at line '.$posInfo->line.
               ', column '.$posInfo->column.
               ': '.$msg;
        $msg .= "\n" . $posInfo->lineText . "\n";
        if ($posInfo->column - 1 > 0) {
            $msg .= str_repeat('-', $posInfo->column - 1);
        }
        $msg .= '^';
        $this->DefaultUserException($msg);
    }
}

class pnTpl__PositionInfo
{
    var $fileName = '';
    var $line = 1;
    var $column = 1;
    var $lineText = '';
}

class pnTpl__Compiler
{
    var $parser;
    var $codeGenerator;

    function pnTpl__Compiler()
    {
        $this->parser = new pnTpl__Parser();
        $this->codeGenerator = new pnTpl__CodeGenerator();
    }

    function compileFile($fileName)
    {
        if (!($fp = @fopen($fileName, 'r'))) {
            pnExceptionSet(PN_USER_EXCEPTION, 'CompilerError',
                           new pnTpl__CompilerError("Cannot open template file '$fileName'."));
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

class pnTpl__CodeGenerator
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
        return $code;
    }

    function generateNode($node)
    {
        //pnLogMessage('generateNode', PNLOG_LEVEL_ERROR);
        if ($node->hasChildren() /*|| $node->hasText()*/) {
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
                //pnLogVariable('child', $child, PNLOG_LEVEL_ERROR);
                if ($checkNode->needAssignment() || $checkNode->needParameter()) {
                    if (!$child->isAssignable()) {
                        pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                                       new pnTpl__ParserError("The '".$checkNode->tagName."' tag cannot have children of type '".$child->tagName."'.", $child));
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
                    //pnLogVariable('checkNode', $checkNode, PNLOG_LEVEL_ERROR);
                    //pnLogMessage('here', PNLOG_LEVEL_ERROR);
                    $code .= ";\n";
                    if ($child->needExceptionsControl() || $this->isPendingExceptionsControl()) {
                        //pnLogMessage('exception control 1', PNLOG_LEVEL_ERROR);
                        $code .= "if (pnExceptionMajor() != PN_NO_EXCEPTION) return false;\n";
                        $this->setPendingExceptionsControl(false);
                    }
                } else {
                    //pnLogVariable('pass here', $child->tagName, PNLOG_LEVEL_ERROR);
                    if ($child->needExceptionsControl()) {
                        //pnLogVariable('pendingExceptionsControl', $child->tagName, PNLOG_LEVEL_ERROR);
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
                //pnLogVariable('final control', $node->tagName, PNLOG_LEVEL_ERROR);
                //pnLogVariable('final control', $node->needExceptionsControl(), PNLOG_LEVEL_ERROR); 
                $code .= "if (pnExceptionMajor() != PN_NO_EXCEPTION) return false;\n";
                $this->setPendingExceptionsControl(false);
            }
        } else {
            $code = $node->render();
            if (!isset($code)) {
                return; // throw back
            }
        }
        //pnLogMessage('exiting generateNode', PNLOG_LEVEL_ERROR);
        return $code;
    }
}

class pnTpl__Parser extends pnTpl__PositionInfo
{
    var $nodesFactory;

    var $tagNamesStack;
    var $tagIds;

    function pnTpl__Parser()
    {
        $this->nodesFactory = new pnTpl__NodesFactory(&$this);
    }

    function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

    function parse($templateSource)
    {
        //pnLogVariable('templateSource', $templateSource, PNLOG_LEVEL_ERROR);
        $this->templateSource = $templateSource;
        $this->line = 1;
        $this->column = 1;
        $this->pos = 0;
        $this->lineText = '';

        $this->tagNamesStack = array();
        $this->tagIds = array();

        $documentTree = $this->nodesFactory->createDocumentNode($this);

        $res = $this->parseNode($documentTree);
        if (!isset($res)) {
            return; // throw back
        }
        $documentTree->children = $res;
        //pnLogVariable('documentTree', $documentTree, PNLOG_LEVEL_ERROR);
        return $documentTree;
    }

    function parseNode($parent) {
        $children = array();
        $text = '';
        while (true) {
            //pnLogMessage('parseNode', PNLOG_LEVEL_ERROR);
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
                    // Check for header tag (<?pnt)
                    //
                    if ($nextToken == '?') {
                        $nextToken = $this->getNextToken(3);
                        if ($nextToken == 'pnt') {
                            // <?pnt header tag
                            // Handle Header Tag
                            $variables = $this->parseHeaderTag();
                            if (!isset($variables)) {
                                return; // throw back
                            }
                            $tplVars =& pnTpl__TemplateVariables::instance();
                            foreach ($variables as $name => $value) {
                                $tplVars->set($name, $value);
                            }
                            // Here we set token to an empty string so that $text .= $token will result in $text
                            $token = '';
                            break;
                        }
                        $this->stepBack(3);
                    //
                    // Check for pnt tag (<pnt:)
                    //
                    } elseif ($nextToken == 'p') {
                        $nextToken = $this->getNextToken(3);
                        if ($nextToken == 'nt:') {
                            // <pnt: tag
                            //pnLogMessage('found '.$nextToken, PNLOG_LEVEL_ERROR);
                            if (!$parent->hasChildren()) {
                                pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                                               new pnTpl__ParserError("The '".$parent->tagName."' tag cannot have children.", $parent));
                                return;
                            }
                            // Add text to parent
                            if (trim($text) != '') {
                                if (!$parent->hasText()) {
                                    pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                                                   new pnTpl__ParserError("The '".$parent->tagName."' tag cannot have text.", $parent));
                                    return;
                                }
                                $node = $this->nodesFactory->createTextNode($text, $this);
                                $children[] = $node;
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
                                    pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                                                   new pnTpl__ParserError("Not unique id in '".$tagName."' tag.", $this));
                                    return;
                                }
                                if ($attributes['id'] == '') {
                                    pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                                                   new pnTpl__ParserError("Empty id in '".$tagName."' tag.", $this));
                                    return;
                                }
                                $this->tagIds[$attributes['id']] = true;
                            }
                            $node = $this->nodesFactory->createTplTagNode($tagName, $attributes, $parent->tagName, $this);
                            if (!isset($node)) {
                                return; // throw back
                            }
                            //pnLogVariable('node', $node, PNLOG_LEVEL_ERROR);
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
                                pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                                               new pnTpl__ParserError("The '".$parent->tagName."' tag cannot have children.", $parent));
                                return;
                            }
                            // Add text to parent
                            if (trim($text) != '') {
                                if (!$parent->hasText()) {
                                    pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                                                   new pnTpl__ParserError("The '".$parent->tagName."' tag cannot have text.", $parent));
                                    return;
                                }
                                $node = $this->nodesFactory->createTextNode($text, $this);
                                $children[] = $node;
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
                                    pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                                                   new pnTpl__ParserError("Not unique id in '".$tagName."' tag.", $this));
                                    return;
                                }
                                if ($attributes['id'] == '') {
                                    pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                                                   new pnTpl__ParserError("Empty id in '".$tagName."' tag.", $this));
                                    return;
                                }
                                $this->tagIds[$attributes['id']] = true;
                            }
                            $node = $this->nodesFactory->createWidgetNode($tagName, $attributes, /*$parent->tagName,*/ $this);
                            if (!isset($node)) {
                                return; // throw back
                            }
                            //pnLogVariable('node', $node, PNLOG_LEVEL_ERROR);
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
                    // Check for pnt end tag (</pnt:)
                    //
                        if ($nextToken == 'p') {
                            $nextToken = $this->getNextToken(3);
                            if ($nextToken == 'nt:') {
                                // </pnt: tag
                                //pnLogMessage('found </pnt:', PNLOG_LEVEL_ERROR);
                                // Add text to parent
                                if (trim($text) != '') {
                                    if (!$parent->hasText()) {
                                        pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                                                       new pnTpl__ParserError("The '".$parent->tagName."' tag cannot have text.", $parent));
                                        return;
                                    }
                                    $node = $this->nodesFactory->createTextNode($text, $this);
                                    $children[] = $node;
                                    $text = '';
                                }
                                // Handle End Tag
                                $tagName = $this->parseEndTag();
                                if (!isset($tagName)) {
                                    return; // throw back
                                }
                                $stackTagName = array_pop($this->tagNamesStack);
                                if ($tagName != $stackTagName) {
                                    pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                                                   new pnTpl__ParserError("Found closed '$tagName' tag where close '$stackTagName' was expected.", $this));
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
                                if (trim($text) != '') {
                                    if (!$parent->hasText()) {
                                        pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                                                       new pnTpl__ParserError("The '".$parent->tagName."' tag cannot have text.", $parent));
                                        return;
                                    }
                                    $node = $this->nodesFactory->createTextNode($text, $this);
                                    $children[] = $node;
                                    $text = '';
                                }
                                // Handle End Tag
                                $tagName = $this->parseEndTag();
                                if (!isset($tagName)) {
                                    return; // throw back
                                }
                                $stackTagName = array_pop($this->tagNamesStack);
                                if ($tagName != $stackTagName) {
                                    pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                                                   new pnTpl__ParserError("Found closed '$tagName' tag where close '$stackTagName' was expected.", $this));
                                    return;
                                }
                                return $children;
                            }
                            $this->stepBack(6);
                        }
                        $this->stepBack(1);
                    }
                    $this->stepBack(1);
                    //pnLogVariable('token', $token, PNLOG_LEVEL_ERROR);
                    break;
                    //
                    // Check for pnt entity (&pnt-)
                    //
                case '&':
                    $nextToken = $this->getNextToken(4);
                    if ($nextToken == 'pnt-') {
                        if (!$parent->hasChildren()) {
                            pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                                           new pnTpl__ParserError("The '".$parent->tagName."' tag cannot have children.", $parent));
                            return;
                        }
                        // Add text to parent
                        if (trim($text) != '') {
                            if (!$parent->hasText()) {
                                pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                                               new pnTpl__ParserError("The '".$parent->tagName."' tag cannot have text.", $parent));
                                return;
                            }
                            $node = $this->nodesFactory->createTextNode($text, $this);
                            $children[] = $node;
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
            }
            $text .= $token;
            //pnLogVariable('text', $text, PNLOG_LEVEL_ERROR);
        }
        if ($text != '') {
            if (!$parent->hasText()) {
                pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                               new pnTpl__ParserError("The '".$parent->tagName."' tag cannot have text inside.", $parent));
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
            pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                           new pnTpl__ParserError("Invalid '$exitToken' character in header tag.", $this));
            return;
        }
        // Must parse the entire tag, we want to find > character
        while (true) {
            $token = $this->getNextToken();
            if (!isset($token)) {
                pnExceptionSet(PN_USER_EXCEPTION, 'InvalidFile',
                               new pnTpl__ParserError("Unexpected end of the file.", $this));
                return;
            }
            if ($token == '<') {
                pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                               new pnTpl__ParserError("Unclosed tag.", $this));
                return;
            }
            if ($token == '>') {
                break;
            }
        }
        return $variables;
    }

    function parseBeginTag() {
        //pnLogMessage('parseBeginTag', PNLOG_LEVEL_ERROR);
        // Tag name
        $tagName = '';
        while (true) {
            $token = $this->getNextToken();
            if (!isset($token)) {
                pnExceptionSet(PN_USER_EXCEPTION, 'InvalidFile',
                               new pnTpl__ParserError("Unexpected end of the file.", $this));
                return;
            }
            if ($token == '<') {
                pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                               new pnTpl__ParserError("Unclosed tag.", $this));
                return;
            }
            if ($token == ' ' || $token == '>' || $token == '/') {
                break;
            }
            $tagName .= $token;
        }
        if ($tagName == '') {
            pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                               new pnTpl__ParserError("Unnamed tag.", $this));
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
                    pnExceptionSet(PN_USER_EXCEPTION, 'InvalidFile',
                                   new pnTpl__ParserError("Unexpected end of the file.", $this));
                    return;
                }
                if ($token == '<') {
                    pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                                   new pnTpl__ParserError("Unclosed tag.", $this));
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
        //pnLogMessage('parseTagAttribute', PNLOG_LEVEL_ERROR);
        // Tag attribute
        $name = '';
        while (true) {
            $token = $this->getNextToken();
            if (!isset($token)) {
                pnExceptionSet(PN_USER_EXCEPTION, 'InvalidFile',
                               new pnTpl__ParserError("Unexpected end of the file.", $this));
                return;
            } elseif ($token == '"' || $token == "'") {
                $quote = $token;
                pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                               new pnTpl__ParserError("Invalid '$token' character in attribute name.", $this));
                return;
            } elseif ($token == '<') {
                pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                               new pnTpl__ParserError("Unclosed tag.", $this));
                return;
            } elseif ($token == '>' || $token == '/' || $token == '?') {
                if (trim($name) != '') {
                    pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                                   new pnTpl__ParserError("Invalid '$name' attribute.", $this));
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
            pnExceptionSet(PN_USER_EXCEPTION, 'InvalidAttribute',
                           new pnTpl__ParserError("Unnamed attribute.", $this));
            return;
        }
        $value = '';
        $quote = '';
        $ok = false;
        while (true) {
            $token = $this->getNextToken();
            if (!isset($token)) {
                pnExceptionSet(PN_USER_EXCEPTION, 'InvalidFile',
                               new pnTpl__ParserError("Unexpected end of the file.", $this));
                return;
            } elseif ($token == '>') {
                pnExceptionSet(PN_USER_EXCEPTION, 'InvalidAttribute',
                               new pnTpl__ParserError("Unclosed '$name' attribute.", $this));
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
        //pnLogMessage('parseEndTag', PNLOG_LEVEL_ERROR);
        // Tag name
        $tagName = '';
        while (true) {
            $token = $this->getNextToken();
            if (!isset($token)) {
                pnExceptionSet(PN_USER_EXCEPTION, 'InvalidFile',
                               new pnTpl__ParserError("Unexpected end of the file.", $this));
                return;
            } elseif ($token == '<') {
                pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                               new pnTpl__ParserError("Unclosed tag.", $this));
                return;
            } elseif ($token == '>') {
                break;
            }
            $tagName .= $token;
        }
        $tagName = rtrim($tagName);
        if ($tagName == '') {
            pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                           new pnTpl__ParserError("Unnamed tag.", $this));
            return;
        }
        return $tagName;
    }

    function parseEntity() {
        //pnLogMessage('parseEndTag', PNLOG_LEVEL_ERROR);
        // Entity type
        $entityType = '';
        while (true) {
            $token = $this->getNextToken();
            if (!isset($token)) {
                pnExceptionSet(PN_USER_EXCEPTION, 'InvalidFile',
                               new pnTpl__ParserError("Unexpected end of the file.", $this));
                return;
            } elseif ($token == '-' || $token == ';') {
                break;
            }
            $entityType .= $token;
        }
        if ($entityType == '') {
            pnExceptionSet(PN_USER_EXCEPTION, 'InvalidEntity',
                           new pnTpl__ParserError("Untyped entity.", $this));
            return;
        }
        $parameters = array();
        if ($token == '-') {
            $parameter = '';
            while (true) {
                $token = $this->getNextToken();
                if (!isset($token)) {
                    pnExceptionSet(PN_USER_EXCEPTION, 'InvalidFile',
                                   new pnTpl__ParserError("Unexpected end of the file.", $this));
                    return;
                } elseif ($token == ';') {
                    if ($parameter == '') {
                        pnExceptionSet(PN_USER_EXCEPTION, 'InvalidEntity',
                                       new pnTpl__ParserError("Empty parameter.", $this));
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
        //pnLogVariable('token', $token, PNLOG_LEVEL_ERROR);

		return $token;
    }

    function stepBack($len = 1)
    {
        $this->pos -= $len;
        $this->column -= $len;
    }
}

class pnTpl__NodesFactory
{

    function createTplTagNode($tagName, $attributes, $parentTagName, $parser)
    {
        // Core tags
        switch ($tagName) {
            case 'var':
                $node = new pnTpl__PntVarNode();
                break;
            case 'loop':
                $node = new pnTpl__PntLoopNode();
                break;
            case 'sec':
                $node = new pnTpl__PntSecNode();
                break;
            case 'ternary':
                $node = new pnTpl__PntTernaryNode();
                break;
            case 'if':
                $node = new pnTpl__PntIfNode();
                break;
            case 'elseif':
                $node = new pnTpl__PntElseifNode();
                break;
            case 'else':
                $node = new pnTpl__PntElseNode();
                break;
            case 'while':
                $node = new pnTpl__PntWhileNode();
                break;
            case 'for':
                $node = new pnTpl__PntForNode();
                break;
            case 'block':
                $node = new pnTpl__PntBlockNode();
                break;
            case 'blockgroup':
                $node = new pnTpl__PntBlockGroupNode();
                break;
            case 'ml':
                $node = new pnTpl__PntMlNode();
                break;
            case 'mlkey':
                $node = new pnTpl__PntMlkeyNode();
                break;
            case 'mlstring':
                $node = new pnTpl__PntMlstringNode();
                break;
            case 'mlvar':
                $node = new pnTpl__PntMlvarNode();
                break;
            case 'comment':
                $node = new pnTpl__PntCommentNode();
                break;
            case 'module':
                $node = new pnTpl__PntModuleNode();
                break;
            case 'event':
                $node = new pnTpl__PntEventNode();
                break;
            case 'include':
                $node = new pnTpl__PntIncludeNode();
                break;
            case 'template':
                $node = new pnTpl__PntTemplateNode();
                break;
            case 'set':
                $node = new pnTpl__PntSetNode();
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
// TODO: is pnTplRegisterTag still supposed to work for this ?
        //If we get here, the tag doesn't exist so we raise a user exception
        pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                       new pnTpl__ParserError("Cannot instantiate unexistent tag '$tagName'.", $parser));
        return;
    }

    function createTplEntityNode($entityType, $parameters, $parser)
    {
        switch ($entityType) {
            case 'var':
                $node = new pnTpl__PntVarEntityNode();
                break;
            case 'config':
                $node = new pnTpl__PntConfigEntityNode();
                break;
            case 'mod':
                $node = new pnTpl__PntModEntityNode();
                break;
            case 'session':
                $node = new pnTpl__PntSessionEntityNode();
                break;
            case 'modurl':
                $node = new pnTpl__PntModurlEntityNode();
                break;
            case 'url':
                $node = new pnTpl__PntUrlEntityNode();
                break;
            case 'baseurl':
                $node = new pnTpl__PntBaseurlEntityNode();
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
        pnExceptionSet(PN_USER_EXCEPTION, 'InvalidEntity',
                       new pnTpl__ParserError("Cannot instantiate unexistent entity '$entityType'.", $parser));
        return;
    }

    function createWidgetNode($widgetName, $attributes, $parser)
    {
        switch ($widgetName) {
            case 'modlink':
                $node = new pnTpl__WidgetModlink();
                break;
            case 'postfield':
                $node = new pnTpl__WidgetPostfield();
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
        pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                       new pnTpl__ParserError("Cannot instantiate unexistent widget '$widgetName'.", $parser));
        return;
    }

    function createTextNode($content, $parser)
    {
        $node = new pnTpl__TextNode();
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
        $node = new pnTpl__DocumentNode();
        $node->tagName = 'DocumentNode';
        $node->fileName = $parser->fileName;
        return $node;
    }
}

class pnTpl__SpecialVariableNamesResolver
{
    var $varsMapping = array();

    function &instance() {
        static $instance = NULL;
        if (!isset($instance)) {
            $instance = new pnTpl__SpecialVariableNamesResolver();
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
            pnExceptionSet(PN_USER_EXCEPTION, 'InvalidSpecialVariable',
                           new pnTpl__ParserError("Invalid use of '$specialVarName' special variable.", $posInfo));
            return;
        }
        return $this->varsMapping[$specialVarName][count($this->varsMapping[$specialVarName]) - 1];
    }
}

class pnTpl__TemplateVariables
{
    var $tplVars = array();

    function pnTpl__TemplateVariables()
    {
        // Fill defaults
        $this->tplVars['version'] = '1.0';
        $this->tplVars['encoding'] = 'us-ascii';
    }

    function &instance() {
        static $instance = NULL;
        if (!isset($instance)) {
            $instance = new pnTpl__TemplateVariables();
        }
        return $instance;
    }

    function get($name)
    {
        return $this->tplVars[$name];
    }

    function set($name, $value)
    {
        $this->tplVars[$name] = $value;
    }
}

class pnTpl__ExpressionTransformer
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

            // Get pnTpl__SpecialVariableNamesResolver instance
            $resolver =& pnTpl__SpecialVariableNamesResolver::instance();
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
            // Get pnTpl__SpecialVariableNamesResolver instance
            $resolver =& pnTpl__SpecialVariableNamesResolver::instance();
            for ($i = 0; $i < count($matches[0]); $i++) {
                $resolvedName = $resolver->resolve($matches[1][$i], $this);
                if (!isset($resolvedName)) {
                    return; // throw back
                }
                $phpExpression = str_replace($matches[0][$i], $resolvedName, $phpExpression);
            }
        }
        return $phpExpression;
    }
}

/*
 * pnTpl__Node
 * hasChildren -> false
 * hasText -> false
 * isAssignable -> true
 * isPHPCode -> false
 * needAssignment -> false
 * needParameter -> false
 * needExceptionsControl -> false
 */

class pnTpl__Node extends pnTpl__PositionInfo
{
    var $tagName;

    function render()
    {
        die('pnTpl__Node::render: abstract');
    }

    function renderBeginTag()
    {
        die('pnTpl__Node::renderBeginTag: abstract');
    }

    function renderEndTag()
    {
        die('pnTpl__Node::renderEndTag: abstract');
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

/*
 * pnTpl__DocumentNode
 * hasChildren -> true
 * hasText -> true
 * isAssignable -> false
 * isPHPCode -> false
 * needAssignment -> false
 * needParameter -> false
 * needExceptionsControl -> false
 */
class pnTpl__DocumentNode extends pnTpl__Node
{
    var $children;

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

/*
 * pnTpl__TextNode
 * hasChildren -> false
 * hasText -> false
 * isAssignable -> false
 * isPHPCode -> false
 * needAssignment -> false
 * needParameter -> false
 * needExceptionsControl -> false
 */
class pnTpl__TextNode extends pnTpl__Node
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

/*
 * pnTpl__EntityNode
 * hasChildren -> false
 * hasText -> false
 * isAssignable -> true
 * isPHPCode -> true
 * needAssignment -> false
 * needParameter -> false
 * needExceptionsControl -> false
 */
class pnTpl__EntityNode extends pnTpl__Node
{
    var $entityType;
    var $parameters;

    function isPHPCode()
    {
        return true;
    }
}

class pnTpl__PntVarEntityNode extends pnTpl__EntityNode
{
    function render()
    {
        if (count($this->parameters) != 1) {
            pnExceptionSet(PN_USER_EXCEPTION, 'MissingParameter',
                           new pnTpl__ParserError('Parameters mismatch in &pnt-var entity.', $this));
            return;
        }
        $name = pnTpl__ExpressionTransformer::transformBLExpression($this->parameters[0]);
        if (!isset($name)) {
            return; // throw back
        }

        return $name;
    }
}

class pnTpl__PntConfigEntityNode extends pnTpl__EntityNode
{
    function render()
    {
        if (count($this->parameters) != 1) {
            pnExceptionSet(PN_USER_EXCEPTION, 'MissingParameter',
                           new pnTpl__ParserError('Parameters mismatch in &pnt-config entity.', $this));
            return;
        }
        $name = $this->parameters[0];
        return "pnConfigGetVar('".$name."')";
    }

    function needExceptionsControl()
    {
        return true;
    }
}

class pnTpl__PntModEntityNode extends pnTpl__EntityNode
{
    function render()
    {
        if (count($this->parameters) != 2) {
            pnExceptionSet(PN_USER_EXCEPTION, 'MissingParameter',
                           new pnTpl__ParserError('Parameters mismatch in &pnt-mod entity.', $this));
            return;
        }
        $module = $this->parameters[0];
        $name = $this->parameters[1];
        return "pnModGetVar('".$module."', '".$name."')";
    }

    function needExceptionsControl()
    {
        return true;
    }
}

class pnTpl__PntSessionEntityNode extends pnTpl__EntityNode
{
    function render()
    {
        if (count($this->parameters) != 1) {
            pnExceptionSet(PN_USER_EXCEPTION, 'MissingParameter',
                           new pnTpl__ParserError('Parameters mismatch in &pnt-session entity.', $this));
            return;
        }
        $name = $this->parameters[0];
        return "pnSessionGetVar('".$name."')";
    }
}

class pnTpl__PntModurlEntityNode extends pnTpl__EntityNode
{
    function render()
    {
        if (count($this->parameters) != 3) {
            pnExceptionSet(PN_USER_EXCEPTION, 'MissingParameter',
                           new pnTpl__ParserError('Parameters mismatch in &pnt-modurl entity.', $this));
            return;
        }
        $module = $this->parameters[0];
        $type = $this->parameters[1];
        $func = $this->parameters[2];
        return "pnModURL('".$module."', '".$type."', '".$func."')";
    }
}

class pnTpl__PntUrlEntityNode extends pnTpl__EntityNode
{
    function render()
    {
        if (count($this->parameters) < 3) {
            pnExceptionSet(PN_USER_EXCEPTION, 'MissingParameter',
                           new pnTpl__ParserError('Parameters mismatch in &pnt-url entity.', $this));
            return;
        }
        $module = $this->parameters[0];
        if ($module == '') {
            $tplVars =& pnTpl__TemplateVariables::instance();
            $module = $tplVars->get('module');
            if (empty($module)) {
                pnExceptionSet(PN_USER_EXCEPTION, 'MissingParameter',
                               new pnTpl__ParserError('Empty module parameter in &pnt-url entity.', $this));
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
        return "pnModURL('$module', '$type', '$func'$args)";
    }
}

class pnTpl__PntBaseurlEntityNode extends pnTpl__EntityNode
{
    function render()
    {
        return "pnServerGetBaseURL()";
    }
}

/*
 * pnTpl__TplTagNode
 * hasChildren -> false
 * hasText -> false
 * isAssignable -> true
 * isPHPCode -> true
 * needAssignment -> false
 * needParameter -> false
 * needExceptionsControl -> false
 */
class pnTpl__TplTagNode extends pnTpl__Node
{
    var $attributes;
    var $parentTagName;
    var $children;

    function isPHPCode()
    {
        return true;
    }
}

class pnTpl__PntVarNode extends pnTpl__TplTagNode
{
    function render()
    {
        extract($this->attributes);

        if (!isset($name)) {
            pnExceptionSet(PN_USER_EXCEPTION, 'MissingAttribute',
                           new pnTpl__ParserError('Missing \'name\' attribute in <pnt:var> tag.', $this));
            return;
        }

        if (!isset($scope)) {
            $scope = 'local';
        }

        switch ($scope) {
            case 'config':
                return "pnConfigGetVar('".$name."')";
            case 'session':
                return "pnSessionGetVar('".$name."')";
            case 'module':
                if (!isset($module)) {
                    pnExceptionSet(PN_USER_EXCEPTION, 'MissingAttribute',
                                   new pnTpl__ParserError('Missing \'module\' attribute in <pnt:var> tag.', $this));
                    return;
                }
                return "pnModGetVar('".$module."', '".$name."')";
            case 'local':
                $name = pnTpl__ExpressionTransformer::transformBLExpression($name);
                if (!isset($name)) {
                    return; // throw back
                }
                return $name;
            default:
                pnExceptionSet(PN_USER_EXCEPTION, 'InvalidAttribute',
                               new pnTpl__ParserError('Invalid value for \'local\' attribute in <pnt:var> tag.', $this));
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

class pnTpl__PntLoopNode extends pnTpl__TplTagNode 
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
            pnExceptionSet(PN_USER_EXCEPTION, 'MissingAttribute',
                           new pnTpl__ParserError('Missing \'name\' attribute in <pnt:loop> tag.', $this));
            return;
        }
        $name = pnTpl__ExpressionTransformer::transformBLExpression($name);
        if (!isset($name)) {
            return; // throw back
        }

        // Increment the loopCounter and retrieve its new value
        $loopCounter = pnTpl__PntLoopNode::loopCounter('++');
        // Get pnTpl__SpecialVariableNamesResolver instance
        $resolver =& pnTpl__SpecialVariableNamesResolver::instance();
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

        $output = '$_bl_loop_index'.$loopCounter." = 0;\n";
        $output .= '$_bl_loop_number'.$loopCounter." = 1;\n";
        $output .= 'foreach ('.$name.' as $_bl_loop_key'.$loopCounter.' => $_bl_loop_item'.$loopCounter.") {\n";

        if (!isset($prefix)) {
            $output .= 'extract($_bl_loop_item'.$loopCounter.", EXTR_OVERWRITE);\n";
        } else {
            $output .= 'extract($_bl_loop_item'.$loopCounter.", EXTR_PREFIX_ALL, '$prefix');\n";
        }

        return $output;
    }

    function renderEndTag()
    {
        // Decrement the loopCounter
        // $loopCounter is the new value + 1
        $loopCounter = pnTpl__PntLoopNode::loopCounter('--') + 1;

        // Get pnTpl__SpecialVariableNamesResolver instance
        $resolver =& pnTpl__SpecialVariableNamesResolver::instance();
        // Register special variables
        $resolver->pop('loop:item');
        $resolver->pop('loop:key');
        $resolver->pop('loop:index');
        $resolver->pop('loop:number');

        $output = '$_bl_loop_index'.$loopCounter."++;\n";
        $output .= '$_bl_loop_number'.$loopCounter."++;\n";
        $output .= "}\n";
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

class pnTpl__PntSecNode extends pnTpl__TplTagNode
{
    function renderBeginTag()
    {
        extract($this->attributes);

        if (!isset($realm)) {
            pnExceptionSet(PN_USER_EXCEPTION, 'MissingAttribute',
                           new pnTpl__ParserError('Missing \'realm\' attribute in <pnt:sec> tag.', $this));
            return;
        }

        if (!isset($component)) {
            pnExceptionSet(PN_USER_EXCEPTION, 'MissingAttribute',
                           new pnTpl__ParserError('Missing \'component\' attribute in <pnt:sec> tag.', $this));
            return;
        }

        if (!isset($instance)) {
            pnExceptionSet(PN_USER_EXCEPTION, 'MissingAttribute',
                           new pnTpl__ParserError('Missing \'instance\' attribute in <pnt:sec> tag.', $this));
            return;
        }

        $levelNames = array('NONE', 'OVERVIEW', 'READ', 'COMMENT', 'MODERATE',
                            'EDIT', 'ADD', 'DELETE', 'ADMIN');
        if (!isset($level)) {
            pnExceptionSet(PN_USER_EXCEPTION, 'MissingAttribute',
                           new pnTpl__ParserError('Missing \'level\' attribute in <pnt:sec> tag.', $this));
            return;
        }
        if (!in_array($level, $levelNames)) {
            pnExceptionSet(PN_USER_EXCEPTION, 'InvalidAttribute',
                           new pnTpl__ParserError("Invalid value '$level' for 'level' attribute in <pnt:sec> tag.", $this));
            return;
        }

        return "if (pnSecAuthAction($realm, '$component', '$instance', ACCESS_$level)) {\n";
    }

    function renderEndTag()
    {
        return "}\n";
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

class pnTpl__PntTernaryNode extends pnTpl__TplTagNode
{
    function renderBeginTag()
    {
        extract($this->attributes);

        if (!isset($condition)) {
            pnExceptionSet(PN_USER_EXCEPTION, 'MissingAttribute',
                           new pnTpl__ParserError('Missing \'condition\' attribute in <pnt:ternary> tag.', $this));
            return;
        }

        if (count($this->children) != 3 || $this->children[1]->tagName != 'else') {
            pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                           new pnTpl__ParserError('Missing subexpressions or \'else\' tag in <pnt:ternary> tag.', $this));
            return;
        }

        $condition = pnTpl__ExpressionTransformer::transformPHPExpression($condition);
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

class pnTpl__PntIfNode extends pnTpl__TplTagNode
{
    function renderBeginTag()
    {
        extract($this->attributes);

        if (!isset($condition)) {
            pnExceptionSet(PN_USER_EXCEPTION, 'MissingAttribute',
                           new pnTpl__ParserError('Missing \'condition\' attribute in <pnt:if> tag.', $this));
            return;
        }

        $condition = pnTpl__ExpressionTransformer::transformPHPExpression($condition);
        if (!isset($condition)) {
            return; // throw back
        }

        $findLogic      = array(' eq ', ' neq ', ' lt ', ' gt ', ' id ', ' nid ', ' lte ', ' gte ');
        $replaceLogic   = array(' == ', ' != ',  ' < ',  ' > ', ' === ', ' !== ', ' <= ', ' >= ');
        $condition = str_replace($findLogic, $replaceLogic, $condition);
        
        return "if ($condition) {\n";
    }

    function renderEndTag()
    {
        return "}\n";
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

class pnTpl__PntElseifNode extends pnTpl__TplTagNode
{
    function render()
    {
        extract($this->attributes);

        if (!isset($condition)) {
            pnExceptionSet(PN_USER_EXCEPTION, 'MissingAttribute',
                           new pnTpl__ParserError('Missing \'condition\' attribute in <pnt:elseif> tag.', $this));
            return;
        }

        $condition = pnTpl__ExpressionTransformer::transformPHPExpression($condition);
        if (!isset($condition)) {
            return; // throw back
        }
        
        $findLogic      = array(' eq ', ' neq ', ' lt ', ' gt ', ' id ', ' nid ', ' lte ', ' gte ');
        $replaceLogic   = array(' == ', ' != ',  ' < ',  ' > ', ' === ', ' !== ', ' <= ', ' >= ');
        $condition = str_replace($findLogic, $replaceLogic, $condition);
        
        return "} elseif ($condition) {\n";
    }

    function isAssignable()
    {
        return false;
    }
}

class pnTpl__PntElseNode extends pnTpl__TplTagNode
{
    function render()
    {
        switch ($this->parentTagName) {
            case 'if':
            case 'sec':
                $output = "} else {\n";
                break;
            case 'ternary':
                $output = " : ";
                break;
            default:
                pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                           new pnTpl__ParserError("The <pnt:else> tag cannot be placed under '".$this->parentTagName."' tag.", $this));
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

class pnTpl__PntWhileNode extends pnTpl__TplTagNode
{
    function renderBeginTag()
    {
        extract($this->attributes);

        if (!isset($condition)) {
            pnExceptionSet(PN_USER_EXCEPTION, 'MissingAttribute',
                           new pnTpl__ParserError('Missing \'condition\' attribute in <pnt:while> tag.', $this));
            return;
        }

        $condition = pnTpl__ExpressionTransformer::transformPHPExpression($condition);
        if (!isset($condition)) {
            return; // throw back
        }
        
        $findLogic      = array(' eq ', ' neq ', ' lt ', ' gt ', ' id ', ' nid ', ' lte ', ' gte ');
        $replaceLogic   = array(' == ', ' != ',  ' < ',  ' > ', ' === ', ' !== ', ' <= ', ' >= ');
        $condition = str_replace($findLogic, $replaceLogic, $condition);

        return "while ($condition) {\n";
    }

    function renderEndTag()
    {
        return "}\n";
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

class pnTpl__PntForNode extends pnTpl__TplTagNode
{
    function renderBeginTag()
    {
        extract($this->attributes);

        if (!isset($start)) {
            pnExceptionSet(PN_USER_EXCEPTION, 'MissingAttribute',
                           new pnTpl__ParserError('Missing \'start\' attribute in <pnt:for> tag.', $this));
            return;
        }

        if (!isset($test)) {
            pnExceptionSet(PN_USER_EXCEPTION, 'MissingAttribute',
                           new pnTpl__ParserError('Missing \'test\' attribute in <pnt:for> tag.', $this));
            return;
        }

        if (!isset($iter)) {
            pnExceptionSet(PN_USER_EXCEPTION, 'MissingAttribute',
                           new pnTpl__ParserError('Missing \'iter\' attribute in <pnt:for> tag.', $this));
            return;
        }

        $start = pnTpl__ExpressionTransformer::transformPHPExpression($start);
        if (!isset($start)) {
            return; // throw back
        }
        $test = pnTpl__ExpressionTransformer::transformPHPExpression($test);
        if (!isset($test)) {
            return; // throw back
        }
        $iter = pnTpl__ExpressionTransformer::transformPHPExpression($iter);
        if (!isset($iter)) {
            return; // throw back
        }
        
        $findLogic      = array(' eq ', ' neq ', ' lt ', ' gt ', ' id ', ' nid ', ' lte ', ' gte ');
        $replaceLogic   = array(' == ', ' != ',  ' < ',  ' > ', ' === ', ' !== ', ' <= ', ' >= ');
        $test = str_replace($findLogic, $replaceLogic, $test);
        
        return "for ($start; $test; $iter) {\n";
    }

    function renderEndTag()
    {
        return "}\n";
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

class pnTpl__PntBlockNode extends pnTpl__TplTagNode
{
    function renderBeginTag()
    {
    	extract($this->attributes);

        if (!isset($name)) {
            pnExceptionSet(PN_USER_EXCEPTION, 'MissingAttribute',
                           new pnTpl__ParserError('Missing \'name\' attribute in <pnt:block> tag.', $this));
            return;
        }

        if (!isset($module)) {
            pnExceptionSet(PN_USER_EXCEPTION, 'MissingAttribute',
                           new pnTpl__ParserError('Missing \'module\' attribute in <pnt:block> tag.', $this));
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
        $bid = md5(pnUserGetTheme().$id);
		
        if (isset($this->children) && count($this->children) > 0) {
            $contentNode = $this->children[0];
            if (isset($contentNode)) {
                $content = trim(addslashes($contentNode->render()));
            }
        }

        $this->children = array();


        return "pnBlock_render(array('module' => '$module', 'type' => '$name', 'bid' => '$bid',
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

class pnTpl__PntBlockGroupNode extends pnTpl__TplTagNode
{
    function render()
    {
        extract($this->attributes);

        if (!isset($name)) {
            pnExceptionSet(PN_USER_EXCEPTION, 'MissingAttribute',
                           new pnTpl__ParserError('Missing \'name\' attribute in <pnt:blockgroup> tag.', $this));
            return;
        }

        return "pnBlock_renderGroup('$name')";
    }

    function needExceptionsControl()
    {
        return true;
    }
}

class pnTpl__PntMlNode extends pnTpl__TplTagNode
{
    function renderBeginTag()
    {
        if (isset($this->cachedOutput)) {
            return $this->cachedOutput;
        }

        if (count($this->children) == 0 ||
           ($this->children[0]->tagName != 'mlkey' &&
            $this->children[0]->tagName != 'mlstring')) {
            pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                           new pnTpl__ParserError('Missing mlkey and mlstring tags in <pnt:ml> tag.', $this));
            return;
        }
        $mlNode = $this->children[0];
        if (!isset($mlNode)) {
            pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                           new pnTpl__ParserError('Missing \'mlkey\' and \'mlstring\' tags in <pnt:ml> tag.', $this));
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
                pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                               new pnTpl__ParserError("The '".$this->tagName."' tag cannot have children of type '".$node->tagName."'.", $node));
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

class pnTpl__PntMlkeyNode extends pnTpl__TplTagNode
{
    function render()
    {
        return $this->renderBeginTag() . $this->renderEndTag();
    }

    function renderBeginTag()
    {
        $key = '';
        // Children are only of text type
        foreach($this->children as $node) {
            $key .= $node->render();
        }
        $key = trim($key);
        if ($key == '') {
            pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                           new pnTpl__ParserError('Missing content in <pnt:mlkey> tag.', $this));
            return;
        }
        return "pnMLByKey('$key'";
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

class pnTpl__PntMlstringNode extends pnTpl__TplTagNode
{
    function render()
    {
        return $this->renderBeginTag() . $this->renderEndTag();
    }

    function renderBeginTag()
    {
        $string = '';
        // Children are only of text type
        foreach($this->children as $node) {
            $string .= $node->render();
        }
        $string = trim($string);
        if ($string == '') {
            pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                           new pnTpl__ParserError('Missing content in <pnt:mlstring> tag.', $this));
            return;
        }
        return "pnML(\"".addslashes($string)."\"";
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

class pnTpl__PntMlvarNode extends pnTpl__TplTagNode
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

        if (count($this->children) > 1) {
            pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                           new pnTpl__ParserError('The <pnt:mlvar> tag can contain only one child tag.', $this));
            return;
        }

        $codeGenerator = new pnTpl__CodeGenerator();
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

class pnTpl__PntCommentNode extends pnTpl__TplTagNode
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

class pnTpl__PntModuleNode extends pnTpl__TplTagNode
{
    function render()
    {
        extract($this->attributes);

        if (!isset($main)) {
            pnExceptionSet(PN_USER_EXCEPTION, 'MissingAttribute',
                           new pnTpl__ParserError('Missing \'main\' attribute in <pnt:module> tag.', $this));
            return;
        }

        return '$_bl_mainModuleOutput';
    }
}

class pnTpl__PntEventNode extends pnTpl__TplTagNode
{
    function render()
    {
        extract($this->attributes);

        if (!isset($name)) {
            pnExceptionSet(PN_USER_EXCEPTION, 'MissingAttribute',
                           new pnTpl__ParserError('Missing \'name\' attribute in <pnt:event> tag.', $this));
            return;
        }

        return "pnEvt_fire('$name')";
    }

    function isAssignable()
    {
        return false;
    }
}

class pnTpl__PntIncludeNode extends pnTpl__TplTagNode
{
    function render()
    {
        extract($this->attributes);
        
        if (!isset($file)) {
            pnExceptionSet(PN_USER_EXCEPTION, 'MissingAttribute',
                           new pnTpl__ParserError('Missing \'file\' attribute in <pnt:include> tag.', $this));
            return;
        }
        
        if (!isset($type)) {
            pnExceptionSet(PN_USER_EXCEPTION, 'MissingAttribute',
                           new pnTpl__ParserError('Missing \'type\' attribute in <pnt:include> tag.', $this));
            return;
        }
        
        $themeName = pnCore_getSiteVar('BL.Theme.Name');
        $directories = array();
        if ($type == 'theme') {
            $directories[] = "themes/$themeName/includes/";
        } elseif ($type == 'module') {
            $directories[] = "themes/$themeName/modules/$_bl_module_name/includes/";
            $directories[] = "modules/$_bl_module_name/pninclude/";
        } else {
            pnExceptionSet(PN_USER_EXCEPTION, 'InvalidAttribute',
                           new pnTpl__ParserError("Invalid value '$type' for 'type' attribute in <pnt:include> tag.", $this));
            return;
        }

        $path = implode('; ', $directories);
        if (strstr($file, '..') != false) {
            pnExceptionSet(PN_USER_EXCEPTION, 'InvalidAttribute',
                           new pnTpl__ParserError("File '$file' may not be located outside search path. (Path: '$path')", $this));
            return;
        }
        
        foreach ($directories as $directory) {
            if (file_exists($directory . '/' . $file)) {
                return "include ('$directory/$file');\n";
            }
        }
        
        pnExceptionSet(PN_USER_EXCEPTION, 'InvalidAttribute',
                       new pnTpl__ParserError("File '$file' not found. (Search path: '$path')", $this));
    }
}

class pnTpl__PntTemplateNode extends pnTpl__TplTagNode
{
    function render()
    {
        extract($this->attributes);
        
        if (!isset($file)) {
            pnExceptionSet(PN_USER_EXCEPTION, 'MissingAttribute',
                           new pnTpl__ParserError('Missing \'file\' attribute in <pnt:include> tag.', $this));
            return;
        }
        
        if (!isset($type)) {
            pnExceptionSet(PN_USER_EXCEPTION, 'MissingAttribute',
                           new pnTpl__ParserError('Missing \'type\' attribute in <pnt:include> tag.', $this));
            return;
        }
        
        $themeName = pnCore_getSiteVar('BL.Theme.Name');
        $directories = array();
        if ($type == 'theme') {
            $directories[] = "themes/$themeName/includes/";
        } elseif ($type == 'module') {
            $directories[] = "themes/$themeName/modules/$_bl_module_name/includes/";
            $directories[] = "modules/$_bl_module_name/pninclude/";
        } else {
            pnExceptionSet(PN_USER_EXCEPTION, 'InvalidAttribute',
                           new pnTpl__ParserError("Invalid value '$type' for 'type' attribute in <pnt:include> tag.", $this));
            return;
        }

        $path = implode('; ', $directories);
        if (strstr($file, '..') != false) {
            pnExceptionSet(PN_USER_EXCEPTION, 'InvalidAttribute',
                           new pnTpl__ParserError("File '$file' may not be located outside search path. (Path: '$path')", $this));
            return;
        }
        
        foreach ($directories as $directory) {
            if (file_exists($directory . '/' . $file)) {
                return "pnTplFile('$directory/$file', \$_bl_data)\n";
            }
        }
        
        pnExceptionSet(PN_USER_EXCEPTION, 'InvalidAttribute',
                       new pnTpl__ParserError("File '$file' not found. (Search path: '$path')", $this));
    }
}

class pnTpl__PntSetNode extends pnTpl__TplTagNode
{
    function renderBeginTag()
    {
        extract($this->attributes);

        if (!isset($name)) {
            pnExceptionSet(PN_USER_EXCEPTION, 'MissingAttribute',
                           new pnTpl__ParserError('Missing \'name\' attribute in <pnt:set> tag.', $this));
            return;
        }

        if (count($this->children) > 1) {
            pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                           new pnTpl__ParserError('The <pnt:set> tag can contain only one child tag.', $this));
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

/*
 * pnTpl__TplWidgetNode
 * hasChildren -> true
 * hasText -> false
 * isAssignable -> false
 * isPHPCode -> true
 * needAssignment -> false
 * needParameter -> false
 * needExceptionsControl -> false
 */
class pnTpl__TplWidgetNode extends pnTpl__TplTagNode
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

class pnTpl__WidgetAttribute extends pnTpl__TplWidgetNode
{
    function getValue()
    {
        if (isset($this->cachedValue)) {
            return $this->cachedValue;
        }

        if (isset($this->attributes['value'])) {
            $value = pnTpl__ExpressionTransformer::transformPHPExpression($this->attributes['value']);
            if (!isset($value)) {
                return; // throw back
            }
        } else {
            if (count($this->children) > 1) {
                pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                               new pnTpl__ParserError('The <widget:attribute> tag can contain only one child tag.', $this));
                return;
            }

            $codeGenerator = new pnTpl__CodeGenerator();
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

class pnTpl__WidgetModlink extends pnTpl__TplWidgetNode
{
    function getAttributesInfo()
    {
        $attributes = array('module' => 'string', 'type' => 'string', 'func' => 'string', 'label' => 'string');
        $attributes = array_merge(pnTpl__TplWidgetNode::getAttributesInfo(), $attributes);
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
                pnExceptionSet(PN_USER_EXCEPTION, 'InvalidTag',
                               new pnTpl__ParserError("The '".$this->tagName."' tag cannot have children of type '".$node->tagName."'.", $node));
                return;
            }
            // Node is pnTpl__WidgetPostfield
            $args = array_merge($args, $node->collectAttributes());
        }

        $output = "\$_bl_tplData = array('url' => pnModURL($modName, $modType, $funcName, " . $this->dumpArray($args) . "),".
                                        "'label' => '" . addslashes($label) . "',".
                                        "'attributes' => '');\n";
        $output .= "echo pnTpl_renderWidget('modlink', \$_bl_tplData);";
        //$output = "echo pnTpl_renderWidget('modlink', array());";
        return $output;
    }
}

class pnTpl__WidgetPostfield extends pnTpl__TplWidgetNode
{
    function getAttributesInfo()
    {
        $attributes = array('name' => 'string', 'value' => 'BLExpression');
        $attributes = array_merge(pnTpl__TplWidgetNode::getAttributesInfo(), $attributes);
        return $attributes;
    }
}

?>
