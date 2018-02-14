<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"
    xmlns:php="http://php.net/xsl"
    exclude-result-prefixes="php xar">

<xsl:template match="xar:data-view">
  <xsl:processing-instruction name="php">
    <xsl:choose>
      <!-- No object or objectname? Generate ourselves then -->
      <xsl:when test="not(@object) and not(@objectname)">
        <xsl:text>echo xarMod::apiFunc('dynamicdata','user','showview',</xsl:text>
        <!-- Dump the attributes in an array for the function call -->
        <xsl:call-template name="atts2args">
          <xsl:with-param name="nodeset" select="@*"/>
        </xsl:call-template>
        <xsl:text>);</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <xsl:choose>
          <xsl:when test="@object != ''">
            <!-- Use the object attribute -->
            <xsl:text>echo </xsl:text><xsl:value-of select="@object"/>
          </xsl:when>
          <xsl:when test="@objectname != ''">
            <!-- This a string. we assume it's an object name -->
            <xsl:text>sys::import('modules.dynamicdata.class.objects.master');</xsl:text>
            <xsl:text>$__</xsl:text>
            <xsl:value-of select="@objectname"/>
            <xsl:text>=DataObjectMaster::getObjectList(array('name'=>'</xsl:text>
            <xsl:value-of select="@objectname"/>
            <xsl:text>'));</xsl:text>
            <xsl:text>$__</xsl:text>
            <xsl:value-of select="@objectname"/>
            <xsl:text>-&gt;getItems(</xsl:text>
            <xsl:call-template name="atts2args">
              <xsl:with-param name="nodeset" select="@*[name() != 'objectname']"/>
            </xsl:call-template>
            <xsl:text>);</xsl:text>
            <xsl:text>echo </xsl:text>
            <xsl:text>$__</xsl:text>
            <xsl:value-of select="@objectname"/>
          </xsl:when>
        </xsl:choose>
        <xsl:text>-&gt;showView(</xsl:text>
        <!-- Dump the attributes in an array for the function call, but skip the object and objectname attributes -->
        <xsl:call-template name="atts2args">
          <xsl:with-param name="nodeset" select="@*[name() != 'object' and name() != 'objectname']"/>
        </xsl:call-template>
        <xsl:text>);</xsl:text>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:processing-instruction>
</xsl:template>

</xsl:stylesheet>