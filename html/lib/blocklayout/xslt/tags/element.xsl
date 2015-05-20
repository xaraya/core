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
    This tag only allows a name attribute that is a string (for now)
    Need to look at preprocess and postprocess in xsltransformer.php when trying to enhance further
  -->
  <xsl:element name="{@name}">
    <xsl:apply-templates />
  </xsl:element>
</xsl:template>

<xsl:template match="xar:attribute">
  <!--
    This tag allows a name attribute that is either a string or a $var
  -->
  <xsl:choose>
    <xsl:when test="substring(@name,1,1) = '$'">
      <xsl:variable name="newname">
        <xsl:call-template name="replace">
          <xsl:with-param name="source" select="@name" />
          <xsl:with-param name="from" select="'$'" />
          <xsl:with-param name="to" select="'xyzzy'" />
        </xsl:call-template>
      </xsl:variable>
      
      <xsl:attribute name="{concat($newname,'yzzyx')}">
        <xsl:value-of select="node()" />
      </xsl:attribute>
    </xsl:when>
    <xsl:otherwise>
      <xsl:attribute name="{@name}">
        <xsl:value-of select="node()" />
      </xsl:attribute>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

</xsl:stylesheet>
