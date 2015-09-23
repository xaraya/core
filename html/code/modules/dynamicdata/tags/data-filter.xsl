<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nl "&#xd;&#xa;">
]>
<xsl:stylesheet version="1.0" 
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xar="http://xaraya.com/2004/blocklayout"   
    xmlns:php="http://php.net/xsl" 
    exclude-result-prefixes="php xar">

<xsl:template match="xar:data-filter">
    <xsl:processing-instruction name="php">
        <xsl:choose>
          <xsl:when test="not(@property)">
          <!-- No property, gotta make one -->
            <xsl:text>try{sys::import('modules.dynamicdata.class.properties');</xsl:text>
            <xsl:text>$property =&amp; DataPropertyMaster::getProperty(</xsl:text>
            <xsl:call-template name="atts2args">
              <xsl:with-param name="nodeset" select="@*[name() != 'hidden' and name() != 'preset']"/>
            </xsl:call-template>
            <xsl:text>);</xsl:text>
            <xsl:text>echo $property-&gt;</xsl:text>
            <xsl:choose>
              <xsl:when test="@hidden">
                <xsl:text>showHidden(</xsl:text>
                <xsl:call-template name="atts2args">
                  <xsl:with-param name="nodeset" select="@*[name() != 'hidden' and name() != 'preset']"/>
                </xsl:call-template>
              </xsl:when>
              <xsl:otherwise>
                <xsl:text>showFilter(</xsl:text>
                <xsl:call-template name="atts2args">
                  <xsl:with-param name="nodeset" select="@*[name() != 'hidden' and name() != 'preset']"/>
                </xsl:call-template>
              </xsl:otherwise>
            </xsl:choose>
            <xsl:text>);}catch(Exception $e){if(xarModVars::get('dynamicdata','debugmode')&amp;&amp;in_array(xarUser::getVar('id'),xarConfigVars::get(null, 'Site.User.DebugAdmins')))echo "&lt;pre&gt;".$e->getMessage()."&lt;/pre&gt;";}</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <!-- We have a property -->
            <xsl:text>if (isset(</xsl:text>
            <xsl:value-of select="@property"/>
            <xsl:text>)){</xsl:text>
            <xsl:text>echo </xsl:text><xsl:value-of select="@property"/>
            <xsl:text>-&gt;showFilter(</xsl:text>
            <xsl:call-template name="atts2args">
              <xsl:with-param name="nodeset" select="@*"/>
            </xsl:call-template>
            <xsl:text>);</xsl:text>
            <xsl:text>}</xsl:text>
          </xsl:otherwise>
        </xsl:choose>
    </xsl:processing-instruction>
</xsl:template>

</xsl:stylesheet>