<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"
    xmlns:php="http://php.net/xsl"
    exclude-result-prefixes="php xar">

<xsl:template name="xar-template" match="xar:template">
  <xsl:variable name="subdata">
    <xsl:choose>
      <xsl:when test="not(@subdata)">
        <xsl:text>$_bl_data</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="@subdata"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:variable>

  <xsl:choose>
    <!-- If the template tag does not contain anything, treat it as in 1.x -->
    <!--
      Redundant space will be compressed, so watch out here for nodes which
      just contain space-type content. There's no way to test that anymore once
      we get here.
    -->
    <xsl:when test="not(node()) and @file">
      <xsl:processing-instruction name="php">
        <xsl:text>echo </xsl:text>
        <xsl:choose>
          <xsl:when test="@type='theme'">
            <xsl:text>xarTpl_includeThemeTemplate('</xsl:text>
            <xsl:value-of select="@file"/>
            <xsl:text>',</xsl:text>
            <xsl:value-of select="$subdata"/>
            <xsl:text>);</xsl:text>
          </xsl:when>
          <xsl:when test="@type='system'">
            <!-- The name is to be interpreted relative to the file we're parsing now -->
            <xsl:text>xarTplFile('</xsl:text>
            <xsl:value-of select="$bl_dirname"/><xsl:text>/</xsl:text><xsl:value-of select="@file"/>
            <xsl:text>',</xsl:text>
            <xsl:call-template name="resolvePHP">
              <xsl:with-param name="expr" select="$subdata"/>
            </xsl:call-template>
            <xsl:text>);</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <xsl:text>xarTpl_includeModuleTemplate(</xsl:text>
            <xsl:choose>
              <xsl:when test="@module != ''">
                <xsl:call-template name="resolvePHP">
                  <xsl:with-param name="expr" select="@module"/>
                </xsl:call-template>
              </xsl:when>
              <xsl:otherwise>
                <xsl:choose>
                  <xsl:when test="string-length(substring-before(substring-after($bl_dirname,'modules/'),'/')) &gt; 0">
                    <xsl:text>'</xsl:text>
                    <xsl:value-of select="substring-before(substring-after($bl_dirname,'modules/'),'/')"/>
                    <xsl:text>'</xsl:text>
                  </xsl:when>
                  <xsl:otherwise>
                    <xsl:text>xarModGetName()</xsl:text>
                  </xsl:otherwise>
                </xsl:choose>
              </xsl:otherwise>
            </xsl:choose>
            <xsl:text>, "</xsl:text>
            <xsl:call-template name="resolvePHP">
              <xsl:with-param name="expr" select="@file"/>
            </xsl:call-template>
            <xsl:text>",</xsl:text>
            <xsl:call-template name="resolvePHP">
              <xsl:with-param name="expr" select="$subdata"/>
            </xsl:call-template>
            <xsl:text>);</xsl:text>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:processing-instruction>
    </xsl:when>
    <xsl:otherwise>
      <!--
        It's the root tag of a template file, or placed in block form inline
        no need to do anything yet, but process the children in it
      -->
      <xsl:apply-templates/>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>
</xsl:stylesheet>
