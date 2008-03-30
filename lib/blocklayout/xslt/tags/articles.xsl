<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"
    xmlns:php="http://php.net/xsl"
    exclude-result-prefixes="php xar">

  <xsl:template match="xar:articles-field">
    <xsl:processing-instruction name="php">
        <xsl:choose>
          <xsl:when test="@definition">
            <xsl:text>echo xarModAPIFunc('articles','user','showfield',</xsl:text>
              <xsl:value-of select="@definition"/>
            <xsl:text>);</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <xsl:text>echo xarModAPIFunc('articles','user','showfield',</xsl:text>
              <xsl:call-template name="atts2args">
                <xsl:with-param name="nodeset" select="@*"/>
              </xsl:call-template>
            <xsl:text>);</xsl:text>
          </xsl:otherwise>
        </xsl:choose>
    </xsl:processing-instruction>
  </xsl:template>

</xsl:stylesheet>

