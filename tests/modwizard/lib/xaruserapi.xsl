<!--

    XARAYA MODULE WIZARD

    COPYRIGHT:      Michael Jansen
    CONTACT:        xaraya-module-wizard@schneelocke.de
    LICENSE:        GPL

-->
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="http://www.w3.org/TR/xhtml1/strict">

<!--

    xaruserapi.xsl
    ===========

-->

<!-- ENTRY POINT    print out progress and call module template -->
<xsl:template match="/" mode="xaruserapi" xml:space="default">
### Generating user api <xsl:apply-templates mode="xaruserapi" select="xaraya_module" />
</xsl:template>



<!-- MODULE POINT

     Create a new file called xaruserapi.php.

-->
<xsl:template match="xaraya_module" mode="xaruserapi">

    <!-- UTILITY FUNCTIONS
    -->
    <xsl:document href="{$output}/xaruserapi/getmenulinks.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaruserapiapi/getmenulinks.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates select="." mode="xaruserapi_getmenulinks" />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>


    <!-- // FUNC // ShortURLSupport

         create the following functions only if the user enabled short url
         support
    -->
    <xsl:if test="not( boolean( configuration/capabilities/supportshorturls ) ) or configuration/capabilities/supportshorturls/text() = 'yes'">
        <xsl:document href="{$output}/xaruserapi/encode_shorturl.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

            <xsl:call-template name="xaraya_standard_php_file_header" select=".">
                <xsl:with-param name="filename">xaruserapiapi/encode_shorturl.php</xsl:with-param>
            </xsl:call-template>

            <xsl:apply-templates select="." mode="xaruserapi_encode_shorturl" />

            <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

        </xsl:processing-instruction></xsl:document>


        <xsl:document href="{$output}/xaruserapi/decode_shorturl.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

            <xsl:call-template name="xaraya_standard_php_file_header" select=".">
                <xsl:with-param name="filename">xaruserapiapi/decode_shorturl.php</xsl:with-param>
            </xsl:call-template>

            <xsl:apply-templates select="." mode="xaruserapi_decode_shorturl" />

            <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

        </xsl:processing-instruction></xsl:document>

    </xsl:if>

    <!-- EVENT FUNCTIONS
    -->

    <!-- GENERIC DATA ACCESS FUNCTIONS
    -->
    <xsl:if test="boolean( database/table[@user='true'] )">
    <xsl:document href="{$output}/xaruserapi/count.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaruserapiapi/count.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaruserapi_count" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>


    <xsl:document href="{$output}/xaruserapi/getall.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaruserapiapi/getall.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaruserapi_getall" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>


    <xsl:document href="{$output}/xaruserapi/get.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaruserapiapi/get.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaruserapi_get" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>


    <xsl:document href="{$output}/xaruserapi/gettitle.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaruserapiapi/gettitle.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates mode="xaruserapi_gettitle" select="." />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>


    <xsl:document href="{$output}/xaruserapi/getitemlinks.php" format="text" omit-xml-declaration="yes" ><xsl:processing-instruction name="php">

        <xsl:call-template name="xaraya_standard_php_file_header" select=".">
            <xsl:with-param name="filename">xaruserapiapi/getitemlinks.php</xsl:with-param>
        </xsl:call-template>

        <xsl:apply-templates select="." mode="xaruserapi_getitemlinks" />

        <xsl:call-template name="xaraya_standard_php_file_footer" select="." />

    </xsl:processing-instruction></xsl:document>

    </xsl:if>


</xsl:template>



<!-- =========================================================================
     TEMPLATE FOR <module>_userapi_getmenulinks()
-->
<xsl:template match="xaraya_module" mode="xaruserapi_getmenulinks">

<xsl:variable name="module_prefix" select="registry/name" />
<xsl:if test="$gCommentsLevel >= 1">
/**
 * Utility function to pass individual menu items to the main menu.
 *
 * This function is invoked by the core to retrieve the items for the
 * usermenu.
 *
 * @returns array
 * @return  array containing the menulinks for the main menu items
 */
