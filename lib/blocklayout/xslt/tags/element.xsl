<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"
    xmlns:php="http://php.net/xsl"
    exclude-result-prefixes="php xar">

<xsl:template match="xar:element">
  <!--
    This is less trivial than it sounds.
    We can't use xsl:element just now, because we postprocess our expressions
    and <#$sometag#> just wont do inside the xml stream, so.....
    @todo revisit later
  -->
  <xsl:text disable-output-escaping="yes">&lt;</xsl:text><xsl:value-of select="@name"/><xsl:text disable-output-escaping="yes">&gt;</xsl:text>
  <xsl:apply-templates />
  <xsl:text disable-output-escaping="yes">&lt;/</xsl:text><xsl:value-of select="@name"/><xsl:text disable-output-escaping="yes">&gt;</xsl:text>
</xsl:template>
</xsl:stylesheet>
