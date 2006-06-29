<?xml version="1.0"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" >
  <xsl:output method="text"/>
  
  <!-- Variables, xslt style -->
  <xsl:variable name="CR">
<xsl:text>
</xsl:text>
  </xsl:variable>
  
<!-- Context sensitive header, reacts on name attribute (typical: database)-->
<xsl:template name="topheader">
/* ---------------------------------------------------------------------------
 * Model generated from: TODO
 * Name                : <xsl:value-of select="@name"/>
 * Vendor              : TODO
 * Date                : TODO
 * Remark:             :
 */
</xsl:template>

<!-- Context sensitive header, reacts on name and element-name (typical: table) -->
<xsl:template name="dynheader">
/* ---------------------------------------------------------------------------
 * <xsl:value-of select="local-name()"/>: <xsl:value-of select="@name" />
 */  
</xsl:template>

</xsl:stylesheet>  
  