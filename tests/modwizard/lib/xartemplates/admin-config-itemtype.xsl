<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<!--

    xartemplates/includes/xarinit.php
    =================================

-->

<xsl:template match="/" mode="xd_admin-config-itemtype">
    <xsl:apply-templates mode="xd_admin-display-itemtype" select="xaraya_module" />
</xsl:template>


<xsl:template match="xaraya_module" mode="xd_admin-display-itemtype">
    <xsl:for-each select="database/table">
    generating xartemplates/admin-config-<xsl:value-of select="@name" />.xd <xsl:apply-templates mode="xd_admin-config-itemtype" select="." /> finished
    </xsl:for-each>
</xsl:template>


<!--

    THE FILE
    ========

-->
<xsl:template match="table" mode="xd_admin-config-itemtype">
    <xsl:variable name="table" select="@name" />
<xsl:document href="{$output}/xartemplates/admin-config-{$table}.xd" format="text" omit-xml-declaration="yes" xml:space="preserve">

<!--

    COMMON HEADER

-->
<xar:template file="header" type="module" />

<form method="post" action="#$action#">

    <input type="hidden" name="authid" id="authid" value="#$authid#" />

    <!--

        ITEMTYPE CONFIGURATIION

    -->
    <input type="hidden" name="itemtype" id="itemtype" value="#$itemtype#" />

    <br />
    <br />

    <table width="100%">

        <tr>
            <td><label for="integerbox">Item's per page</label></td>
            <td>
                <xsl:element name="xar:data-input" xml:space="default">
                    <xsl:attribute name="type">integerbox</xsl:attribute>
                    <xsl:attribute name="name">itemsperpage</xsl:attribute>
                    <xsl:attribute name="value">$itemsperpage</xsl:attribute>
                </xsl:element>
            </td>
        </tr>

    </table>

    <!--

        HOOKS

    -->
    <xsl:if test="@hooks = 'enable'">
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
