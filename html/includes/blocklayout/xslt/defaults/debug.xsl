<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

  <!--
    Utility template to be able to generate output when something is wrong, but
    not terribly:
    - Any xar tag we dont match, we highlight in the output
    - When a typo was made for example in attributes or something like that
    @todo: best way i could come up with to do this doctype agnostic, anything better?
  -->
  <xsl:template name="oops">
    <xsl:param name="label" select="'UNKNOWN ERROR'"/>
    <!-- Insert a CDATA section preceded by a 'weird' symbol -->
    <!--
      x2707 is the 'radiation symbol' if it displays, you're config is good,
      otherwise you'll have to settle for a ? or an empty square or something
      like that
    -->
    <xsl:text disable-output-escaping="yes">&#x2707;
&lt;![CDATA[</xsl:text>
    <xsl:value-of select="$label"/>
    <xsl:text>: --- </xsl:text>
    <xsl:value-of select="name()"/>
    <xsl:text> </xsl:text>
    <xsl:for-each select="@*">
      <xsl:value-of select="name()"/>
      <xsl:text>="</xsl:text>
      <xsl:value-of select="."/>
      <xsl:text>" </xsl:text>
    </xsl:for-each>
    <xsl:text>---</xsl:text>
    <xsl:text disable-output-escaping="yes"> ]]&gt; </xsl:text>
  </xsl:template>

  <xsl:template match="xar:*">
    <xsl:param name="label" select="'MISSING TAG IMPLEMENTATION'"/>
    <!-- Insert a CDATA section preceded by a 'weird' symbol -->
    <!--
      x2707 is the 'radiation symbol' if it displays, you're config is good,
      otherwise you'll have to settle for a ? or an empty square or something
      like that
    -->
    <xsl:text disable-output-escaping="yes">&#x2707;
&lt;![CDATA[</xsl:text>
    <xsl:value-of select="$label"/>
    <xsl:text>: </xsl:text>
    <xsl:apply-imports />
    <xsl:text disable-output-escaping="yes"> ]]&gt; </xsl:text>
  </xsl:template>

  <xsl:template match="xar:set/xar:*">
    <xsl:param name="label" select="'MISSING TAG IMPLEMENTATION'"/>
    <!-- Insert a CDATA section preceded by a 'weird' symbol -->
    <!--
      x2707 is the 'radiation symbol' if it displays, you're config is good,
      otherwise you'll have to settle for a ? or an empty square or something
      like that
    -->
    <xsl:text disable-output-escaping="yes">'&#x2707;</xsl:text>
    <xsl:call-template name="replace">
      <xsl:with-param name="source" select="$label"/>
    </xsl:call-template>
    <xsl:text>: </xsl:text><xsl:value-of select="name()"/>
    <xsl:apply-imports />
    <xsl:text>'</xsl:text>
  </xsl:template>
</xsl:stylesheet>