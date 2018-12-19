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
    The value is considered a PHP expression. Similar to the <xar:set> tag, the node between beginning and end tag is PHP space.
    So, if the value is a string, for instance, it needs to be enclosed in single quotes (double quotes are not allowed).
    If the value expression is enclosed in #..#, the # are removed.
    Both the name and the value of the attribute to be created are considered PHP expressions. 
    However, because the XSL transform can only create XML, we need to simply add the PHP during compilation, but add
    the PHP open and close tags at run time through the postProcess method of the BlockLayoutXSLTProcessor object
  -->
  <xsl:choose>
    <xsl:when test="substring(@name,1,1) = '$'">
      <xsl:variable name="newname">
        <xsl:call-template name="replace">
          <xsl:with-param name="source" select="@name" />
          <xsl:with-param name="from" select="'$'" />
          <xsl:with-param name="to" select="'xyzzy$'" />
        </xsl:call-template>
      </xsl:variable>
      
      <xsl:attribute name="{concat($newname,'yzzyx')}">
        <xsl:value-of select="node()" />
      </xsl:attribute>
    </xsl:when>
    <xsl:otherwise>
        <xsl:attribute name="{@name}">
          <xsl:text>xyzzy</xsl:text>
          <xsl:choose>
            <xsl:when test="substring(normalize-space(.),1,1) = '#'">
              <!-- The string starts with #, so we remove that -->
              <xsl:value-of select="substring(normalize-space(.),2,string-length()-1)"/>
            </xsl:when>
            <xsl:otherwise>
              <!-- No start with #, just get the value -->
              <xsl:value-of select="normalize-space(.)" />
            </xsl:otherwise>
          </xsl:choose>
          <xsl:text>yzzyx</xsl:text>
        </xsl:attribute>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

</xsl:stylesheet>