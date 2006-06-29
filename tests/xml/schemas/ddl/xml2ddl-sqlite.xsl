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
/* Connecting to the database will create the database if it does not exist for sqlite */
<xsl:apply-templates />
</xsl:template>

<xsl:template match="table">
  <xsl:call-template name="dynheader"/>
DROP TABLE IF EXISTS <xsl:value-of select="@name"/>;
CREATE TABLE <xsl:value-of select="@name"/>
(
  <xsl:apply-templates select="column"/>
);
<xsl:apply-templates select="primary"/>
<xsl:apply-templates select="index"/>
</xsl:template>

<xsl:template match="table/column">
  <xsl:call-template name="TODO"/>
</xsl:template>

<xsl:template match="table/primary">
  /* Sqlite can only specify primary key constraints in a column spec */
  <xsl:call-template name="TODO"/>
</xsl:template>

</xsl:stylesheet>