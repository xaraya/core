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
    <xsl:text>');</xsl:text>
  </xsl:processing-instruction>
</xsl:template>

<!--
  xar:ml as child of xar:set is already in php mode, no need to
  do it again (TEMP, ugly)
-->
<xsl:template match="xar:set/xar:ml">
  <xsl:text>xarML('</xsl:text>
  <xsl:apply-templates/>
  <xsl:text>');</xsl:text>
</xsl:template>

<!--
  xar:var tags as children of xar:ml need to get placeholders

-->
<xsl:template match="xar:ml//xar:var">
  <xsl:text>#(</xsl:text>
  <xsl:number from="xar:ml" level="any"/>
  <xsl:text>)#</xsl:text>
</xsl:template>



<!-- Not handled anymore, ignore closed mlvar, pass on content of mlstring -->
<xsl:template match="xar:mlvar"/>
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
