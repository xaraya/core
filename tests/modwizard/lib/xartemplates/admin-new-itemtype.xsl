<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="table" mode="xd_admin-new-itemtype">
    <xsl:variable name="table" select="@name" />
<xsl:document href="{$output}/xartemplates/admin-new-{$table}.xd" format="text" omit-xml-declaration="yes" xml:space="preserve">

<xar:template file="header" type="module" />

<xar:if condition="!empty($preview)">
    #$preview#
</xar:if>

<form method="post" action="#$action#">

    <input type="hidden" name="authid" id="authid" value="#$authid#" />

    <table>
        <xar:data-form object="$object" />
    </table>

    <!-- Only display hooks when necessary -->
    <xsl:if test="@hooks = 'enable'">
    <div>
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

        <input type="submit"                value="Create" />
        <input type="submit" name="preview" value="Preview" />
        <input type="submit" name="cancel"  value="Cancel" />
</form>
</xsl:document>
</xsl:template>

</xsl:stylesheet>
