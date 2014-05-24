<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"
    xmlns:php="http://php.net/xsl"
    exclude-result-prefixes="php xar">

<xsl:template match="xar:data-label">
  <xsl:processing-instruction name="php">
    <xsl:choose>
      <xsl:when test="not(@object)">
        <!-- No object -->
        <xsl:choose>
          <xsl:when test="not(@property)">
            <!-- No property either -->
            <xsl:choose>
              <xsl:when test="not(@label)">
                <!-- Doh, no label either -->
                <xsl:text>echo "I need an object or a property or a label attribute";</xsl:text>
              </xsl:when>
              <xsl:otherwise>
                <!-- Ok, we have nothin, but a label -->
                <xsl:text>echo xarTplProperty('dynamicdata','label','showoutput',array('label'=&gt;</xsl:text>
                <xsl:choose>
                  <xsl:when test="starts-with(@label,'$')">
                    <xsl:value-of select="@label"/>
                  </xsl:when>
                  <xsl:otherwise>
                    <xsl:text>'</xsl:text>
                    <xsl:value-of select="@label"/>
                    <xsl:text>'</xsl:text>
                  </xsl:otherwise>
                </xsl:choose>
                <xsl:if test="@for">
                  <xsl:text>,'for'=&gt;'</xsl:text>
                  <xsl:value-of select="@for"/>
                  <xsl:text>'</xsl:text>
                </xsl:if>
                <xsl:if test="@title">
                  <xsl:text>,'title'=&gt;</xsl:text>
                  <xsl:choose>
                    <xsl:when test="starts-with(@title,'$')">
                      <xsl:value-of select="@title"/>
                    </xsl:when>
                    <xsl:otherwise>
                      <xsl:text>'</xsl:text>
                      <xsl:value-of select="@title"/>
                      <xsl:text>'</xsl:text>
                    </xsl:otherwise>
                  </xsl:choose>
                </xsl:if>
                <xsl:text>),'label');</xsl:text>
              </xsl:otherwise>
            </xsl:choose>
          </xsl:when>
          <xsl:otherwise>
            <!-- We have a property -->
            <xsl:text>if (isset(</xsl:text>
            <xsl:value-of select="@property"/>
            <xsl:text>)){</xsl:text>
            <xsl:text>echo </xsl:text><xsl:value-of select="@property"/>
            <xsl:text>-&gt;showLabel(</xsl:text>
            <xsl:call-template name="atts2args">
              <xsl:with-param name="nodeset" select="@*"/>
            </xsl:call-template>
            <xsl:text>);</xsl:text>
            <xsl:text>}</xsl:text>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:when>
      <xsl:otherwise>
        <!-- If we have an object, throw out its label -->
        <xsl:text>echo xarVarPrepForDisplay(</xsl:text>
        <xsl:value-of select="@object"/><xsl:text>-&gt;label);</xsl:text>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:processing-instruction>
</xsl:template>

</xsl:stylesheet>