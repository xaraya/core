<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0" 
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"   
    xmlns:php="http://php.net/xsl" 
    exclude-result-prefixes="php xar">
    
  <xsl:template match="xar:blocklayout">
    <xsl:processing-instruction name="php">
      <xsl:text>$_bl_locale  = xarMLSGetCurrentLocale();&nl;</xsl:text>
      <xsl:text>$_bl_charset = xarMLSGetCharsetFromLocale($_bl_locale);&nl;</xsl:text>
      <xsl:text>header("Content-Type:</xsl:text>
      <xsl:value-of select="@content"/>
      <xsl:text>; charset = $_bl_charset");&nl;</xsl:text>
    </xsl:processing-instruction>
  
    <!-- Generate the doctype 
      @todo: can we do this earlier?
    -->
    <xsl:text disable-output-escaping="yes">&lt;!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"&gt;&nl;</xsl:text>
    <xsl:apply-templates />
  </xsl:template>
</xsl:stylesheet>
