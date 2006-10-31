<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0" 
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"   
    xmlns:php="http://php.net/xsl" 
    exclude-result-prefixes="php xar">

<xsl:template match="xar:style">
  <xsl:processing-instruction name="php">
    <xsl:text>xarModAPIFunc('themes','user','register',array(</xsl:text>
    <xsl:if test="@file != ''">
      <xsl:text disable-output-escaping="yes">'file' =&gt;'</xsl:text><xsl:value-of select="@file"/><xsl:text>',</xsl:text>
    </xsl:if>
    <xsl:if test="@scope != ''">
      <xsl:text disable-output-escaping="yes">'scope' =&gt;'</xsl:text><xsl:value-of select="@scope"/><xsl:text>',</xsl:text>
    </xsl:if>
    <xsl:if test="@type != ''">
      <xsl:text disable-output-escaping="yes">'type' =&gt;'</xsl:text><xsl:value-of select="@type"/><xsl:text>',</xsl:text>
    </xsl:if>
    <xsl:if test="@media != ''">
      <xsl:text disable-output-escaping="yes">'media' =&gt;'</xsl:text><xsl:value-of select="@media"/><xsl:text>',</xsl:text>
    </xsl:if>
    <xsl:if test="@alternate != ''">
      <xsl:text disable-output-escaping="yes">'alternate' =&gt;'</xsl:text><xsl:value-of select="@alternate"/><xsl:text>',</xsl:text>
    </xsl:if>
    <xsl:if test="@title != ''">
      <xsl:text disable-output-escaping="yes">'title' =&gt;'</xsl:text><xsl:value-of select="@title"/><xsl:text>',</xsl:text>
    </xsl:if>
    <xsl:if test="@method != ''">
      <xsl:text disable-output-escaping="yes">'method' =&gt;'</xsl:text><xsl:value-of select="@method"/><xsl:text>',</xsl:text>
    </xsl:if>
    <xsl:if test="@condition != ''">
      <xsl:text disable-output-escaping="yes">'condition' =&gt;'</xsl:text><xsl:value-of select="@condition"/><xsl:text>',</xsl:text>
    </xsl:if>
    <xsl:text>));</xsl:text>
  </xsl:processing-instruction>
</xsl:template>
</xsl:stylesheet>
