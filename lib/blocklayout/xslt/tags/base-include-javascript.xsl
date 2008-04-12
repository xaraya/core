<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0" 
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"   
    xmlns:php="http://php.net/xsl" 
    exclude-result-prefixes="php xar">

<xsl:template match="xar:base-include-javascript">
  <!-- Make sure we have sensible values -->
  <xsl:variable name="module">
    <xsl:choose>
      <xsl:when test="not(@module)">
        <xsl:text>xarMod::getName()</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <xsl:text>'</xsl:text>
        <xsl:value-of select="@module"/>
        <xsl:text>'</xsl:text>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:variable>
  
  <xsl:variable name="position">
    <xsl:choose>
      <xsl:when test="not(@position)">
        <xsl:text>head</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="@position"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:variable>
  
  <xsl:processing-instruction name="php">
    <xsl:text>&nl;</xsl:text>
    <xsl:choose>
      <xsl:when test="@code and @type">
        <xsl:text>xarTplAddJavaScript('</xsl:text>
        <xsl:value-of select="$position"/>
        <xsl:text>','</xsl:text>
        <xsl:value-of select="@type"/>
        <xsl:text>',"</xsl:text>
        <xsl:value-of select="@code"/>
        <xsl:text>");&nl;</xsl:text>
      </xsl:when>
      <xsl:when test="string-length(@filename) &gt; 0">
        <xsl:text>xarModApiFunc('base','javascript','modulefile',array('module'=&gt;</xsl:text>
        <xsl:value-of select="$module"/>
        <xsl:text>,'filename'=&gt;'</xsl:text>
        <xsl:value-of select="@filename"/>
        <xsl:text>','position'=&gt;'</xsl:text>
        <xsl:value-of select="$position"/>
        <xsl:text>')); </xsl:text>
      </xsl:when>
    </xsl:choose>
  </xsl:processing-instruction>
</xsl:template>

</xsl:stylesheet>