</xsl:if>
function <xsl:value-of select="$module_prefix" />_userapi_getmenulinks ( $args ) {

    <xsl:if test="$gCommentsLevel >= 2">
    // First we need to do a security check to ensure that we only return menu items
    // that we are suppose to see.  It will be important to add for each menu item that
    // you want to filter.  No sense in someone seeing a menu link that they have no access
    // to edit.  Notice that we are checking to see that the user has permissions, and
    // not that he/she doesn't.
    </xsl:if>

    if (xarSecurityCheck('View<xsl:value-of select="$module_prefix" />')) {
        <xsl:if test="$gCommentsLevel >= 2">
        // The main menu will look for this array and return it for a tree
        // view of the module. We are just looking for three items in the
        // array, the url, which we need to use the xarModURL function, the
        // title of the link, which will display a tool tip for the module
        // url, in order to keep the label short, and finally the exact label
        // for the function that we are displaying.
        </xsl:if>
        <xsl:for-each select="database/table[@user='true']">
        $menulinks[] = array(
            'url'       => xarModURL(
                '<xsl:value-of select="$module_prefix" />'
                ,'user'
                ,'view'
                ,array(
                    'itemtype' => <xsl:value-of select="@itemtype" /> ))
            ,'title'    => 'Look at the <xsl:value-of select="label" />'
            ,'label'    => 'View <xsl:value-of select="label" />' );
        </xsl:for-each>

    }

    if (empty($menulinks)){
        $menulinks = '';
    }

    // The final thing that we need to do in this function is return the values back
    // to the main menu for display.
    return $menulinks;

}
</xsl:template>



<!-- =========================================================================
     TEMPLATE FOR <module>_userapi_getmenulinks()
-->
<xsl:template match="xaraya_module" mode="xaruserapi_getitemlinks">

<xsl:variable name="module_prefix" select="registry/name" />
/**
 * Utility function to pass individual item links to whoever
 *
 * @param $args['itemtype'] item type (optional)
 * @param $args['itemids'] array of item ids to get
 * @returns array
 * @return array containing the itemlink(s) for the item(s).
 */
function <xsl:value-of select="$module_prefix" />_userapi_getitemlinks ( $args ) {

    extract($args);

    if (empty($itemtype)) {
        return;
    }

    $itemlinks = array();
    $objects =&amp; xarModAPIFunc(
        '<xsl:value-of select="$module_prefix" />'
        ,'user'
        ,'getall'
        ,array(
             'itemids'   => $itemids
            ,'itemtype'  => $itemtype
#            ,'fieldlist' => array( <xsl:for-each select="labelfields/field">'<xsl:value-of select="@name" />'<xsl:if test="position() != last()">,</xsl:if>
#               </xsl:for-each>)
        ));
    if ( empty($objects) ) return;

    $data =&amp; $objects->items;

    foreach( $data as $id => $object ) {

        $title = xarModAPIFunc(
            '<xsl:value-of select="$module_prefix" />'
            ,'user'
            ,'gettitle'
            ,array(
                'itemtype'  =>  $itemtype
                ,'item'     =>  &amp; $object
                ));

        $itemlinks[$id] = array(
            'url'   =>  xarModURL(
                '<xsl:value-of select="$module_prefix" />'
                ,'user'
                ,'display'
                ,array(
                    'itemid'    => $id
                    ,'itemtype' => $itemtype
                    ))
            ,'title'    =>  $title
            ,'label'    =>  $title
            );
    }

    return $itemlinks;
}
</xsl:template>



<!-- ========================================================================

        MODE: xaruserapi_count              MATCH:  table
-->
<xsl:template mode="xaruserapi_count" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
/**
 * Generic function to retrieve the number of objects stored in database of
 * itemtype $itemtype;
 *
 * @param array( 'itemtype' => &lt;itemtype&gt; )
 * @return number of items
 */
function <xsl:value-of select="$module_prefix" />_userapi_count( $args ) {

    extract( $args );

    // Retrieve all objects via the dynamicdata module api.
    $numitems =&amp; xarModAPIFunc(
        'dynamicdata'
        ,'user'
        ,'countitems'
        ,array(
            'module'     => '<xsl:value-of select="$module_prefix" />'
            ,'itemtype'  => $itemtype
        ));

    return $numitems;
}
</xsl:template>



<!-- ========================================================================

        MODE: xaruserapi_get                MATCH:  table
