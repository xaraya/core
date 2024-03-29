<?php
/**
 * BlockLayout Template Engine Compiler
 *
 * The compiler is responsible for compiling xar + xml -> php + xml
 *
 * @package blocklayout
 * @subpackage compiler
 * @category Xaraya Web Applications Framework
 * @version 2.4.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.info
 *
 * @author Marco Canini <marco@xaraya.com>
 * @author Paul Rosania  <paul@xaraya.com>
 * @author Marcel van der Boom <marcel@xaraya.com>
 * @author Marty Vance <dracos@xaraya.com>
 * @author Garrett Hunter <garrett@blacktower.com>
 * @todo  This is still the architecture of BL1, just stripped. We can do a lot better.
 */

/**
 *  Interface definition for the blocklayout compiler, these are the things
 *  it offers, no more, no less
 *
 */
interface IxarBLCompiler
{
    /** @return IxarBLCompiler */
    public static function &instance();        // Get an instance of the compiler
    /** @return array<string> */
    public function configure();               // configure the compiler
    /**
     * @param string $fileName
     * @return string
     */
    public function compileFile($fileName);    // compile a file
    /**
     * @param string $data
     * @return string
     */
    public function compileString(&$data);     // compile a string
}

/**
 * xarBLCompiler - the abstraction of the BL compiler
 *
 * The compiler holds the parser and the code generator as objects
 *
 * @package blocklayout
 * @access public
 */
class xarBLCompiler extends xarObject implements IxarBLCompiler
{
    /** @var ?xarBLCompiler */
    public static $instance  = null;
    /** @var ?string */
    private $lastFile        = null;
    /** @var ?BlockLayoutXSLTProcessor */
    private $processor       = null;

    /** @var mixed */
    protected $compresswhitespace = 1;

    /**
     * Private constructor, since this is a Singleton
     */
    public function __construct()
    {
    }

    /**
     * Implementation of the interface
     * @return IxarBLCompiler
     */
    public static function &instance()
    {
        if(self::$instance == null) {
            self::$instance = new xarBLCompiler();
        }
        return self::$instance;
    }

    /**
     * Summary of compileString
     * @param string $data
     * @return string
     */
    public function compileString(&$data)
    {
        return $this->compile($data);
    }

