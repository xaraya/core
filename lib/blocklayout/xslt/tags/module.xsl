<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0" 
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"   
    xmlns:php="http://php.net/xsl" 
    exclude-result-prefixes="php xar">

<xsl:template match="xar:module">
  <xsl:processing-instruction name="php">
    <xsl:choose>
      <xsl:when test="string-length(@module) = 0">
        <!-- Obviously this sucks -->
        <xsl:text>echo $_bl_mainModuleOutput;</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <!-- module attribute has a value -->
        <xsl:text>echo xarModFunc("</xsl:text>
        <xsl:call-template name="resolvePHP">
          <xsl:with-param name="expr" select="@module"/>
        </xsl:call-template>
        <xsl:text>","</xsl:text>
        <xsl:choose>
          <xsl:when test="string-length(@type) = 0">
            <xsl:text>user</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <xsl:call-template name="resolvePHP">
              <xsl:with-param name="expr" select="@type"/>
            </xsl:call-template>
          </xsl:otherwise>
        </xsl:choose>
        <xsl:text>","</xsl:text>
        <xsl:choose>
          <xsl:when test="string-length(@func) = 0">
            <xsl:text>main</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <xsl:call-template name="resolvePHP">
              <xsl:with-param name="expr" select="@func"/>
            </xsl:call-template>
          </xsl:otherwise>
        </xsl:choose>
        <!-- Add all other attributes -->
        <xsl:text>",array(</xsl:text>
        <xsl:for-each select="@*[name()!='module' and name()!='func' and name()!='type']">
          <xsl:text>'</xsl:text><xsl:value-of select="name()"/><xsl:text>'</xsl:text>
          <xsl:text disable-output-escaping="yes">=&gt;'</xsl:text>
          <xsl:value-of select="."/><xsl:text>',</xsl:text>
        </xsl:for-each>
        <xsl:text>));</xsl:text>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:processing-instruction>
</xsl:template>
    
</xsl:stylesheet>