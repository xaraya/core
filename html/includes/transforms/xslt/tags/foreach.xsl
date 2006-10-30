<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0" 
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"   
    xmlns:php="http://php.net/xsl" 
    exclude-result-prefixes="php xar">

<xsl:template match="xar:foreach">
  <xsl:processing-instruction name="php">
    <xsl:text>foreach(</xsl:text>
    <xsl:choose>
      <xsl:when test="@key!='' and @value!='' ">
        <xsl:value-of select="@in"/><xsl:text> as </xsl:text><xsl:value-of select="@key"/>
        <xsl:text disable-output-escaping="yes"> =&gt; </xsl:text><xsl:value-of select="@value"/>
      </xsl:when>
      <xsl:when test="@value!=''">
        <xsl:value-of select="@in"/><xsl:text> as </xsl:text><xsl:value-of select="@value"/>
      </xsl:when>
      <xsl:when test="@key!=''">
        <xsl:text>array_keys(</xsl:text>
        <xsl:value-of select="@in"/>
        <xsl:text>) as </xsl:text>
        <xsl:value-of select="@key"/>
      </xsl:when>
    </xsl:choose>
    <xsl:text>) {
    </xsl:text>
  </xsl:processing-instruction>

  <xsl:apply-templates/>

  <xsl:processing-instruction name="php">
    <xsl:text>}</xsl:text>
  </xsl:processing-instruction>
</xsl:template>

</xsl:stylesheet>
