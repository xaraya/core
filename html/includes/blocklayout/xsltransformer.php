<?php
/**
 * XSLT version of the BL compiler
 *
 * @package core
 * @copyright (C) 2006 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage xsl
 * @author Marcel van der Boom <marcel@xaraya.com>
**/

class BlocklayoutXSLTProcessor extends Object
{
    private $xslProc = NULL;
    private $xmlDoc  = NULL;
    private $xml ='';
    public  $xmlFile = NULL;
        
    function __construct($xml = '', $xslFile='') 
    {
        //  debug(htmlspecialchars($xml));
        // Set up the xsl processor
        $this->xslProc = new XSLTProcessor();
        $this->xslProc->registerPHPFunctions();

        // Set up the stylesheet
        $domDoc = new DOMDocument();
        $domDoc->load($xslFile);
        $this->xslProc->importStyleSheet($domDoc);
        
        // Set up the document to transform
        $this->xmlDoc = new DOMDocument();
        $this->xmlDoc->resolveExternals = true;
        $this->xmlDoc->loadXML($xml);
        $this->xml = $xml;
    }

    function transform()
    {
        // Set up the parameters
        if(isset($this->xmlFile)) {
            // Set up the parameters
            $this->xslProc->setParameter('','bl_filename',basename($this->xmlFile));
            $this->xslProc->setParameter('','bl_dirname',dirname($this->xmlFile));
        }
        
        // Transform it
        $result = $this->xslProc->transformToXML($this->xmlDoc);
        
        //debug(htmlspecialchars($result));
        return $result;
    }

    static function escape($var)
    {
        return str_replace("'","\'",$var);
    }
        
    static function phpexpression($expr)
    {
        return ExpressionTransformer::transformPHPExpression($expr);
    }
}
?>
