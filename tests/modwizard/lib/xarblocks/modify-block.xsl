<!--

    XARAYA MODULE WIZARD

    COPYRIGHT:      Michael Jansen
    CONTACT:        xaraya-module-wizard@schneelocke.de
    LICENSE:        GPL

-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<!-- ENTRY POINT    print out progress and call module template -->
<xsl:template match="block" mode="xarblocks_modify-block" xml:space="default">

    <xsl:message>      * xarblocks/modify-<xsl:value-of select="@name" />.php</xsl:message>

    <xsl:variable name="block" select="@name" />
    <xsl:document href="{$output}/xarblocks/modify-{$block}.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <!-- call template for file header -->
        <xsl:call-template name="xaraya_standard_php_file_header" select="../..">
            <xsl:with-param name="filename">modify-<xsl:value-of select="$block" />.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xarblocks_block_update" select="." />

        <xsl:apply-templates mode="xarblocks_block_modify" select="." />

        <!-- call template for module_xarupgrade() function -->
        <xsl:apply-templates mode="xarblocks_block_help" select="." />

        <!-- call template for file footer -->
        <xsl:call-template name="xaraya_standard_php_file_footer" select="../.." />

    </xsl:processing-instruction></xsl:document>

</xsl:template>

<!-- update() Function -->
<xsl:template mode="xarblocks_block_update" match="block">
    <xsl:variable name="module_prefix" select="../../registry/name" />
/**
 * Update Block information
 */
function <xsl:value-of select="$module_prefix" />_<xsl:value-of select="@name" />block_update( $blockinfo )
{
<xsl:if test="$gCommentsLevel >= 10">    // Get variables from content block.
    // Content is a serialized array for legacy support, but will be
    // an array (not serialized) once all blocks have been converted.</xsl:if>
    if( !is_array( $blockinfo['content'] ))
        {
        $vars = unserialize( $blockinfo['content'] );
        }
    else
        {
        $vars = $blockinfo[ 'content' ];
        }

    if( !xarVarFetch( 'numitems' ,'int:0' ,$numitems ,XARVAR_DONT_SET))
        {
        return;
        }

    $vars['numitems'] = $numitems;
    $blockinfo['content'] = $vars;


    return $blockinfo;
}
</xsl:template>



<!-- help() Function -->
<xsl:template mode="xarblocks_block_modify" match="block">
    <xsl:variable name="module_prefix" select="../../registry/name" />
/**
 * Update block settings
 */
function <xsl:value-of select="$module_prefix" />_<xsl:value-of select="@name" />block_modify( $blockinfo )
{
<xsl:if test="$gCommentsLevel >= 10">    // Get variables from content block.
    // Content is a serialized array for legacy support, but will be
    // an array (not serialized) once all blocks have been converted.</xsl:if>
    if( !is_array( $blockinfo['content'] ))
        {
        $vars = unserialize( $blockinfo['content'] );
        }
    else
        {
        $vars = $blockinfo[ 'content' ];
        }

<xsl:if test="$gCommentsLevel >= 10">    // Set default values</xsl:if>
    if (empty($vars['numitems'])) {
        $vars['numitems'] = 15;
    }

<xsl:if test="$gCommentsLevel >= 10">    // Send content to template</xsl:if>
    return array (
        'numitems' => $vars['numitems']
        ,'blockid' => $blockinfo['bid']
        );
}
</xsl:template>

</xsl:stylesheet>

