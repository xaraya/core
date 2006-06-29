<?xml version="1.0"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<!--
  XSLT to create a DDL fragment which represents the same
  information as the ddl XML
-->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" >
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
#---------------------------------------------------------------------------
# Generated schema
# Database: <xsl:value-of select="@name"/>
# Date    : 
# 
#---------------------------------------------------------------------------
CREATE DATABASE <xsl:value-of select="@name"/>;
<xsl:apply-templates />
</xsl:template>

<xsl:template match="table">
#---------------------------------------------------------------------------
#  Table: <xsl:value-of select="@name" />
#---------------------------------------------------------------------------
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
<xsl:text>&nl;</xsl:text></xsl:template>

<xsl:template match="table/index">
<xsl:text>CREATE </xsl:text>
<xsl:if test="@type='unique'"><xsl:text>UNIQUE </xsl:text></xsl:if>
<xsl:text>INDEX </xsl:text><xsl:value-of select="@name"/> ON <xsl:value-of select="../@name"/>(<xsl:apply-templates/>);
</xsl:template>

<xsl:template match="table/index/index-column">
<xsl:value-of select="@name"/>
<xsl:if test="position() != last()"><xsl:text>,</xsl:text></xsl:if></xsl:template>



</xsl:stylesheet>