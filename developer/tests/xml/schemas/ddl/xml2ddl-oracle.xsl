<?xml version="1.0" encoding="utf-8"?>
<!--
  XSLT to create a DDL fragment which represents the same
  information as the ddl XML
-->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
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
    - reference: http://download-uk.oracle.com/docs/cd/B19188_01/doc/B15917/toc.htm
    - assuming for now we want to drop before create
  </xsl:with-param>
</xsl:call-template>
<xsl:apply-templates/>
</xsl:template>

<xsl:template match="table">
  <xsl:call-template name="dynheader"/>
CREATE TABLE <xsl:value-of select="@name"/> 
(
  <xsl:apply-templates select="primary/column | column"/>
);
<xsl:apply-templates select="primary"/>
<xsl:apply-templates select="index"/>
</xsl:template>

<xsl:template match="table/column">
  <xsl:call-template name="TODO"/>
</xsl:template>

</xsl:stylesheet>