    /**
     * Summary of compileFile
     * @param string $fileName
     * @throws \Exception
     * @return string
     */
    public function compileFile($fileName)
    {
        xarLog::message("BL: Compiling the file '$fileName'", xarLog::LEVEL_DEBUG);
        $this->lastFile = $fileName;
        // The @ makes the code better to handle, leave it.
        if (!($fp = @fopen($fileName, 'r'))) {
            throw new Exception("Cannot open template file '" . $fileName . "'");
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

        $res = $this->compile($templateSource);
        return $res;
    }

    /**
     * Summary of getTagPaths
     * @param string $filepath
     * @param string $prefix
     * @return array<string>
     */
    public function getTagPaths($filepath, $prefix)
    {
        $files = array();
        foreach (new DirectoryIterator($filepath) as $fileInfo) {
            if($fileInfo->isDot()) {
                continue;
            }
            $pathinfo = pathinfo($fileInfo->getPathName());
            if(isset($pathinfo['extension']) && $pathinfo['extension'] != 'xsl') {
                continue;
            }
            $files[] = $prefix . "/" . $fileInfo->getFileName();
        }
        return $files;
    }

    /**
     * Summary of configure
     * @return array<string>
     */
    public function configure()
    {
        // Compressing excess whitespace
        try {
            $this->compresswhitespace = xarConfigVars::get(null, 'Site.BL.CompressWhitespace');
        } catch (Exception $e) {
            $this->compresswhitespace = 1;
        }
        return [];
    }

    /**
     * Summary of getProcessor
     * @param string $xslFile
     * @return BlockLayoutXSLTProcessor
     */
    protected function getProcessor($xslFile = '')
    {
        xarLog::message("BL: Creating a new XSLT processor", xarLog::LEVEL_DEBUG);

        sys::import('blocklayout.xsltransformer');
        if (empty($xslFile)) {
            $xslProc = new BlockLayoutXSLTProcessor();
        } else {
            $xslProc = new BlockLayoutXSLTProcessor($xslFile);
        }
        return $xslProc;
    }

    /**
     * Private methods
     */

    /**
     * Summary of boot
     * @param DOMDocument|null $customDoc
     * @return string
     */
    protected function boot($customDoc = null)
    {
        $xslFile = sys::lib() . 'blocklayout/xslt/booter.xsl';
        if (!isset($this->processor)) {
            $this->processor = $this->getProcessor();
        }
        $this->processor->setStyleSheet($xslFile);

        $xmlFile = sys::lib() . 'blocklayout/xslt/xar2php.xsl';
        $doc = new DOMDocument();
        $doc->load($xmlFile);

        // Pass the default tags
        $baseDir = sys::lib() . 'blocklayout/xslt/defaults';
        $xslFiles = $this->getTagPaths($baseDir, 'defaults');
        $this->processor->setParameter('', 'defaults', implode(',', $xslFiles));

        // Pass the debug tags
        $baseDir = sys::lib() . 'blocklayout/xslt/debug';
        $xslFiles = $this->getTagPaths($baseDir, 'debug');
        $this->processor->setParameter('', 'debug', implode(',', $xslFiles));

        // Pass the Blocklayout tags
        $baseDir = sys::lib() . 'blocklayout/xslt/tags';
        $xslFiles = $this->getTagPaths($baseDir, 'tags');
        $this->processor->setParameter('', 'bltags', implode(',', $xslFiles));

        // Pass the custom tags of the client using Blocklayout
        $clienttags = $this->configure();
        $this->processor->setParameter('', 'clienttags', implode(',', $clienttags));

        // Pass any legacy tags if legacy support is turned on
        try {
            if (class_exists('xarConfigVars') && xarConfigVars::get(null, 'Site.Core.LoadLegacy')) {
                $baseDir = sys::lib() . 'xaraya/legacy/tags';
                $baseDir = realpath($baseDir);
                if (strpos($baseDir, '\\') != false) {
                    // On Windows, drive letters are preceeded by an extra / [file:///C:/...]
                    $baseURI = 'file:///' . str_replace('\\', '/', $baseDir);
                } else {
                    $baseURI = 'file://' . $baseDir;
                }
                $xslFiles = $this->getTagPaths($baseDir, $baseURI);
                $this->processor->setParameter('', 'legacytags', implode(',', $xslFiles));
            }
        } catch (Exception $e) {
        }

        // Compress excess whitespace
        $this->processor->setParameter('', 'compresswhitespace', $this->compresswhitespace);

        // Pass any custom markup. We expect this to be in the form of a stylesheet document
        // We do this by adding the nodes to the end of our stylesheet
        if (!empty($customDoc)) {
            // Get the node of the import document that contains the stylesheet markup
            $sheetnode = $customDoc->getElementsByTagName("stylesheet")->item(0);
            // Get all its child nodes
            $children = $sheetnode->childNodes;
            // Run through them and append to the end of our stylesheet
            foreach($children as $node) {
                $tempnode = $doc->importNode($node, true);
                $doc->documentElement->appendChild($tempnode);
            }
        }

        // Compile the compiler
        $outDoc = $this->processor->transformToXML($doc);
        return $outDoc;
    }

    /**
     * Summary of compile
     * @param string $templateSource
     * @return string
     */
    protected function compile(&$templateSource)
    {
        xarLog::message("BL: Checking for an XSLT processor", xarLog::LEVEL_DEBUG);
        if (!isset($this->processor)) {
            $this->processor = $this->getProcessor();
            $xslDoc = new DOMDocument();
            xarLog::message("BL: Creating the compiler as a stylesheet", xarLog::LEVEL_DEBUG);
            $xslDoc->loadXML($this->boot());
            $this->processor->importStyleSheet($xslDoc);
        }

        // This is confusing, don't do this here.
        $this->processor->xmlFile = $this->lastFile;
        xarLog::message("BL: Preparing the transform", xarLog::LEVEL_DEBUG);
        $outDoc = $this->processor->transform($templateSource);

        return $outDoc;
    }
}

/**
 * ExpressionTransformer
 *
 * Transforms BL and php expressions from templates.
 *
 * @package blocklayout
 * @access public
 * @todo   make protected, should only be called from tag handler no?
 * @todo   split up in PHP and BL parts with one interface method (transform)
 */
class ExpressionTransformer extends xarObject
{
    public const XAR_TOKEN_VAR_START = '$';
    public const XAR_TOKEN_CI_DELIM  = '#';
    /**
     * Replace the array and object notation.
     * This is the BLExpression grammar:
     * BLExpression ::= Variable | Variable '.' ArrayKey | Variable ':' Property
     * Variable ::= [a-zA-Z_] ([0-9a-zA-Z_])*
     * ArrayKey ::= Name | Name '.' ArrayKey | Name ':' Property
     * Property ::= Name | Name '.' ArrayKey | Name ':' Property
     * Name     ::= ([0-9a-zA-Z_])+
     * @param string $blExpression
     * @return string
     */
    public static function transformBLExpression($blExpression)
    {
        $blExpression = self::normalize($blExpression);

        // 'resolve' the dot and colon notation
        $subparts = preg_split('/[\[|\]]/', $blExpression);
        if(count($subparts) > 1) {
            foreach($subparts as $subpart) {
                // Resolve the subpart
                $blExpression = str_replace($subpart, self::transformBLExpression($subpart), $blExpression);
            }
            return $blExpression;
        }

        $identifiers = preg_split('/[.|:]/', $blExpression);
        $operators = preg_split('/[^.|^:]/', $blExpression, -1, PREG_SPLIT_NO_EMPTY);

        $numIdentifiers = count($identifiers);

        $expression = $identifiers[0];
        for ($i = 1; $i < $numIdentifiers; $i++) {
            if($operators[$i - 1] == '.') {
                if((substr($identifiers[$i], 0, 1) == self::XAR_TOKEN_VAR_START) || is_numeric($identifiers[$i])) {
                    $expression .= "[".$identifiers[$i]."]";
                } else {
                    $expression .= "['".$identifiers[$i]."']";
                }
            } elseif($operators[$i - 1] == ':') {
                $expression .= '->'.$identifiers[$i];
            }
        }
        return $expression;
    }

