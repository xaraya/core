<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<!--

    xartemplates/
    =================================

-->

<xsl:template match="/" mode="xd_admin-delete-itemtype">
    <xsl:apply-templates mode="xd_admin-delete-itemtype" select="xaraya_module" />
</xsl:template>


<!--

    THE FILE
    ========

-->
<xsl:template match="xaraya_module" mode="xd_admin-delete-itemtype">
    <xsl:for-each select="database/table">
    generating xartemplates/admin-delete-itemtype<xsl:value-of select="@name" />.xd ...<xsl:apply-templates select="." mode="xd_admin-delete-itemtype" />... finished
    </xsl:for-each>
</xsl:template>

<xsl:template match="table" mode="xd_admin-delete-itemtype">
<xsl:variable name="table" select="@name" />
<xsl:document href="{$output}/xartemplates/admin-delete-{$table}.xd" format="text" omit-xml-declaration="yes" xml:space="preserve">

<xar:template file="header" type="module" />

<form method="post" action="#$action#">

    <input type="hidden" name="authid" id="authid" value="#$authid#" />
    <input type="hidden" name="itemid" id="itemid" value="#$itemid#" />

    <table>
        <xar:data-form object="$object" />
    </table>

    <!-- Only display hooks when necessary -->
    <xsl:if test="@hooks = 'enable'">
    <div>
        <b>hooks</b>
        <xar:if condition="!empty($hooks)">
        <table>
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

        <input type="submit" name="confirm"     value="Delete" />
        <input type="submit" name="cancel"      value="Cancel" />

</form>
</xsl:document>
</xsl:template>

</xsl:stylesheet>
