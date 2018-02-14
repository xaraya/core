<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0" 
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"   
    xmlns:php="http://php.net/xsl" 
    exclude-result-prefixes="php xar">

    <xsl:template match="xar:data-filterform">
        <xsl:processing-instruction name="php">
          <xsl:choose>
            <xsl:when test="not(@object)">
              <!-- No object passed in -->
              <xsl:text>echo xarMod::apiFunc('dynamicdata','admin','showfilterform',</xsl:text>
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
              <!-- Use the object attribute -->
              <xsl:text>echo </xsl:text><xsl:value-of select="@object"/>
              <xsl:text>-&gt;showFilterForm(</xsl:text>
              <xsl:call-template name="atts2args">
                <xsl:with-param name="nodeset" select="@*[name() != 'object']"/>
              </xsl:call-template>
              <xsl:text>);</xsl:text>
            </xsl:otherwise>
          </xsl:choose>
        </xsl:processing-instruction>
    </xsl:template>

</xsl:stylesheet>