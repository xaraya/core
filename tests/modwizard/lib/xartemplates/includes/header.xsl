<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<!--

    xartemplates/includes/xarinit.php
    =================================

-->

<xsl:template match="/" mode="xd_includes_header">
    Baue xartemplates/includes/header.php ...<xsl:apply-templates mode="xd_includes_header" select="xaraya_module" />... fertig
</xsl:template>


<!--

    THE FILE
    ========

-->
<xsl:template match="xaraya_module" mode="xd_includes_header">
<xsl:document href="{$output}/xartemplates/includes/header.xd" format="text" omit-xml-declaration="yes" xml:space="preserve">

<xar:if condition="!empty($common)">

    <xar:if condition="isset( $common['type'])">
        <div class="xar-mod-head"><span class="xar-mod-title">#$common['type']#</span></div>
    </xar:if>

    <div class="xar-mod-body">

        <h2>#$common['pagetitle']#</h2>

        <xar:if condition="count( $common['menu']) > 0">

        <xar:set name="$common_menu">#$common['menu']#</xar:set>

        <div>
            #$common['menu_label']#:

            <xar:foreach in="$common['menu']" value="$menuitem">
            <a href="#$menuitem['url']#">#$menuitem['label']#</a> |
            </xar:foreach>
        </div>

        </xar:if>

    </div>

    <xar:if condition="!empty($common['statusmsg'])">
    <div class="xar-mod-message" style="margin: 20px 10px 20px 20px; background-color: ##EEEEEE; text-align: center;">
        #$common['statusmsg']#
    </div>
    </xar:if>

</xar:if>
</xsl:document>
</xsl:template>
</xsl:stylesheet>
