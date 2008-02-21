<?php
/**
 * XSLT version of the BL compiler
 *
 * @package core
 * @copyright 2007 The Digital Development Foundation.
 * @link http://www.xaraya.com
 *
 * @subpackage xsl
 * @author Marcel van der Boom <marcel@xaraya.com>
**/

sys::import('blocklayout.compiler'); // expression transformer
class BlocklayoutXSLTProcessor extends Object
{
    private $xslProc = null;    // Object representing the processor.
    private $xslDoc  = null;    // Object representing the stylesheet.
    private $xmlDoc  = null;    // Object representing the input XML.

    private $origXml = '';      // The original XML
    private $prepXml = '';      // The preprocessed XML
    private $postXml = '';      // The transformed result XML

    public  $xmlFile = null;

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

    private function setSourceDocument(&$xml)
    {
        $this->xmlDoc = new DOMDocument();
        // Setting this to false makes it 2 times faster, what do we loose?
        $this->xmlDoc->resolveExternals = false;
        // We're still a long way from validating
        // $this->xmlDoc->validateOnParse = true;
        $file = isset($this->xmlFile) ? $this->xmlFile : 'unknown';
        $this->xmlDoc->loadXML($xml);

        // Set up additional parameters related to the input
        // @todo wrong here.
        if(isset($this->xmlFile)) {
            // Set up the parameters
            $this->xslProc->setParameter('','bl_filename',basename($this->xmlFile));
            $this->xslProc->setParameter('','bl_dirname',dirname($this->xmlFile));
        }

    }

    private function preProcess()
    {
        // Make sure our entities look like expressions
        // &xar-entity; -> #[whatever expression it needs]#
        $this->prepXml = $this->origXml;
        $entityPattern = '/(&xar-[a-z\-_]+?;)/';
        $callBack      = array('XsltCallbacks','entities');
        $this->prepXml = preg_replace_callback($entityPattern,$callBack,$this->prepXml);

        // Make sure ML placeholders look like expressions
        // #(1)... -> #(1)#...
        $mlsPattern     = '/(#\([0-9]+\))([^#])/';
        $callBack       = array('XsltCallbacks','mlsplaceholders');
        $this->prepXml  = preg_replace_callback($mlsPattern, $callBack, $this->prepXml);
    }

    public function transform(&$xml)
    {
        // Save the original XML
        $this->origXml = $xml;

        // Preprocess it.
        $this->preProcess();

        // Set the source document to what we prepped
        $this->setSourceDocument($this->prepXml);

        // Transform it
        set_exception_handler(array('ExceptionHandlers','defaulthandler'));
        // What should we initialize $result to?
        $this->postXML = $this->xslProc->transformToXML($this->xmlDoc);

        // Postprocess it
        $this->postProcess();
        return $this->postXML;
    }

    private function postProcess()
    {
        /*
            Expressions in attributes are not handled by the transform because
            XSLT can not generate anything other than valid XML which means
            processing instruction inside attribute values are impossible.

            This pattern should not greedy match the dots in #...# constructs
            *only* in attributes.
            We exclude between the #s:
                " == delimiter of attributes (text nodes are xslt transformed)
                # == our own delimiter (the ? takes care of this)
                < == tag delimiter (expression has to stay within a text node)
                > == tag delimiter (expression has to stay within a start tag (attribute))

            TODO:
                This just shifts the problem to where an expression contains a
                literal string
                title="#SomeFunc('I dont like this, it is problem #5')#"
                The # will create a problem currently.

        */
        $exprPattern = '/(#[^"><]*?#)/';
        $callBack    = array('XsltCallbacks','attributes');
        $this->postXML = preg_replace_callback($exprPattern,$callBack,$this->postXML);
    }

    static function phpexpression($expr)
    {
        $res = ExpressionTransformer::transformPHPExpression($expr);
        xarLogMessage("BL: '$expr' resolved to '$res'");
        return $res;
    }
}

class XsltCallbacks extends Object
{
    static function mlsplaceholders($matches)
    {
        $res = $matches[1].'#'.$matches[2];
        //xarLogMessage('MLS: ' . $matches[0] . ' => '.$res);
        return $res;
    }

    static function attributes($matches)
    {
        // Resolve the parts between the #-es, but leave MLS stuff alone.
        if(preg_match('/#\([0-9]+(\))#?/',$matches[0])) return $matches[0];
        if($matches[0] == '##') return '#';
        $raw = ExpressionTransformer::transformPHPExpression($matches[1]);
        $raw = self::reverseXMLEntities($raw);
        // Return the first match too, to ensure not changing the input
        $res = '<?php echo ' . $raw .';?>';
        xarLogMessage('ATT: '. $matches[0] . ' => ' . $res);
        return $res;
    }

    private static function reverseXMLEntities($content)
    {
        /*
            XML predefines 5 entities and as we resolve our attribute
            expressions to php code, we need a way to make php happy bout
            them too. This touches obviously on the problem of expressions
            in attributes in general.
        */
        return str_replace(
            array('&amp;', '&gt;', '&lt;', '&quot;','&apos;'),
            array('&', '>', '<', '"',"'"),
            $content
        );
    }

    /*
        Entity resolvement callback for xar- entities.
    */
    static function entities($matches)
    {
        // Strip the & and the ; off.
        $entityName  = substr($matches[0],1,-1);
        $entityParts = explode('-',$entityName);

        // The first part will always be xar, if not, return the whole entity back
        if($entityParts[0] != 'xar' or !isset($entityParts[1]))
            return $matches[0];

        // The second part signals what we need to do
        switch($entityParts[1])
        {
            // &xar-baseurl;
            case 'baseurl':
                return '#xarServer::getBaseURL()#';
            // &xar-modurl-modname-type-func;
            case 'modurl':
                //   1       2     3    4
                // modurl-modname-type-func
                if( isset($entityParts[2]) and
                    isset($entityParts[3]) and
                    isset($entityParts[4])
                ) return "#xarModUrl('$entityParts[2]','$entityParts[3]','$entityParts[4]')#";
                break;
            // &xar-var;
            case 'var':
                return "#\$$entityParts[2]#";
            // &xar-currenturl;
            case 'currenturl':
                return '#xarServer::getCurrentURL()#';
            // Not implemented:
            // &xar-config-varname;
            // &xar-mod-modname-varname;
            // &xar-session-varname;
            // &xar-url-modname-type-func-args;
        }
        xarLogMessage('ENT: found in xml source:'.$entityName);
        return $matches[0];
    }


}
?>
