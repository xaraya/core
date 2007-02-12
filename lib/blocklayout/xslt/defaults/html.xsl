<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <!--
    Defaults for html type stuff
  -->

  <!-- For now, dont resolve inline CSS -->
  <xsl:template match="style/text()">
    <xsl:apply-imports />
  </xsl:template>

  <!--
      Problematic elements

      - empty div elements bork everything, so first, leave their spacing alone
      which doesnt influence correctness, but saves a whole lot of trouble.
      - empty script element work in safari, but not in FF
  -->
  <xsl:template match="div|script">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
      <xsl:if test="not(node()[not(self::comment())])">
        <xsl:comment>Empty tag workaround for <xsl:value-of select="name()"/> tag</xsl:comment>
      </xsl:if>
    </xsl:copy>
  </xsl:template>
</xsl:stylesheet>