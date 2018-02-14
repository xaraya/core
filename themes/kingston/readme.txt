Welcome to the Kingston (http://en.wikipedia.org/wiki/Kingston,_Jamaica) Administrative theme for Xaraya's (xaraya.com) 2.x Jamaica branch.

This is alpha release 0.0.1 and at this point is released primarily for purposes of feedback, though it is fully functional on my Jamaica install. The plan is to iterate until it is stable and cross-browser compatible for Mozilla, Webkit, and IE9. Feedback is very welcome at http://www.xaraya.com/index.php?module=roles&func=email&uid=7535.

Important! Kingston is currently only intended for backend admin use and provides no styles for the frontend.

Notes for Kingston 0.0.1.
¥ For best results, in blocks add template "outergroup;innergroup" to the "admin" block, and "outerblock;innerblock" to the "adminpanel" block. This is necessary because as far as I can tell, it is not currently possible to override block templates.
¥ Kingston uses three freely available custom fonts, Letterica for a condensed width and Quicksand to mimic the Xaraya logotype. @font-face TTF fonts are only supported in recent versions of Webkit and Mozilla based browsers. EOT fonts will be added for Internet Explorer in beta.
¥ Kingston relies on the nth-child pseudo class which, again, requires a modern browser.
¥ Rounded corners and gradients have only been implemented for Webkit in this release. -moz style declarations will be added in 0.0.2.
¥ An attempt has been made to use as few custom templates as possible (e.g. admin-overview.xt). This was disappointingly difficult and further attempts to reduce dependence on non-core files will follow.

This is my first template release, so please forgive any obvious oversights in this distribution.

Regards, and enjoy.

Nathan Jacobson
admin@bhold.biz
bhold.biz