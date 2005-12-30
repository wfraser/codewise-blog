function getScrollX()
{
    if(typeof(window.pageXOffset) == 'number')
    {
        return window.pageXOffset;
    } else if(document.body && document.body.scrollLeft) {
        return document.body.scrollLeft;
    } else if(document.documentElement && document.documentElement.scrollLeft) {
        return document.documentElement.scrollLeft;
    }
}

function getScrollY()
{
    if(typeof(window.pageYOffset) == 'number')
    {
        return window.pageYOffset;
    } else if(document.body && document.body.scrollTop) {
        return document.body.scrollTop;
    } else if(document.documentElement && document.documentElement.scrollTop) {
        return document.documentElement.scrollTop;
    }
}

function autoResize(internal)
{
    // this script causes an infinite loop in Konqueror
    if(navigator.appName == "Konqueror")
    {
        alert("this feature does not work under Konqueror");
        return;
    }

    /*
    ** Step 1: resize to 1,1
    ** Step 2: read scrollbar info (use this as the limit)
    ** Step 3: (for x and y) expand until scrollbar info is greater than limit
    ** Step 4: step back one x and y.
    ** Step 5: done!
    */

    // hide the preview first
    var preview = document.getElementById("preview");
    if(preview)
        preview.style.display = "none";

    var canvas = document.getElementById("canvas");
    var cols = document.getElementById("cols");
    var rows = document.getElementById("rows");

    canvas.cols = 1;
    canvas.rows = 1;

    window.scrollTo(1e6, 1e6); // that should be high enough ;)

    var sxmax = getScrollX();
    var symax = getScrollY();

    var xdone = false;

    while(true) {

        window.scrollTo(1e6,1e6);

        var sx = getScrollX();
        var sy = getScrollY();

        if(sx <= sxmax)
        {
            canvas.cols++;
        }

        if(sy <= symax)
        {
            canvas.rows++;
        }

        if(sx > sxmax && sy > symax)
        {
            canvas.cols--;
            canvas.rows--;

            if(canvas.rows < 25)
                canvas.rows = 25;
            if(canvas.cols < 80)
                canvas.cols = 80;

            cols.value = canvas.cols;
            rows.value = canvas.rows;

            // unhide the preview
            //document.getElementById("preview").style.display = "";
            if(preview)
                preview.style.display = "";

            // scroll to the bottom
            window.scrollTo(1e6,1e6);

            // all done :)
            return;
        }
    }

}