-->
<xsl:template mode="xaruserapi_get" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
/**
 *
 * @param array( 'itemtype' => &lt;itemtype&gt; )
 * @return number of items
 *
 * @param $args['itemid'] starting article number
 * @param $args['numitems'] number of articles to get
 * @param $args['sort'] sort order ('date','title','hits','rating',...)
 * @param $args['fields'] array with all the fields to return
 * @param $args['fields'] array with all the fields to return
 */
function <xsl:value-of select="$module_prefix" />_userapi_get( $args ) {

    extract( $args );

    // Retrieve the object via the dynamicdata module api.
    $object = xarModAPIFunc(
        'dynamicdata'
        ,'user'
        ,'getitem'
        ,array(
            'module'     => '<xsl:value-of select="$module_prefix" />'
            ,'itemtype'  => $itemtype
            ,'itemid'    => $itemid
            ,'status'    => 1
            ,'getobject' => 1
        ));
    if ( empty($object) ) return;

    return $object;
}
</xsl:template>



<!-- ========================================================================

        MODE: xaruserapi_gettitle           MATCH:  table
-->
<xsl:template mode="xaruserapi_gettitle" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
/**
 *
 * @param array( 'itemtype' => &lt;itemtype&gt; )
 * @return number of items
 *
 * @param $args['item'] item
 * @param $args['itemtype'] itemtyp
 */
function <xsl:value-of select="$module_prefix" />_userapi_gettitle( $args ) {

    extract( $args );

    if ( empty( $itemtype ) ) return 'Itemtype missing';

    if ( isset( $item ) ) {
        switch ( $itemtype ) {
        <xsl:for-each select="database/table">
            <xsl:if test="boolean( labelfields )">
            case <xsl:value-of select="@itemtype" />:
                return <xsl:for-each select="labelfields/field">$item['<xsl:value-of select="@name" />']<xsl:if test="last() != position()"> .
                       '<xsl:value-of select="../@separator" />' . </xsl:if></xsl:for-each>;
                break;
            </xsl:if>
        </xsl:for-each>
        }

    } else if ( isset( $object ) ) {

        switch ( $itemtype ) {
        <xsl:for-each select="database/table">
            <xsl:if test="boolean( labelfields )">
            case <xsl:value-of select="@itemtype" />:
                return <xsl:for-each select="labelfields/field">$object->properties['<xsl:value-of select="@name" />']->getValue()<xsl:if test="last() != position()"> .
                       '<xsl:value-of select="../@separator" />' . </xsl:if></xsl:for-each>;
                break;
            </xsl:if>
        </xsl:for-each>
        }
    }

    return 'Unknown Itemtype';
}
</xsl:template>



<!-- ========================================================================

        MODE: xaruserapi_getall             MATCH:  table
-->
<xsl:template mode="xaruserapi_getall" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
/**
 *
 * @param array( 'itemtype' => &lt;itemtype&gt; )
 * @return number of items
 *
 * @param $args['startnum'] starting article number
 * @param $args['numitems'] number of articles to get
 * @param $args['sort'] sort order ('date','title','hits','rating',...)
 * @param $args['fields'] array with all the fields to return per article
 *                        Default list is : 'aid','title','summary','authorid',
 *                        'pubdate','pubtypeid','notes','status','body'
 *                        Optional fields : 'cids','author','counter','rating','dynamicdata'
 */
function <xsl:value-of select="$module_prefix" />_userapi_getall( $args ) {

    extract( $args );

    if ( empty($startnum) ) {
        $startnum = NULL;
    }

    if ( empty($numitems) ) {
        $numitems = NULL;
    }

    if ( empty($sort) ) {
        $sort = NULL;
    }

    if ( empty($fieldlist) ) {
        $fieldlist = NULL;
    }

    if ( empty($itemids) ) {
        $itemids = NULL;
    }

    // Retrieve all objects via the dynamicdata module api.
    $objects =&amp; xarModAPIFunc(
        'dynamicdata'
        ,'user'
        ,'getitems'
        ,array(
            'module'     => '<xsl:value-of select="$module_prefix" />'
            ,'itemtype'  => $itemtype
            ,'numitems'  => $numitems
            ,'startnum'  => $startnum
            ,'status'    => 1
            ,'sort'      => $sort
            ,'getobject' => 1
            ,'itemids'   => $itemids
            ,'fieldlist' => $fieldlist
        ));

    return $objects;
}
</xsl:template>



