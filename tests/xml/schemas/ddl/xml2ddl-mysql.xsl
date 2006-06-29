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
/* Disable foreign key checks until we're done */
SET FOREIGN_KEY_CHECKS = 0;
<xsl:apply-templates/>
SET FOREIGN_KEY_CHECKS = 1;
</xsl:template>

<xsl:template match="table">
  <xsl:call-template name="dynheader"/>
DROP TABLE IF EXISTS <xsl:value-of select="@name"/>;
CREATE TABLE <xsl:value-of select="@name"/> 
(
<xsl:apply-templates select="column"/>
)
COMMENT='<xsl:value-of select="@description"/>';
<xsl:apply-templates select="primary"/>
<xsl:apply-templates select="index"/>
</xsl:template>

<xsl:template match="table/column">
<xsl:text>  </xsl:text>
<xsl:value-of select="@name"/><xsl:text> </xsl:text>
<xsl:value-of select="@type"/>(<xsl:value-of select="@size"/>)<xsl:text> </xsl:text>
<xsl:if test="@required ='true'"> NOT NULL</xsl:if>
<xsl:if test="@default != ''"> DEFAULT '<xsl:value-of select="@default"/>'</xsl:if>
<xsl:if test="@autoIncrement ='true'"> AUTO_INCREMENT</xsl:if>
<xsl:if test="position() != last()"><xsl:text>,</xsl:text></xsl:if>
<xsl:value-of select="$CR"/></xsl:template>

</xsl:stylesheet>