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
    - reference: http://sqlite.org/lang.html
    - assuming for now we want to drop before create
    </xsl:with-param>
  </xsl:call-template>
  <xsl:apply-templates/>
</xsl:template>

<xsl:template match="database">
  <xsl:call-template name="dynheader"/>
  <xsl:text>/* Connecting to the database will create the database if it does not exist for sqlite */</xsl:text>
  <xsl:apply-templates />
</xsl:template>

<xsl:template match="table">
  <xsl:call-template name="dynheader"/>
  <xsl:text>CREATE TABLE </xsl:text>
  <xsl:value-of select="@name"/>
  <xsl:value-of select="$CR"/>
  <xsl:text>(</xsl:text>
  <xsl:value-of select="$CR"/>
  <xsl:apply-templates select="primary/column | column"/>
  <xsl:apply-templates select="primary"/>
  <xsl:value-of select="$CR"/>
  <xsl:text>);</xsl:text>
  <xsl:value-of select="$CR"/>
  <xsl:apply-templates select="index"/>
</xsl:template>

<xsl:template match="column">
  <xsl:text>  </xsl:text>
  <xsl:value-of select="@name"/><xsl:text> </xsl:text>
  <xsl:value-of select="@type"/>
  <xsl:if test="@size != ''">
    <xsl:text>(</xsl:text>
    <xsl:value-of select="@size"/>
    <xsl:text>) </xsl:text>
  </xsl:if>
  <xsl:if test="@required ='true'">
    <xsl:text>NOT NULL</xsl:text>
  </xsl:if>
  <xsl:if test="@default != ''">
    <xsl:text> DEFAULT '</xsl:text>
    <xsl:value-of select="@default"/>
    <xsl:text>'</xsl:text>
  </xsl:if>
  <xsl:if test="position() != last()">
    <xsl:text>,</xsl:text>
  </xsl:if>
  <xsl:value-of select="$CR"/>
</xsl:template>

<xsl:template match="table/primary">
  <xsl:text>,PRIMARY KEY (</xsl:text>
  <xsl:for-each select="column">
    <xsl:value-of select="@name"/>
    <xsl:if test="position() != last()"><xsl:text>,</xsl:text></xsl:if>
  </xsl:for-each>
  <xsl:text>)</xsl:text>
</xsl:template>
</xsl:stylesheet>