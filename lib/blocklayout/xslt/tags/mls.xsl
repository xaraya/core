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
  xar:ml tags signals something is up for translation as a unit, so
  group everything below into one xarML call
-->
<xsl:template match="xar:ml">
  <xsl:processing-instruction name="php">
    <xsl:text>echo xarML('</xsl:text>
    <xsl:apply-templates/>
    <xsl:text>'</xsl:text>
    <xsl:for-each select=".//xar:var">
      <xsl:text>,</xsl:text>
      <xsl:call-template name="xarvar_getcode"/>
    </xsl:for-each>
    <xsl:for-each select="xar:mlvar">
      <xsl:if test="count(xar:var)=0">
        <xsl:text>,</xsl:text>
        <xsl:call-template name="mlvar"/>
      </xsl:if>
    </xsl:for-each>
    <xsl:text>);</xsl:text>
  </xsl:processing-instruction>
</xsl:template>

<!--
  xar:ml as child of xar:set is already in php mode, no need to
  do it again (TEMP, ugly)
-->
<xsl:template match="xar:set/xar:ml">
  <xsl:text>xarML('</xsl:text>
  <xsl:apply-templates/>
  <xsl:text>'</xsl:text>
  <xsl:for-each select=".//xar:var">
    <xsl:text>,</xsl:text>
    <xsl:call-template name="xarvar_getcode"/>
  </xsl:for-each>
  <xsl:for-each select="xar:mlvar">
    <xsl:if test="count(xar:var)=0">
      <xsl:text>,</xsl:text>
      <xsl:call-template name="mlvar"/>
    </xsl:if>
  </xsl:for-each>
  <xsl:text>);</xsl:text>
</xsl:template>

<!--
  xar:var tags as children of xar:ml need to get placeholders
-->
<xsl:template match="xar:ml//xar:var">
  <xsl:text>#(</xsl:text>
  <xsl:number from="xar:ml" level="any"/>
  <xsl:text>)#</xsl:text>
</xsl:template>



<!--
  Matching the old xar:mlvar tag does nothing, but
-->
<xsl:template match="xar:mlvar" />

<!--
  we pick up its values to add to the PHP xarML function as params by explicitly calling this template
-->
<xsl:template name="mlvar">
  <xsl:param name="expr">
    <xsl:value-of select="."/>
  </xsl:param>
  <xsl:param name="strippedexpr">
    <xsl:value-of select="substring-before(substring-after($expr,'#'),'#')"/>
  </xsl:param>
  <xsl:choose>
    <xsl:when test="$strippedexpr=''">
      <xsl:value-of select="$expr"/>
    </xsl:when>
    <xsl:otherwise>
      <xsl:value-of select="$strippedexpr"/>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

<!-- Not handled anymore, ignore closed mlvar, pass on content of mlstring -->
<xsl:template match="xar:mlstring">
  <xsl:call-template name="replace">
    <xsl:with-param name="source">
      <xsl:value-of select="."/>
    </xsl:with-param>
  </xsl:call-template>
</xsl:template>
<xsl:template match="xar:set/xar:ml/xar:mlstring">
  <xsl:call-template name="replace">
    <xsl:with-param name="source">
      <xsl:value-of select="."/>
    </xsl:with-param>
  </xsl:call-template>
</xsl:template>
<xsl:template match="xar:set/xar:mlstring"><xsl:text>'</xsl:text><xsl:apply-templates /><xsl:text>'</xsl:text></xsl:template>

</xsl:stylesheet>
