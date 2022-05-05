<?php
/**
 * XSLT version of the BL compiler
 *
 * @package blocklayout
 * @subpackage xsl
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marcel van der Boom <marcel@xaraya.com>
**/

sys::import('blocklayout.compiler');

class BlockLayoutXSLTProcessor extends xarObject
{
    protected $xslProc = null;    // Object representing the processor.
    protected $xslDoc  = null;    // Object representing the stylesheet.
    protected $xmlDoc  = null;    // Object representing the input XML.

    protected $origXml = '';      // The original XML
    protected $prepXml = '';      // The preprocessed XML
    protected $postXml = '';      // The transformed result XML

    public  $xmlFile = null;

    public function __construct($xslFile=null)
    {
        // Set up the xsl processor
        $this->xslProc = new XSLTProcessor();
        $this->xslProc->registerPHPFunctions();

        // Set the exceptions handler
        sys::import('xaraya.exceptions.handlers');
    	xarDebug::setExceptionHandler(array('ExceptionHandlers','bone'));

        // Set up the stylesheet
        if (isset($xslFile)) $this->setStyleSheet($xslFile);
        // Set up the document to transform
        $this->xmlDoc = new DOMDocument();
        // Setting this to false makes it 2 times faster, what do we lose?
        $this->xmlDoc->resolveExternals = false;
        // We're still a long way from validating
        // $this->xmlDoc->validateOnParse = true;
    }

    public function setStyleSheet($xslFile)
    {
        $this->xslDoc = new DOMDocument();
        $this->xslDoc->load($xslFile);
		$this->importStyleSheet($this->xslDoc);
    }

    protected function setSourceDocument(&$xml)
    {
        xarLog::message(xarML("XSL: Creating a DOM document for the template code"), xarLog::LEVEL_DEBUG);
        $this->xmlDoc = new DOMDocument();
        // Setting this to false makes it 2 times faster, what do we lose?
        $this->xmlDoc->resolveExternals = false;
        // We're still a long way from validating
        // $this->xmlDoc->validateOnParse = true;
        $file = isset($this->xmlFile) ? $this->xmlFile : 'unknown';
        xarLog::message(xarML("XSL: Loading the template code"), xarLog::LEVEL_DEBUG);
        $this->xmlDoc->loadXML($xml);

        // Set up additional parameters related to the input
        // @todo wrong here.
        if(isset($this->xmlFile)) {
        	xarLog::message(xarML("XSL: Adding parameters to the processor"), xarLog::LEVEL_DEBUG);
            // Set up the parameters
            $this->xslProc->setParameter('','bl_filename',basename($this->xmlFile));
            $this->xslProc->setParameter('','bl_dirname',dirname($this->xmlFile));
            $this->xslProc->setParameter('','bl_doctype',xarTpl::getDocType());
        }
    }

    protected function preProcess()
    {
        // Make sure our entities look like expressions
        // &xar-entity; -> #[whatever expression it needs]#
        $this->prepXml = $this->origXml;
        $entityPattern = '/(&xar-[a-z\-_]+?;)/';
        $callBack      = array('XsltCallbacks','entities');
        $this->prepXml = preg_replace_callback($entityPattern,$callBack,$this->prepXml);

        // Make sure ML placeholders look like expressions
        // #(1)... -> #(1)#...
        // Disable  this for now (random)
        //$mlsPattern     = '/(#\([0-9]+\))([^#])/';
        //$callBack       = array('XsltCallbacks','mlsplaceholders');
        //$this->prepXml  = preg_replace_callback($mlsPattern, $callBack, $this->prepXml);
    }

    public function importStyleSheet($xslDoc)
    {
        xarLog::message(xarML("XSL: Importing the stylesheet"), xarLog::LEVEL_DEBUG);
		if (!$this->xslProc->importStyleSheet($xslDoc)) {
			$halt = xarML('Could not load the stylesheet #(1)',$xslDoc->saveXML());
			echo $halt; exit;
		}
        xarLog::message(xarML("XSL: The stylesheet was successfully imported"), xarLog::LEVEL_DEBUG);
    }
    public function setParameter($space, $name, $value)
    {
        $this->xslProc->setParameter($space, $name, $value);
    }
    public function transformToXML($xmlString)
    {
        xarLog::message(xarML("XSL: Running the transform to XML"), xarLog::LEVEL_DEBUG);
        try {$transform = $this->xslProc->transformToXML($xmlString);
        } catch (Exception $e) {
        xarLog::message(xarML("XSL: huh?"), xarLog::LEVEL_WARNING);
        }
        return $transform;
    }
    public function transformToDoc($xmlString)
    {
        xarLog::message(xarML("XSL: Running the transform to Doc"), xarLog::LEVEL_DEBUG);
        return $this->xslProc->transformToDoc($xmlString);
    }

