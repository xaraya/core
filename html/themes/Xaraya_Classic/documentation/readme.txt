XARAYA_CLASSIC - DEFAULT THEME.. 
[revision 3 by andyv_at_xaraya.com]

The CSS a bit more interesting and a lot better organised than in the old original;

Differences so far are as follows:

    - improved readability and formatting of source
    - rules arranged according to page structure, layout and other properties
    - detailed comments for each class and selector
    - the page layouts should be rendered similar by a wide range of browsers
    - improved consistency for the theme typography
    - increased reliance on relative sizing and positioning
    - a few more advanced rules provided as examples of theme capabilities
    - cleaned up and removed unnecessary duplication of inheritable properties
    - introduced professional quality in colour co-ordination and graphics

additions and corrections as of Aug 15 - 2004 

    - text colours is in the web-safe range (should easier to read on low-res screen)
    - body background attachement changed to fixed
    - general cleanup

additions and corrections as of Oct 15 - 2004 

    - tried hard to achieve the source ordered 3-col layout, when the content comes first
    - general facelift undertaken
    - redone all backgrounds, the look seems clearer after removing the textures
    - all images are gifs now
    - new set of micro badges for the footer
    - added link to the p/shop source files archive for those who want them
    - gone totally tableless (Rabbitt said it's ok to disregard IE windows users ;-)
    - tried to eliminate the potential box-model problems and consequent hacks
    - redone the header - no longer we use the transparent gif to make it clickable
    - javascript style switcher is slightly modified for a better onload events handling
    - actually tested on FF, Moz, Safari, IE5-Mac, IE6-Win (no special linux tests yet)
    - added _undo_ browsers defaults section right at the beginning (just in case)


/*  occasionally may need to UNDO some of the default browsers styles, 
    so that we know exactly where we are */

body, div, ul, li, td, h1, h2, h3, h4, h5, h6, code, pre {
 font-size: 1em;
}
p, ul, ol, li, h1, h2, h3, h4, h5, h6, pre, form, blockquote, fieldset, input {
 margin: 0;
 padding: 0;
}
:link, :visited { text-decoration: none; }
ul, ol { list-style-type: none; }
a img, :link img, :visited img { border: none; }

/* DESCRIPTION OF INCLUDED STYLESHEETS */

style.css - main styles
layout.css - layout skeleton

styleswitcher related:

colstyle_blue.css - Blue'ish colours styles
colstyle_green.css - Green'ish colours styles
colstyle_orange.css - Orange'ish colours styles 
colstyle_highcontrast.css -  High contrast style

different text sizes:

style_textsmall.css
style_textmedium.css
style_textlarge.css

/* with the styleswitcher in this theme we mainly need to override/modify 
those attributes which influence colours, like color, background-color 
and background-image.. although it's possible to alter any other rule here,
lets keep these alternative stylesheets relatively small and easy to work with.
(some browsers dont like totally empty external css files)
------------------------------------------------------------------[important note] */

/* XARAYA REQUIRED CLASSES */
