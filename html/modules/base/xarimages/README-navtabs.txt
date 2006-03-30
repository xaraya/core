3D Navtabs for Xaraya
---------------------

This idea was taken from http://www.alistapart.com/articles/slidingdoors/ .  The images
were taken directly from this example, and the CSS was converted to work WITHOUT changing
anything in the navtabs templates.

The colors were adapted to mimic the colors of the older Xaraya navtabs.  Image
manipulation was performed with The GIMP.  All images were first converted to greyscale.

The red color of the active tab was achieved as follows.  This applies only to
navtabs_left_on.gif and navtabs_right_on.gif.  These steps could be repeated to
achieve a different color suited to your own tastes:

1. Flood fill the straight parts of the borders using the Bucket tool.
2. Create a new transparent layer on top of the background layer.  Make sure this layer
   is selected during the next several steps.
3. Using a 13px diameter solid brush, add a circle of your preferred color to the
   corner of the image, so that the edges of the circle precisely line up with the
   corner.
4. Reduce the size of the brush to 11px diameter and change its color to white.  Add
   a circle to the exact center of the previous circle.
5. Use the eraser tool to remove the parts of these circles that do not make up the
   corner of the image.  Don't forget to erase the white area remaining from the center
   of the circles.  You can use the "select continuous regions" tool (the magic wand) 
   to select that area and press Ctrl-X to delete it.
6. Merge this layer down with the background layer, convert the image mode to Indexed,
   and save.  You're done!

-curtisdf, March 2006










