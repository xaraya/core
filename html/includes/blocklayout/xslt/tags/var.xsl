<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0" 
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"   
    xmlns:php="http://php.net/xsl" 
    exclude-result-prefixes="php xar">
    
<xsl:template match="xar:set/xar:var">
    <xsl:call-template name="xarvar_code"/>
</xsl:template>

<xsl:template name="xar-var" match="xar:var">
  <xsl:processing-instruction name="php">
    <xsl:text>echo </xsl:text>
    <xsl:call-template name="xarvar_code"/>
    <xsl:text>;</xsl:text>
  </xsl:processing-instruction>
</xsl:template>

<xsl:template name="xarvar_code">
    <xsl:choose>
      <xsl:when test="@scope = 'module'">
        <xsl:text>xarModVars::get('</xsl:text>
        <xsl:value-of select="@module"/>
        <xsl:text>', '</xsl:text>
        <xsl:value-of select="@name"/>
        <xsl:text>')</xsl:text>
      </xsl:when>
      <xsl:when test="@scope = 'local' or not(@scope)">
        <xsl:text>$</xsl:text><xsl:value-of select="@name"/>
      </xsl:when>
      <xsl:when test="@scope = 'user'">
        <xsl:text>xarUserGetVar('</xsl:text>
        <xsl:value-of select="@name"/>
        <xsl:text>',</xsl:text>
        <xsl:call-template name="resolvePHP">
          <xsl:with-param name="expr" select="@user"/>
        </xsl:call-template>
        <xsl:text>)</xsl:text>
      </xsl:when>
    </xsl:choose>
</xsl:template>
</xsl:stylesheet>