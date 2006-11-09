<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"
    xmlns:php="http://php.net/xsl"
    exclude-result-prefixes="php xar">

<xsl:template match="xar:ml">
  <xsl:processing-instruction name="php">
    <xsl:text>echo xarML('</xsl:text>
    <xsl:apply-templates/>
    <xsl:text>');</xsl:text>
  </xsl:processing-instruction>
</xsl:template>

<xsl:template match="xar:set/xar:ml">
  <xsl:text>xarML('</xsl:text>
  <xsl:apply-templates/>
  <xsl:text>');</xsl:text>
</xsl:template>

<!--
  xar:mlstring is deprecated, we just pass on what's inside it.
  if it is below a xar:set we add single quotes around what it produced.
  These will disappear, as text nodes will be passed onto MLS by default
  later on
-->
<xsl:template match="xar:mlstring">
  <xsl:apply-templates />
</xsl:template>

<!--
<xsl:template match="xar:ml/xar:mlstring">
  <xsl:apply-templates />
</xsl:template>
-->

<xsl:template match="xar:set/xar:ml/xar:mlstring">
  <xsl:apply-templates />
</xsl:template>

<xsl:template match="xar:set/xar:mlstring">
  <xsl:text>'</xsl:text>
  <xsl:apply-templates />
  <xsl:text>'</xsl:text>
</xsl:template>

<!-- Not handled anymore -->
<xsl:template match="xar:mlvar"/>

</xsl:stylesheet>
