<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<xsl:template match="table" mode="xd_admin-delete-itemtype">

    <xsl:variable name="table" select="@name" />
    <xsl:message>      * xartemplates/admin-delete-<xsl:value-of select="@name" />.xd</xsl:message>

<xsl:document href="{$output}/xartemplates/admin-delete-{$table}.xd" format="text" omit-xml-declaration="yes" xml:space="preserve">

    <xar:template file="header" type="module" />
    <div class="xar-mod-body">
    <div style="padding: 1px;" class="xar-norm-outline">

<form method="post" action="#$action#">

    <input type="hidden" name="authid" id="authid" value="#$authid#" />
    <input type="hidden" name="itemid" id="itemid" value="#$itemid#" />

    <div class="xar-norm-outline">
        #$preview#
    </div>

    <!-- Only display hooks when necessary -->
    <xar:if condition="!empty($hooks)">
    <xar:foreach in="$hooks" key="$hookmodule">
        #$hooks[$hookmodule]#
    </xar:foreach>
    </xar:if>

    <div style="clear: both; padding-top: 10px; text-align: center; width: 100%;">
            <input type="submit" name="confirm" value="#xarML('Delete')#" />
            <input type="submit" name="cancel"  value="#xarML('Cancel')#" />
    </div>

</form>
</div>
</div>
</xsl:document>
</xsl:template>

</xsl:stylesheet>
