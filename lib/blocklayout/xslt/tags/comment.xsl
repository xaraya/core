<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0" 
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"   
    xmlns:php="http://php.net/xsl" 
    exclude-result-prefixes="php xar">

<xsl:template match="xar:comment">
  <xsl:comment>
    <xsl:if test="string-length(@iecondition)">
      <xsl:text>[</xsl:text><xsl:value-of select="@iecondition"/><xsl:text>]&gt;</xsl:text>
    </xsl:if>
    <xsl:call-template name="translateText">
      <xsl:with-param name="expr" select="."/>
    </xsl:call-template>
    <xsl:if test="string-length(@iecondition)">
      <xsl:text>&lt;![endif]</xsl:text>
    </xsl:if>
  </xsl:comment>
</xsl:template>

</xsl:stylesheet>
