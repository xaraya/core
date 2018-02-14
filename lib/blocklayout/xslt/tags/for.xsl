<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0" 
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"   
    xmlns:php="http://php.net/xsl" 
    exclude-result-prefixes="php xar">

<xsl:template match="xar:for">
  <xsl:processing-instruction name="php">
    <xsl:text>for(</xsl:text>
    <!-- 
      The start attribute should resolve to a number 
      It's usually something like : $i = 1
    -->
    <xsl:value-of select="@start"/>
    <xsl:text>;</xsl:text>
    <!--
      Invariant for the for loop, it should resolve to
      a boolean. It's usually something like $i lt $someMax
    -->
    <xsl:call-template name="resolvePHP">
      <xsl:with-param name="expr" select="@test"/>
    </xsl:call-template>
    <xsl:text>;</xsl:text>
    <!--
      Operation to perform after each iteration. It should
      be a valid php expression, changing the conditions for the @test
    -->
    <xsl:value-of select="@iter"/>
    <xsl:text>) {&nl;</xsl:text>
  </xsl:processing-instruction>
  
  <xsl:apply-templates />
  
  <xsl:processing-instruction name="php">
    <xsl:text>}&nl;</xsl:text>
  </xsl:processing-instruction>
</xsl:template>
</xsl:stylesheet>