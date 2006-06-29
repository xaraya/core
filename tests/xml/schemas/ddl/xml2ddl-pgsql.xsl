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
  
  <!-- Global variables -->
  <xsl:variable name="vendor">pgsql</xsl:variable>
  
<!-- 
    We probably want to specify parameters at some point like:
    - drop4create - drop tables before creating them
    - createdb    - create the database too
    - tableprefix - self explanatory
    - etc.
-->
<xsl:template match="/">
<xsl:call-template name="topheader">
  <xsl:with-param name="dbname"><xsl:value-of select="/database/@name"/></xsl:with-param>
  <xsl:with-param name="remarks">
    - reference: http://www.postgresql.org/docs/8.1/interactive/index.html
    - assuming for now we want to drop before create
  </xsl:with-param>
</xsl:call-template>
<xsl:apply-templates/>
</xsl:template>

<xsl:template match="table">
  <xsl:call-template name="dynheader"/>
/* TODO: Dropping a table without exist checking is an error in Postgres, but common to use */
CREATE TABLE <xsl:value-of select="@name"/>
(
  <xsl:apply-templates select="column"/>
);
<xsl:text>COMMENT ON TABLE </xsl:text><xsl:value-of select="@name"/><xsl:text> IS '</xsl:text><xsl:value-of select="@description"/>';
<xsl:for-each select="./column">
  <xsl:text>COMMENT ON COLUMN </xsl:text><xsl:value-of select="../@name"/>.<xsl:value-of select="@name"/><xsl:text> IS '</xsl:text><xsl:value-of select="@description"/>';
</xsl:for-each>
<xsl:apply-templates select="index"/>
</xsl:template>

<xsl:template match="table/column">
  <xsl:call-template name="TODO"/>
</xsl:template>

</xsl:stylesheet>