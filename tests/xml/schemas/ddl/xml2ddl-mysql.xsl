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
  
  <!-- DDL is no XML -->
  <xsl:output method="text" />
  <xsl:strip-space elements="*"/>
  
<!-- 
    We probably want to specify parameters at some point like:
    - vendor      - (mysql|sqlite|pgsql|mssql|oracle|other)
    - drop4create - drop tables before creating them
    - createdb    - create the database too
    - tableprefix - self explanatory
    - etc.
-->

<xsl:template match="database">
  <xsl:call-template name="topheader"/>
CREATE DATABASE <xsl:value-of select="@name"/>;
<xsl:apply-templates />
</xsl:template>

<xsl:template match="table">
  <xsl:call-template name="dynheader"/>
CREATE TABLE <xsl:value-of select="@name"/> 
(
<xsl:apply-templates select="column"/>);
<xsl:apply-templates select="unique"/>
<xsl:apply-templates select="index"/>
</xsl:template>

<xsl:template match="table/column">
<xsl:text>  </xsl:text>
<xsl:value-of select="@name"/><xsl:text> </xsl:text>
<xsl:value-of select="@type"/>(<xsl:value-of select="@size"/>)<xsl:text> </xsl:text>
<xsl:if test="@required ='true'"> NOT NULL</xsl:if>
<xsl:if test="@default != ''"> DEFAULT '<xsl:value-of select="@default"/>'</xsl:if>
<xsl:if test="@autoIncrement ='true'"> AUTO_INCREMENT</xsl:if>
<xsl:if test="@primaryKey = 'true'"> PRIMARY KEY</xsl:if>
<xsl:if test="position() != last()"><xsl:text>,</xsl:text></xsl:if>
<xsl:value-of select="$CR"/></xsl:template>

<xsl:template match="table/index">
<xsl:text>CREATE </xsl:text>
<xsl:if test="@type='unique'"><xsl:text>UNIQUE </xsl:text></xsl:if>
<xsl:text>INDEX </xsl:text><xsl:value-of select="@name"/> ON <xsl:value-of select="../@name"/>(<xsl:apply-templates/>);
</xsl:template>

<xsl:template match="table/index/index-column">
<xsl:value-of select="@name"/>
<xsl:if test="position() != last()"><xsl:text>,</xsl:text></xsl:if></xsl:template>

</xsl:stylesheet>