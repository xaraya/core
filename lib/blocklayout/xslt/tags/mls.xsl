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
    <xsl:text>echo xarML(</xsl:text>
    <xsl:apply-templates/>
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
  <xsl:text>xarML(</xsl:text>
  <xsl:apply-templates/>
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
  <xsl:text>.'#(</xsl:text>
  <xsl:number from="xar:ml" level="any"/>
  <xsl:text>)'.</xsl:text>
</xsl:template>



<!--
  Matching the old xar:mlvar tag does nothing, but
-->
<xsl:template match="xar:mlvar" />

<!--
  we pick up its value/expression to add to the PHP xarML function as a param by explicitly calling this template
-->
<xsl:template name="mlvar">
  <xsl:call-template name="resolvePHP">
    <xsl:with-param name="expr">
      <xsl:value-of select="normalize-space(.)"/>
    </xsl:with-param>
  </xsl:call-template>
</xsl:template>

<!-- mlstring forces translation for now -->
<xsl:template match="xar:mlstring">
  <xsl:call-template name="translateText">
    <xsl:with-param name="expr" select="."/>
  </xsl:call-template>
</xsl:template>

<!-- mlstring inside ml just needs to resolve the text node -->
<xsl:template match="xar:ml/xar:mlstring">
  <xsl:call-template name="resolveText">
    <xsl:with-param name="expr" select="."/>
  </xsl:call-template>
</xsl:template>

<!-- this case is covered bythe previous one
<xsl:template match="xar:set/xar:ml/xar:mlstring">
  <xsl:call-template name="replace">
    <xsl:with-param name="source" select="."/>
  </xsl:call-template>
</xsl:template>
-->

<!-- mlstring inside set just needs to resolve the text node -->
<xsl:template match="xar:set/xar:mlstring">
  <xsl:text>xarML(</xsl:text>
  <xsl:call-template name="resolveText">
    <xsl:with-param name="expr" select="."/>
  </xsl:call-template>
  <xsl:text>)</xsl:text>
</xsl:template>

</xsl:stylesheet>
