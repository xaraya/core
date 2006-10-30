<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0" 
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"   
    xmlns:php="http://php.net/xsl" 
    exclude-result-prefixes="php xar">

<xsl:template name="xar-set" match="xar:set">
  <xsl:processing-instruction name="php">
    <xsl:text>$</xsl:text>
    <xsl:value-of select="@name"/>
    <xsl:text>=</xsl:text>

    <xsl:apply-templates/>

    <xsl:text>;$_bl_data['</xsl:text>
    <xsl:value-of select="@name"/>
    <xsl:text>']=$</xsl:text>
    <xsl:value-of select="@name"/>
    <xsl:text>;&nl;</xsl:text>
  </xsl:processing-instruction>
</xsl:template>
</xsl:stylesheet>