    public function transform(&$xml)
    {
        // Save the original XML
        $this->origXml = $xml;

        xarLog::message(xarML("XSL: Running the preprocess code"), xarLog::LEVEL_DEBUG);
        // Preprocess it.
        $this->preProcess();

        // Legacy transforms for old 1x templates
        try {
            if (class_exists('xarConfigVars') && xarConfigVars::get(null, 'Site.Core.LoadLegacy')) {
        		xarLog::message(xarML("XSL: Running the legacy transform code"), xarLog::LEVEL_DEBUG);
                sys::import('xaraya.legacy.templates');
                $this->prepXml = xar_legacy_templates_fixLegacy($this->prepXml);
            }
        } catch (Exception $e) {}

        // Set the source document to what we prepped
        $this->setSourceDocument($this->prepXml);

        // Transform it
        xarLog::message(xarML("XSL: Running the XML transform"), xarLog::LEVEL_DEBUG);
    	xarDebug::setExceptionHandler(array('ExceptionHandlers','defaulthandler'));
        // What should we initialize $result to?
        try {
        	$this->postXML = $this->transformToXML($this->xmlDoc);
        } catch (Exception $e) {xarLog::message(xarML("XSL: rolling"), xarLog::LEVEL_WARNING);
        }

        // Postprocess it
        xarLog::message(xarML("XSL: Running the postprocess code"), xarLog::LEVEL_DEBUG);
        $this->postProcess();
        return $this->postXML;
    }

    protected function postProcess()
    {
        /*
        	This first part processes the PHP expressions if there is no support for XSLT::registerPHPFunctions()
        	This seems to be the case with MAMP as far as I can tell, at least in later PHP versions (7.x). Or maybe it's just badly documented.
        	This is not elegant, but it is just moving the resolution of PHP expressions from the XLS transform to here.
        	The code that does the resolving is the same. At worst, I think, we lose some speed.
        	
        	- We have replaced the #...# delimiters with %#%...%#%. This is not necessary, but might avoid some mixups and in any case is clearer.
        	- TDOD: move this back to xar2php.xsl when we can (when PHP adopts XSLT 2.x?)
        */
        $exprPattern = '/%#%(.*?)%#%/';
        $callBack    = array('XsltCallbacks','phpexpressions');
        $this->postXML = preg_replace_callback($exprPattern,$callBack,$this->postXML);
        
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
        
        // Special handling for xar:attribute, where the tag is created at runtime
        $this->postXML = str_replace('xyzzy','<?php echo ',$this->postXML);        
        $this->postXML = str_replace('yzzyx',';?>',$this->postXML);        
    }

    static function phpexpression($expr)
    {
        xarLog::message(xarML("BlockLayoutXSLTProcessor::phpexpression: '#(1)'", $expr), xarLog::LEVEL_DEBUG);
        $res = ExpressionTransformer::transformPHPExpression($expr);
        xarLog::message(xarML("BlockLayoutXSLTProcessor::phpexpression: '#(1)' resolved to '#(2)'", $expr, $res), xarLog::LEVEL_DEBUG);
        return $res;
    }
}

/**
 * @package blocklayout
 * @subpackage xsl
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marcel van der Boom <marcel@xaraya.com>
**/
class XsltCallbacks extends xarObject
{
    static function phpexpressions($matches)
    {
        // Ignore empty strings
        $raw = ExpressionTransformer::transformPHPExpression($matches[1]);
        $res = self::reverseXMLEntities($raw);
        return $res;
    }

    static function mlsplaceholders($matches)
    {
        $res = $matches[1].'#'.$matches[2];
        //xarLog::message('MLS: ' . $matches[0] . ' => '.$res);
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
//        xarLog::message('XsltCallbacks::attributes: '. $matches[0] . ' => ' . $res, xarLog::LEVEL_DEBUG);
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
                ) return "#xarController::URL('$entityParts[2]','$entityParts[3]','$entityParts[4]')#";
                break;
            // &xar-var;
            case 'var':
                return "#\${$entityParts[2]}#";
            // &xar-currenturl;
            case 'currenturl':
                return '#xarServer::getCurrentURL()#';
            // Not implemented:
            // &xar-config-varname;
            // &xar-mod-modname-varname;
            // &xar-session-varname;
            // &xar-url-modname-type-func-args;
        }
        xarLog::message('XsltCallbacks::entities: found in xml source:'.$entityName, xarLog::LEVEL_DEBUG);
        return $matches[0];
    }
}
?>
