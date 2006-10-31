<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0" 
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"   
    xmlns:php="http://php.net/xsl" 
    exclude-result-prefixes="php xar">

<xsl:template match="xar:base-render-javascript">
  <xsl:processing-instruction name="php">
    <xsl:text>echo trim(xarTplModule('base','javascript','render',array('javascript'=&gt;xarTplGetJavaScript('</xsl:text>
    <xsl:value-of select="@position"/>
    <xsl:text>'),'position'=&gt;'</xsl:text>
    <xsl:value-of select="@position"/>
    <xsl:text>','type'=&gt;'</xsl:text>
    <xsl:value-of select="@type"/>
    <xsl:text>')));</xsl:text>
  </xsl:processing-instruction>
</xsl:template>
</xsl:stylesheet>
