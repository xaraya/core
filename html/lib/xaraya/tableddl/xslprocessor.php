<?php

class XarayaXSLProcessor extends Object
{
    private $xslProc = null;    // Object representing the processor.
    private $xslDoc  = null;    // Object representing the stylesheet.
    private $xmlDoc  = null;    // Object representing the input XML.

    public function __construct($xslFile)
    {
        // Set up the xsl processor
        $this->xslProc = new XSLTProcessor();
        $this->xslProc->registerPHPFunctions();

        // Set up the stylesheet
        set_exception_handler(array('ExceptionHandlers','bone'));
        $this->setStyleSheet($xslFile);

        // Set up the document to transform
        $this->xmlDoc = new DOMDocument();
        // Setting this to false makes it 2 times faster, what do we lose?
        $this->xmlDoc->resolveExternals = false;
        // We're still a long way from validating
        // $this->xmlDoc->validateOnParse = true;
    }

    // This will become public once we have more pipes
    private function setStyleSheet($xslFile)
    {
        $this->xslDoc = new DOMDocument();
        $this->xslDoc->load($xslFile);
        $this->xslProc->importStyleSheet($this->xslDoc);

    }

    private function setSourceFile(&$xml)
    {
        $this->xmlDoc = new DOMDocument();
        // Setting this to false makes it 2 times faster, what do we lose?
        $this->xmlDoc->resolveExternals = false;
        // We're still a long way from validating
        //$this->xmlDoc->validateOnParse = true;
        $this->xmlDoc->load($xml);
    }

    public function transform(&$xml)
    {
        // Set the source document to what we prepped
        $this->setSourceFile($xml);

        // Transform it
        set_exception_handler(array('ExceptionHandlers','defaulthandler'));
        // What should we initialize $result to?
        $this->postXML = $this->xslProc->transformToXML($this->xmlDoc);
        return $this->postXML;
    }

    static function phpexpression($expr)
    {
        $res = ExpressionTransformer::transformPHPExpression($expr);
        xarLogMessage("BL: '$expr' resolved to '$res'");
        return $res;
    }

    public function setParameter($namespace, $name, $value)
    {
        return $this->xslProc->setParameter($namespace, $name, $value);
    }
}

?>
