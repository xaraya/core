<?php

    // Load templates with the .xd extension
    
    function loadsourcefilename($tplBaseDir,$tplSubPart,$tplBase,$templateName,$canTemplateName)
    {
        xarLogMessage("TPL: 7. Try legacy .xd templates in $tplBaseDir/xartemplates/");
        if(!empty($templateName) &&
            file_exists($sourceFileName = "$tplBaseDir/xartemplates/$tplSubPart/$tplBase-$templateName.xd")) {
        } elseif(
            file_exists($sourceFileName = "$tplBaseDir/xartemplates/$tplSubPart/$tplBase.xd")) {
        } elseif($canonical &&
            file_exists($sourceFileName = "$tplBaseDir/xartemplates/$canTemplateName.xd")) {
        } else {
            $sourceFileName = '';
        }
        return $sourceFileName;
    }
    
    /* Transform entities into numeric
     * Remove $ from varnames in xar:set
     * Add a blanksspace to empty text areas
     * Convert old tag name into new
     */
     function fixLegacy($templatestring)
    {
        // Quick & dirty wrapper for missing xmlns:xar in old 1.x templates
        if (!strpos($templatestring, ' xmlns:xar="') && !strpos($templatestring, '</xar:template>')) {
            $templatestring = '<?xml version="1.0" encoding="utf-8"?>
<xar:template xmlns:xar="http://xaraya.com/2004/blocklayout">' . $templatestring . '</xar:template>';

            // Replace non-numeric entitites in old 1.x templates
            $templatestring = str_replace(array_keys(entities()),entities(),$templatestring);

            // Allow xar:set name="$var" in old 1.x templates
            $templatestring = str_replace('<xar:set name="$','<xar:set name="',$templatestring);

            // Stop eating </textarea> in old 1.x templates
            $templatestring = str_replace('></textarea>','>&#160;</textarea>',$templatestring);

            // Javascript include in old 1.x templates
            $templatestring = str_replace('<xar:base-include-javascript','<xar:javascript',$templatestring);
        }
        return $templatestring;
    }

    function entities()
    {
        return array(
                '&amp;'    =>      '&#38;',
                '&lsquo;'    =>      '&#145;',
                '&rsquo;'    =>      '&#146;',
                '&ldquo;'    =>      '&#147;',
                '&rdquo;'    =>      '&#148;',
                '&bull'    =>      '&#149;',
                '&en;'    =>      '&#150;',
                '&em;'    =>      '&#151;',

                '&nbsp;'    =>      '&#160;',
                '&iexcl;'    =>      '&#161;',
                '&cent;'    =>      '&#162;',
                '&pound;'    =>      '&#163;',
                '&curren;'    =>      '&#164;',
                '&yen;'    =>      '&#165;',
                '&brvbar;'    =>      '&#166;',
                '&sect;'    =>      '&#167;',
                '&uml;'    =>      '&#168;',
                '&copy;'    =>      '&#169;',
                '&ordf;'    =>      '&#170;',
                '&laquo;'    =>      '&#171;',
                '&not;'    =>      '&#172;',
                '&shy;'    =>      '&#173;',
                '&reg;'    =>      '&#174;',
                '&macr;'    =>      '&#175;',
                '&deg;'    =>      '&#176;',
                '&plusmn;'    =>      '&#177;',
                '&sup2;'    =>      '&#178;',
                '&sup3;'    =>      '&#179;',
                '&acute;'    =>      '&#180;',
                '&micro;'    =>      '&#181;',
                '&para;'    =>      '&#182;',
                '&middot;'    =>      '&#183;',
                '&cedil;'    =>      '&#184;',
                '&sup1;'    =>      '&#185;',
                '&ordm;'    =>      '&#186;',
                '&raquo;'    =>      '&#187;',
                '&frac14;'    =>      '&#188;',
                '&frac12;'    =>      '&#189;',
                '&frac34;'    =>      '&#190;',
                '&iquest;'    =>      '&#191;',
                '&Agrave;'    =>      '&#192;',
                '&Aacute;'    =>      '&#193;',
                '&Acirc;'    =>      '&#194;',
                '&Atilde;'    =>      '&#195;',
                '&Auml;'    =>      '&#196;',
                '&Aring;'    =>      '&#197;',
                '&AElig;'    =>      '&#198;',
                '&Ccedil;'    =>      '&#199;',
                '&Egrave;'    =>      '&#200;',
                '&Eacute;'    =>      '&#201;',
                '&Ecirc;'    =>      '&#202;',
                '&Euml;'    =>      '&#203;',
                '&Igrave;'    =>      '&#204;',
                '&Iacute;'    =>      '&#205;',
                '&Icirc;'    =>      '&#206;',
                '&Iuml;'    =>      '&#207;',
                '&ETH;'    =>      '&#208;',
                '&Ntilde;'    =>      '&#209;',
                '&Ograve;'    =>      '&#210;',
                '&Oacute;'    =>      '&#211;',
                '&Ocirc;'    =>      '&#212;',
                '&Otilde;'    =>      '&#213;',
                '&Ouml;'    =>      '&#214;',
                '&times;'    =>      '&#215;',
                '&Oslash;'    =>      '&#216;',
                '&Ugrave;'    =>      '&#217;',
                '&Uacute;'    =>      '&#218;',
                '&Ucirc;'    =>      '&#219;',
                '&Uuml;'    =>      '&#220;',
                '&Yacute;'    =>      '&#221;',
                '&THORN;'    =>      '&#222;',
                '&szlig;'    =>      '&#223;',
                '&agrave;'    =>      '&#224;',
                '&aacute;'    =>      '&#225;',
                '&acirc;'    =>      '&#226;',
                '&atilde;'    =>      '&#227;',
                '&auml;'    =>      '&#228;',
                '&aring;'    =>      '&#229;',
                '&aelig;'    =>      '&#230;',
                '&ccedil;'    =>      '&#231;',
                '&egrave;'    =>      '&#232;',
                '&eacute;'    =>      '&#233;',
                '&ecirc;'    =>      '&#234;',
                '&euml;'    =>      '&#235;',
                '&igrave;'    =>      '&#236;',
                '&iacute;'    =>      '&#237;',
                '&icirc;'    =>      '&#238;',
                '&iuml;'    =>      '&#239;',
                '&eth;'    =>      '&#240;',
                '&ntilde;'    =>      '&#241;',
                '&ograve;'    =>      '&#242;',
                '&oacute;'    =>      '&#243;',
                '&ocirc;'    =>      '&#244;',
                '&otilde;'    =>      '&#245;',
                '&ouml;'    =>      '&#246;',
                '&divide;'    =>      '&#247;',
                '&oslash;'    =>      '&#248;',
                '&ugrave;'    =>      '&#249;',
                '&uacute;'    =>      '&#250;',
                '&ucirc;'    =>      '&#251;',
                '&uuml;'    =>      '&#252;',
                '&yacute;'    =>      '&#253;',
                '&thorn;'    =>      '&#254;',
                '&yuml;'    =>      '&#255;',
                '&bull;'    =>      '&#8226;',
                );
    }
?>