<!-- =========================================================================

    MODE: xaruserapi_decode_shorturl        MATCH:  xaraya_module

-->
<xsl:template mode="xaruserapi_decode_shorturl" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
/**
 * This function is called when xarModURL is invoked and Short URL Support is
 * enabled.
 *
 * The parameters are passed in $args.
 *
 * Some hints:
 *
 * o If you want to get rid of the modulename. Look at xarModGetAlias() and
 *   xarModSetAlias().
 * o
 *
 */
function <xsl:value-of select="$module_prefix" />_userapi_decode_shorturl( $params ) {

    <xsl:if test="boolean( database/table[@user='true'] )">
    if ( $params[0] != '<xsl:value-of select="$module_prefix" />' )
        return;

    /*
     * Check for the itemtype
     */
    if ( empty( $params[1] ) )
        return array( 'main', array() );

    switch ( $params[1] ) {
    <xsl:for-each select="database/table[@user='true']">
        case '<xsl:value-of select="@name" />':
            $itemtype = <xsl:value-of select="@itemtype" />;
            break;
    </xsl:for-each>

        default:
            return array( 'main', array() );
    }

    if ( !isset( $params[2] ) )
        return array(
            'view'
            ,array(
                'itemtype' => $itemtype ));

    return array(
        'display'
        ,array(
            'itemid'    => $params[2]
            ,'itemtype' => $itemtype ));
    </xsl:if>
}
</xsl:template>



<!-- =========================================================================

    MODE: xaruserapi_encode_shorturl        MATCH:  xaraya_module

-->
<xsl:template mode="xaruserapi_encode_shorturl" match="xaraya_module">
    <xsl:variable name="module_prefix" select="registry/name" />
/**
 * This function is called when xarModURL is invoked and Short URL Support is
 * enabled.
 *
 * The parameters are passed in $args.
 *
 * Some hints:
 *
 * o If you want to get rid of the modulename. Look at xarModGetAlias() and
 *   xarModSetAlias().
 * o
 *
 */
function <xsl:value-of select="$module_prefix" />_userapi_encode_shorturl( $args ) {
    <xsl:if test="boolean( database/table[@user='true'] )">
    $func       = NULL;
    $module     = NULL;
    $itemid     = NULL;
    $itemtype   = NULL;
    $rest       = array();

    foreach( $args as $name => $value ) {

        switch( $name ) {

            case 'module':
                $module = $value;
                break;

            case 'itemtype':
                $itemtype = $value;
                break;

            case 'objectid':
            case 'itemid':
                $itemid = $value;
                break;

            case 'func':
                $func = $value;
                break;

            default:
                $rest[] = $value;

       }
    }

    // kind of a assertion :-))
    if( isset( $module ) and $module != '<xsl:value-of select="$module_prefix" />' ) {
        return;
    }

    /*
     * LETS GO. We start with the module.
     */
    $path = '/<xsl:value-of select="$module_prefix" />';

    if ( empty( $func ) )
        return;

    /*
     * We only provide support for display and view and main
     */
    if ( $func != 'display' and $func != 'view' and $func != 'main' )
        return;

    /*
     * Now add the itemtype if possible
     */
    if ( isset( $itemtype ) ) {

        switch ( $itemtype ) {
        <xsl:for-each select="database/table[@user='true']">
            case <xsl:value-of select="@itemtype" />:
                $itemtype_name = '<xsl:value-of select="@name" />';
                break;
        </xsl:for-each>

        default:
            // Unknown itemtype?
            return;
        }

        $path = $path . '/' . $itemtype_name;

        /*
         * And last but not least the itemid
         */
        If ( isset( $itemid ) ) {
                $path = $path . '/' . $itemid;
        }
    }

    /*
     * ADD THE REST !!!! THIS HAS TO BE DONE EVERYTIME !!!!!
     */
    $add = array();
    foreach ( $rest as $argument ) {
        if ( isset( $rest['argument'] ) ) {
            $add[] =  $argument . '=' . $rest[$argument];
        }
    }

    if ( count( $add ) > 0 ) {
        $path = $path . '?' . implode( '&amp;', $add );
    }

    return $path;
    </xsl:if>
}
</xsl:template>

</xsl:stylesheet>