    /**
     * Transform a PHP expression from a template to a valid piece of PHP code
     *
     * @param string $phpExpression
     * @return string|void Valid PHP expression
     * @todo if expressions were always between #...# this would be easier
     * @todo if the key / objectmember is a variable, make sure it fits the regex for a valid variable name
     * @todo the convenience operators may conflict in some situations with the MLS ( like 'le' for french)
     **/
    public static function transformPHPExpression($phpExpression)
    {
        $phpExpression = self::normalize($phpExpression);
        // This regular expression matches variables in their notation as
        // supported by php  and according to the dot/colon grammar in the
        // method above. These expressions are matched and passed on to the BL
        // expression resolver above which resolves them into php variables notation.
        // The resolved names are replaced in the original expression

        // Let's dissect the expression so it's a bit more clear:
        //  1. /..../i            => we're matching in a case - insensitive  way what's between the /-es (FIXME: KEEP AN EYE ON THIS)
        //  2. \\\$               => matches \$ which is an escaped $ in the string to match
        //  3. (                  => this starts a captured subpattern
        //  4.  [a-z_]            => matches a letter or underscore, which is wat vars need to start with
        //  5.  [0-9a-z_\[\]\$]*  => matches the rest of the variables which might be present, while preserving [ and ]
        //  6.  (                 => start property / array access subpattern
        //  7.   :|\\.            => matches the colon or the dot notation
        //  8.   [$]{0,1}         => the array key or object member may be a variable
        //  9.   [0-9a-z_\]\[\$]+ => matches number,letter or underscore, one or more occurrences
        // 10.  )                 => matches right brace
        // 11.  *                 => match zero or more occurences of the property access / array key notation (colon notation)
        // 12. )                  => ends the current pattern
        // NOTE: The behaviour of this method along with the BLExpression method above CHANGED. Part
        //       of the resolving is now done by the previous method (i.e. a complete expression is passed into it)

        $regex = "/((\\\$[a-z_][a-z0-9_\[\]\$]*)([:|\.][$]{0,1}[0-9a-z_\]\[\$]+)*)/i";
        if (preg_match_all($regex, $phpExpression, $matches)) {
            // Resolve BL expressions inside the php Expressions

            // To prevent overlap as much as we can we sort descending by length
            usort($matches[0], array('ExpressionTransformer','rlensort'));
            $numMatches = count($matches[0]);
            for ($i = 0; $i < $numMatches; $i++) {
                // CHECKME: & removed here for php 4.4
                $resolvedName = self::transformBLExpression($matches[0][$i]);
                if (!isset($resolvedName)) {
                    return;
                } // throw back

                // CHECK: Does it matter if there is overlap in the matches?
                $phpExpression = str_replace($matches[0][$i], $resolvedName, $phpExpression);
            }
        }

        $findLogic      = array(' eq ', ' ne ', ' lt ', ' gt ', ' id ', ' nd ', ' le ', ' ge ');
        $replaceLogic   = array(' == ', ' != ',  ' < ',  ' > ', ' === ', ' !== ', ' <= ', ' >= ');

        $phpExpression = str_replace($findLogic, $replaceLogic, $phpExpression);

        return $phpExpression;
    }

    /**
     * Summary of rlensort
     * @param string $a
     * @param string $b
     * @return int
     */
    public static function rlensort($a, $b)
    {
        if(strlen($a) == strlen($b)) {
            return 0;
        }
        return (strlen($a) < strlen($b)) ? 1 : -1;
    }

    /**
     * Summary of normalize
     * @param string $expr
     * @return string
     */
    public static function normalize($expr)
    {
        /* If the expression is enclosed in # s, ignore them */
        if(empty($expr)) {
            return $expr;
        }
        if($expr[0] == self::XAR_TOKEN_CI_DELIM &&
            $expr[strlen($expr) - 1] == self::XAR_TOKEN_CI_DELIM) {
            $expr = substr($expr, 1, -1);
        }
        return $expr;
    }
}
