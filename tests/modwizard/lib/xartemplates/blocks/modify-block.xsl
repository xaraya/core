<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:xar="dd"
                xmlns="http://www.w3.org/TR/xhtml1/strict">


<xsl:template match="block" mode="xd_blocks_modify-block">
    <xsl:message>      * xartemplates/blocks/modify-<xsl:value-of select="@name" />.xd</xsl:message>

    <xsl:variable name="block" select="@name" />
    <xsl:document xml:space="preserve" href="{$output}/xartemplates/blocks/modify-{$block}.xd" format="xml" omit-xml-declaration="yes" >
                <!-- Forms sent to the blocks admin should be consistant with the blocks UI.  The UI uses CSS for positioning and as such you can use this as a template. -->
                <div style="clear: both; padding-top: 10px;">
                <span style="float: left; width: 20%; text-align: right;">
                        <!-- The span class help displays help as a tool tip. -->
                        <span class="help" title="#xarML('Enter the number of items to display.')#"><label for="numitems#$blockid#"><xar:mlstring>Number of Items</xar:mlstring></label>:</span>
                </span>
                <span style="float: right; width: 78%; text-align: left;">
                         <input type="text" name="numitems" id="numitems#$blockid#" value="#$numitems#" size="5" maxlength="5" />
                </span>
                </div>
                <!-- No further action is required for this block.  The blocks admin will display this form element in a consistant manner throughout Xaraya -->

    </xsl:document>
</xsl:template>

</xsl:stylesheet>
