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
  Spec:
    *   Mandatory attributes: either 'instance' or ('module' and 'type')
    *   Optional attributes: 'title', 'template', 'name', 'state'
    *   Other attributes: all remaining, collected into an array
-->

<xsl:template match="xar:block">
  <xsl:processing-instruction name="php">
    <xsl:text>echo </xsl:text>
    <xsl:call-template name="block_code"/>
    <xsl:text>;</xsl:text>
  </xsl:processing-instruction>
</xsl:template>

<xsl:template match="xar:set/xar:block">
    <xsl:call-template name="block_code"/>
</xsl:template>

<xsl:template name="block_code">
  <xsl:choose>
    <xsl:when test="@instance or (@module and @type)">
      <xsl:text> xarBlock_renderBlock(array(&nl;</xsl:text>
      <xsl:text>'instance' =&gt; "</xsl:text><xsl:value-of select="@instance"/><xsl:text>",&nl;</xsl:text>
      <xsl:text>'module'   =&gt; "</xsl:text><xsl:value-of select="@module"/><xsl:text>",&nl;</xsl:text>
      <xsl:text>'type'     =&gt; "</xsl:text><xsl:value-of select="@type"/><xsl:text>",&nl;</xsl:text>
      <xsl:text>'name'     =&gt; "</xsl:text><xsl:value-of select="@name"/><xsl:text>",&nl;</xsl:text>
      <xsl:text>'title'    =&gt; "</xsl:text><xsl:value-of select="@title"/><xsl:text>",&nl;</xsl:text>
      <xsl:text>'template' =&gt; "</xsl:text><xsl:value-of select="@template"/><xsl:text>",&nl;</xsl:text>
      <xsl:text>'state'    =&gt; "</xsl:text><xsl:value-of select="@state"/><xsl:text>",&nl;</xsl:text>
      <!-- If ancestrial blockgroup tags set a template attribute, use that here -->
      <xsl:text>'box_template' =&gt; ('</xsl:text>
      <xsl:value-of select="ancestor::xar:blockgroup[@template]"/>
      <xsl:text>'),</xsl:text>
      <xsl:text>'content'  =&gt; </xsl:text>
      <!-- Add the rest of the attributes -->
      <xsl:call-template name="atts2args">
        <xsl:with-param
          name="nodeset"
          select="@*[name() != 'instance' and name() != 'module' and name() != 'type' and name() != 'name' and  name() != 'title' and name() != 'template' and name() != 'state'] "/>
      </xsl:call-template>
      <xsl:text>))</xsl:text>
    </xsl:when>
    <xsl:otherwise>
        <!-- Error out? -->
        <xsl:text>'xar:block attribute error'</xsl:text>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

</xsl:stylesheet>
