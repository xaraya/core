<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<!--

    xartemplates/includes/xarinit.php
    =================================

-->

<xsl:template match="/" mode="xd_admin-config">
    generating xartemplates/admin-config.xd <xsl:apply-templates mode="xd_admin-config" select="xaraya_module" /> finished
</xsl:template>


<!--

    THE FILE
    ========

-->
<xsl:template match="xaraya_module" mode="xd_admin-config">
<xsl:document href="{$output}/xartemplates/admin-config.xd" format="text" omit-xml-declaration="yes" xml:space="preserve">

<!--

    COMMON HEADER

-->
<xar:template file="header" type="module" />

<form method="post" action="#$action#">

    <input type="hidden" name="authid" id="authid" value="#$authid#" />

    <!--

        MODULE CONFIGURATION

    -->
    <h3>Configure Module</h3>

    <!-- // FUNC // ShortURLSupport

         create the following checkbox only if the user enabled short url
         support

    -->
    <xsl:if test="not( boolean( configuration/capabilities/supportshorturls ) )
                  or configuration/capabilities/supportshorturls/text() = 'yes'">

    <table width="100%">

        <tr>
            <td><label for="supportshorturls">Short URL Support</label></td>
            <td>
                <xsl:element name="xar:data-input" xml:space="default">
                    <xsl:attribute name="type">checkbox</xsl:attribute>
                    <xsl:attribute name="name">supportshorturls</xsl:attribute>
                    <xsl:attribute name="value">$supportshorturls</xsl:attribute>
                </xsl:element>
            </td>
        </tr>

    </table>

    </xsl:if>

    <!--

        HOOKS

    -->
    <xsl:if test="configuration/hooks/@enable = 'true'">
    <div>
        <xar:if condition="!empty($hooks)">
        <table width="100%">
        <xar:foreach in="$hooks" key="$hookmodule">
        <tr>
            <td>#$hookmodule#</td>
            <td>#$hooks[$hookmodule]#</td>
        </tr>
        </xar:foreach>
        </table>
        </xar:if>
    </div>
    </xsl:if>

    <!--

        BUTTONS

    -->
    <span>
        <br />
        <input type="submit"                value="Modify" />
        <input type="submit" name="cancel"  value="Cancel" />
    </span>

</form>

</xsl:document>
</xsl:template>
</xsl:stylesheet>
