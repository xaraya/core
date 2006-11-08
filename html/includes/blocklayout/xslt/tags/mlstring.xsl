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
  xar:mlstring is deprecated, we just pass on what's insided it.
  if it is below a xar:set we add single quotes around what it produced.
  These will disappear, as text nodes will be passed onto MLS by default
  later on
-->

<xsl:template match="xar:mlstring|xar:ml/xar:mlstring">
  <xsl:apply-templates />
</xsl:template>

<xsl:template match="xar:set/xar:mlstring|xar:set/xar:ml/xar:mlstring">
  <xsl:text>'</xsl:text>
  <xsl:apply-templates />
  <xsl:text>'</xsl:text>
</xsl:template>

</xsl:stylesheet>
