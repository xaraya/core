<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <!--
    Defaults for bootstrap stuff
  -->

  <!--
      Do not close these tags
  -->
  <xsl:template match="span|i">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
      <xsl:if test="not(node()[not(self::comment())])">
        <xsl:comment>Empty tag workaround for <xsl:value-of select="name()"/> tag</xsl:comment>
      </xsl:if>
    </xsl:copy>
  </xsl:template>
  
</xsl:stylesheet>