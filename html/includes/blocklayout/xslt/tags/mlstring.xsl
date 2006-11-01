<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0" 
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"   
    xmlns:php="http://php.net/xsl" 
    exclude-result-prefixes="php xar">

<xsl:template match="xar:mlstring">
  <xsl:processing-instruction name="php">
    <xsl:text>echo xarML('</xsl:text>
    <xsl:value-of 
        select="php:functionString('BlockLayoutXSLTProcessor::escape',string(.))"
        disable-output-escaping="yes" />
    <xsl:text>');</xsl:text>
  </xsl:processing-instruction>
</xsl:template>

<xsl:template match="xar:set/xar:mlstring">
    <xsl:text>xarML('</xsl:text>
    <xsl:value-of 
        select="php:functionString('BlockLayoutXSLTProcessor::escape',string(.))"
        disable-output-escaping="yes" />
    <xsl:text>');</xsl:text>
</xsl:template>
    
</xsl:stylesheet>
