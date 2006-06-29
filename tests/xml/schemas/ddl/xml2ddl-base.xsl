<?xml version="1.0"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" >
  <!-- DDL is no XML -->
  <xsl:output method="text" />
  <xsl:strip-space elements="*"/>

  <!-- Variables, xslt style -->
  <xsl:variable name="CR">
<xsl:text>
</xsl:text>
  </xsl:variable>
  
<!-- File header -->
<xsl:template name="topheader">
<xsl:param name="dbname"/>
<xsl:param name="remarks"/>
/* ---------------------------------------------------------------------------
 * Model generated from: TODO
 * Name                : <xsl:value-of select="$dbname"/>
 * Vendor              : <xsl:value-of select="$vendor"/>
 * Date                : TODO
 * Remarks:            : 
 *   <xsl:value-of select="$remarks"/>
 */
</xsl:template>

<!-- Context sensitive header, reacts on name and element-name -->
<xsl:template name="dynheader">
/* ---------------------------------------------------------------------------
 * <xsl:value-of select="local-name()"/>: <xsl:value-of select="@name" />
 */  
</xsl:template>

<!-- Easy TODO inclusion -->
<xsl:template name="TODO">
<xsl:text>/* TODO: Template for: </xsl:text>
<xsl:value-of select="local-name()"/>
<xsl:text> </xsl:text>
<xsl:value-of select="@name"/>
<xsl:text> handling (vendor: </xsl:text>
<xsl:value-of select="$vendor"/>
<xsl:text>) */
</xsl:text>
</xsl:template>


</xsl:stylesheet>  
  