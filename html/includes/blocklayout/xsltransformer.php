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
    private $xslProc = null;
    private $xmlDoc  = null;
    private $origXml = '';
    private $prepXml = '';
    public  $xmlFile = null;
        
    function __construct(&$xml = '', $xslFile='') 
    {
        // Save the original XML
        $this->origXml = $xml;
        
        // Set up the xsl processor
        $this->xslProc = new XSLTProcessor();
        $this->xslProc->registerPHPFunctions();

        // Set up the stylesheet
        $domDoc = new DOMDocument();
        $domDoc->load($xslFile);
        set_exception_handler(array('ExceptionHandlers','bone'));
        $this->xslProc->importStyleSheet($domDoc);
        
        // Preprocess the xml, so we dont get unresolved entities and stuff.
        // &xar-entity; -> [whatever php code it needs];
        $entityPattern = '/(&xar-[a-z\-_]+?;)/';
        $callBack      = array('XsltCallbacks','entities');
        $this->prepXml = preg_replace_callback($entityPattern,$callBack,$this->origXml);

        // Set up the document to transform
        $this->xmlDoc = new DOMDocument();
        $this->xmlDoc->resolveExternals = true;
        $this->xmlDoc->loadXML($this->prepXml);
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
        set_exception_handler(array('ExceptionHandlers','bone'));
        $result = $this->xslProc->transformToXML($this->xmlDoc);
        //set_exception_handler(array('ExceptionHandlers','default'));
        
        // Expressions in attributes are not handled by the transform because
        // XSLT can not generate anything other than valid XML (well, it can but 
        // definitely not inside attributes), which exclude php PI's
        // in attrbiutes
        $exprPattern = '/(#[^"#]+?#)/';
        $callBack    = array('XsltCallbacks','attributes');
        $result = preg_replace_callback($exprPattern,$callBack,$result);
        //debug(htmlspecialchars($result));
        return $result;
    }

    static function escape($var)
    {
        return str_replace("'","\'",$var);
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
    static function attributes($matches)
    {
        $raw = ExpressionTransformer::transformPHPExpression(substr($matches[1],1,-1));
        $raw = self::reverseXMLEntities($raw);
        $res = '<?php echo ' . $raw .';?>';
        xarLogMessage('ATT: processed'.$matches[1]);
        return $res;
    }
    
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
            case 'baseurl':
                return '#xarServer::getBaseURL()#';
                break;
            case 'modurl':
                //   1       2     3    4
                // modurl-modname-type-func
                if( isset($entityParts[2]) and 
                    isset($entityParts[3]) and
                    isset($entityParts[4])
                ) return "#xarModUrl('$entityParts[2]','$entityParts[3]','$entityParts[4]')#";
                break;
            default:
                return $matches[0];
        }
        
        xarLogMessage('ENT: found in xml source:'.$entityName);
        return $matches[0];
    }
    
    private static function reverseXMLEntities($content)
    {
        return str_replace(
            array('&amp;', '&gt;', '&lt;', '&quot;'),
            array('&', '>', '<', '"'),
            $content
        );
    }
}
?>
