<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0" 
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"   
    xmlns:php="http://php.net/xsl" 
    exclude-result-prefixes="php xar">

<xsl:template name="xar-blockgroup" match="xar:blockgroup">
  <xsl:processing-instruction name="php">
    <xsl:text>echo </xsl:text>
    <xsl:call-template name="blockgroup_code"/>
  </xsl:processing-instruction>
</xsl:template>

<xsl:template match="xar:set/xar:blockgroup">
  <xsl:call-template name="blockgroup_code"/>
</xsl:template>

<xsl:template name="blockgroup_code">
  <xsl:choose>
    <xsl:when test="child::node()">
      <xsl:text>$_bl_blockgroup_template = '></xsl:text>
      <xsl:value-of select="@template"/>
      <xsl:text>';&nl;</xsl:text>

      <xsl:apply-templates />

      <xsl:text>unset($_bl_blockgroup_template);</xsl:text>
    </xsl:when>
    <xsl:when test="not(child::node())">
      <xsl:text>xarBlock_renderGroup('</xsl:text><xsl:value-of select="@name"/>
      <xsl:text>');&nl;</xsl:text>

      <xsl:apply-templates />
    </xsl:when>
    <xsl:text>&nl;</xsl:text>
  </xsl:choose>
</xsl:template>

</xsl:stylesheet>