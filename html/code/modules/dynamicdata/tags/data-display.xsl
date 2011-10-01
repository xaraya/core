<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"
    xmlns:php="http://php.net/xsl"
    exclude-result-prefixes="php xar">

<xsl:template match="xar:data-display">
  <xsl:processing-instruction name="php">
      <xsl:choose>
        <xsl:when test="not(@object)">
          <!-- No object passed in -->
          <xsl:text>echo xarMod::apiFunc('dynamicdata','user','showdisplay',</xsl:text>
          <xsl:choose>
            <xsl:when test="not(@definition)">
              <!-- No direct definition, use the attributes -->
              <xsl:call-template name="atts2args">
                <xsl:with-param name="nodeset" select="@*"/>
              </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
              <xsl:value-of select="@definition"/>
            </xsl:otherwise>
          </xsl:choose>
          <xsl:text>);</xsl:text>
        </xsl:when>
        <xsl:otherwise>
          <xsl:choose>
            <xsl:when test="substring(@object,1,1) = '$'">
              <!-- This a variable. we assume it's an object -->
              <!-- Use the object attribute -->
              <xsl:text>echo </xsl:text><xsl:value-of select="@object"/>
            </xsl:when>
            <xsl:otherwise>
              <!-- This a string. we assume it's an object name -->
              <xsl:text>sys::import('modules.dynamicdata.class.objects.master');</xsl:text>
              <xsl:text>$__</xsl:text>
              <xsl:value-of select="@object"/>
              <xsl:text>=DataObjectMaster::getObject(array('name'=>'</xsl:text>
              <xsl:value-of select="@object"/>
              <xsl:text>'));</xsl:text>
              <xsl:text>echo </xsl:text>
              <xsl:text>$__</xsl:text>
              <xsl:value-of select="@object"/>
            </xsl:otherwise>
          </xsl:choose>
          <xsl:text>-&gt;showDisplay(</xsl:text>
          <xsl:call-template name="atts2args">
            <xsl:with-param name="nodeset" select="@*[name() != 'object']"/>
          </xsl:call-template>
          <xsl:text>);</xsl:text>
        </xsl:otherwise>
      </xsl:choose>
  </xsl:processing-instruction>
</xsl:template>

</xsl:stylesheet>