<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"
    xmlns:php="http://php.net/xsl"
    exclude-result-prefixes="php xar">

<!--
  We're setting the thing specified by name
-->
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

<!--
  If xar:set has a text node, do a poor mans resolving for now
-->
<xsl:template match="xar:set/text()">
    <xsl:choose>
      <xsl:when test="substring(normalize-space(.),1,1) = '#'">
        <!-- The string starts with # so, let's resolve it -->
        <xsl:call-template name="resolvePHP">
          <xsl:with-param name="expr" select="normalize-space(.)"/>
        </xsl:call-template>
      </xsl:when>
      <xsl:otherwise>
        <!-- No start with #, just copy it -->
        <xsl:copy/>
      </xsl:otherwise>
    </xsl:choose>
</xsl:template>

</xsl:stylesheet>
