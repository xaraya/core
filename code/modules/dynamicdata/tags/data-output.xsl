<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"
    xmlns:php="http://php.net/xsl"
    exclude-result-prefixes="php xar">

<xsl:template match="xar:data-output">
  <xsl:processing-instruction name="php">
    <xsl:choose>
        <xsl:when test="not(@property)">
          <!-- No prop, get one (the right one, preferably) -->
          <xsl:text>try{sys::import('modules.dynamicdata.class.properties');</xsl:text>
          <xsl:text>$property =&amp; DataPropertyMaster::getProperty(</xsl:text>
          <xsl:call-template name="atts2args">
            <xsl:with-param name="nodeset" select="@*"/>
          </xsl:call-template>
          <xsl:text>);</xsl:text>
          <xsl:text>echo $property-&gt;showOutput(</xsl:text>
          <!-- if we have a field attribute, use just that, otherwise use all attributes -->
          <xsl:choose>
            <xsl:when test="not(@field)">
              <xsl:call-template name="atts2args">
                <xsl:with-param name="nodeset" select="@*[name() != 'property']"/>
              </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
              <xsl:value-of select="@field"/>
            </xsl:otherwise>
          </xsl:choose>
          <xsl:text>);}catch(Exception $e){}</xsl:text>
        </xsl:when>
        <xsl:otherwise>
          <!-- We already had a property object, run its output method -->
          <xsl:text>if (isset(</xsl:text>
          <xsl:value-of select="@property"/>
          <xsl:text>)){</xsl:text>
          <xsl:text>echo </xsl:text>
          <xsl:value-of select="@property"/>
          <xsl:text>-&gt;showOutput(</xsl:text>
          <!-- if we have a field attribute, use just that, otherwise use all attributes -->
          <xsl:choose>
            <xsl:when test="not(@field)">
              <xsl:call-template name="atts2args">
                <xsl:with-param name="nodeset" select="@*[name() != 'property']"/>
              </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
              <xsl:value-of select="@field"/>
            </xsl:otherwise>
          </xsl:choose>
          <xsl:text>);</xsl:text>
          <xsl:text>}</xsl:text>
        </xsl:otherwise>
    </xsl:choose>
  </xsl:processing-instruction>
</xsl:template>

</xsl:stylesheet>