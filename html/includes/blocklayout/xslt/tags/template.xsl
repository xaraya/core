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
  <xsl:choose>
    <!-- If the template tag does not contain anything, treate as in 1.x -->
    <xsl:when test="not(node())">
      <xsl:processing-instruction name="php">
        <xsl:text>echo </xsl:text>
        <xsl:choose>
          <xsl:when test="@type='theme'">
            <xsl:text>xarTpl_includeThemeTemplate('</xsl:text>
            <xsl:value-of select="@file"/>
            <xsl:text>',$_bl_data);</xsl:text>
          </xsl:when>
          <xsl:when test="@type='system'">
            <!-- The name is to be interpreted relative to the file we're parsing now -->
            <xsl:text>xarTplFile('</xsl:text>
            <xsl:value-of select="$bl_dirname"/><xsl:text>/</xsl:text><xsl:value-of select="@file"/>
            <xsl:text>',$_bl_data );</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <xsl:text>xarTpl_includeModuleTemplate(</xsl:text>
            <xsl:choose>
              <xsl:when test="@module != ''">
                <xsl:text>'</xsl:text>
                <xsl:value-of select="@module"/>
                <xsl:text>'</xsl:text>
              </xsl:when>
              <xsl:otherwise>
                <xsl:text>$_bl_module_name</xsl:text>
              </xsl:otherwise>
            </xsl:choose>
            <xsl:text>, '</xsl:text>
            <xsl:value-of select="@file"/>
            <xsl:text>',$_bl_data);</xsl:text>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:processing-instruction>
    </xsl:when>
    <xsl:otherwise>
      <!-- It's the root tag of a template file, no need to do anything yet -->
      <xsl:apply-templates/>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>
</xsl:stylesheet>
