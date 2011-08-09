<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <!--
    Defaults for html type stuff
  -->

  <!-- For now, dont resolve inline CSS 
  <xsl:template match="style/text()">
    <xsl:apply-imports />
  </xsl:template>
-->

  <!-- Stuff in pre tags should not be translated -->
  <xsl:template match="pre/text()">
    <xsl:value-of select="."/>
  </xsl:template>
  <xsl:template match="textarea/text()">
    <xsl:value-of select="."/>
  </xsl:template>

  <!-- Stuff in script and style tags should not be translated -->
  <xsl:template match="script/text()">
    <xsl:value-of select="."/>
  </xsl:template>
  <xsl:template match="style/text()">
    <xsl:value-of select="."/>
  </xsl:template>

  <!--
      Problematic elements

      - empty div elements bork everything, so first, leave their spacing alone
      which doesnt influence correctness, but saves a whole lot of trouble.
      - empty script element works in safari, but not in FF
  -->
  <xsl:template match="div|script|iframe">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
      <xsl:if test="not(node()[not(self::comment())])">
        <xsl:comment>Empty tag workaround for <xsl:value-of select="name()"/> tag</xsl:comment>
      </xsl:if>
    </xsl:copy>
  </xsl:template>
  
  <!--
      In the case of textareas the fix above shows the comment in the textarea
      But a comment with just a blank gives the result we want
  -->
  <xsl:template match="textarea">
    <xsl:copy>
      <xsl:apply-templates select="@*|node()"/>
      <xsl:if test="not(node()[not(self::comment())])">
        <xsl:comment> </xsl:comment>
      </xsl:if>
    </xsl:copy>
  </xsl:template>
</xsl:stylesheet>