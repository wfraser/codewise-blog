function show(id)
{
    document.getElementById("show"+id).style.display = "none";
    document.getElementById("hide"+id).style.display = "";
    document.getElementById(id).style.display = "";
}

function hide(id)
{
    document.getElementById("show"+id).style.display = "";
    document.getElementById("hide"+id).style.display = "none";
    document.getElementById(id).style.display = "none";
}

function hidebio()
{
    hide("bio");
}

function showbio()
{
    show("bio");
}