<?xml version="1.0"?>
<!--
  XSLT to create a DDL fragment which represents the same
  information as the ddl XML
-->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" >
  <!-- 
      Import common templates, we use import instead of include so the 
      imported templates get a lower priority than the ones in this file,
      giving the ability here to override the imports
  -->
  <xsl:import href="xml2ddl-base.xsl"/>
  
<!-- Things to do before we start handling elements -->
<xsl:template match="/">
  <xsl:call-template name="topheader">
    <xsl:with-param name="dbname"><xsl:value-of select="/database/@name"/></xsl:with-param>
    <xsl:with-param name="remarks">
    - reference: http://dev.mysql.com/doc/refman/5.0/en/index.html
    - assuming for now we want to drop before create
    </xsl:with-param>
  </xsl:call-template>
  <xsl:text>/* Disable foreign key checks until we're done */</xsl:text>
  <xsl:value-of select="$CR"/>
  <xsl:text>SET FOREIGN_KEY_CHECKS = 0;</xsl:text>
  <xsl:value-of select="$CR"/>
  <xsl:apply-templates/>
  <xsl:text>SET FOREIGN_KEY_CHECKS = 1;</xsl:text>
  <xsl:value-of select="$CR"/>
</xsl:template>

<xsl:template match="table">
  <xsl:call-template name="dynheader"/>
  <xsl:text>DROP TABLE IF EXISTS </xsl:text><xsl:value-of select="@name"/><xsl:text>;</xsl:text>
  <xsl:value-of select="$CR"/>
  <xsl:text>CREATE TABLE </xsl:text><xsl:value-of select="@name"/> 
  <xsl:text>(</xsl:text>
  <xsl:apply-templates select="column"/>
  <xsl:text>)</xsl:text>
  <xsl:if test="@description != ''">
    <xsl:text>COMMENT='</xsl:text>
    <xsl:value-of select="@description"/>
    <xsl:text>'</xsl:text>
  </xsl:if>
  <xsl:text>;</xsl:text>
  <xsl:value-of select="$CR"/>
  <xsl:apply-templates select="primary"/>
  <xsl:apply-templates select="index"/>
</xsl:template>

<xsl:template match="table/column">
  <xsl:text>  </xsl:text>
  <xsl:value-of select="@name"/><xsl:text> </xsl:text>
  <xsl:choose>
    <xsl:when test="@type = 'LONGVARCHAR'">
      <xsl:text>MEDIUMTEXT</xsl:text>
    </xsl:when>
    <xsl:otherwise>
      <xsl:value-of select="@type"/>
    </xsl:otherwise>
  </xsl:choose>
  <xsl:if test="@size != ''">(<xsl:value-of select="@size"/>)<xsl:text> </xsl:text></xsl:if>
  <xsl:if test="@required ='true'"> NOT NULL</xsl:if>
  <xsl:if test="@default != ''"> DEFAULT '<xsl:value-of select="@default"/>'</xsl:if>
  <xsl:if test="@autoIncrement ='true'"> AUTO_INCREMENT</xsl:if>
  <xsl:if test="position() != last()"><xsl:text>,</xsl:text></xsl:if>
  <xsl:value-of select="$CR"/></xsl:template>
</xsl:stylesheet>