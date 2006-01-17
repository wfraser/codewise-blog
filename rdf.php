<?php

/*
** RDF Feed Generator
** for CodewiseBlog Multi-User
**
** by Bill R. Fraser <bill.fraser@gmail.com>
** Copyright (c) 2005-2006 Codewise.org
*/

/*
** This file is part of CodewiseBlog
**
** CodewiseBlog is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** CodewiseBlog is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with CodewiseBlog; if not, write to the Free Software
** Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

header("Content-Type: text/xml");

echo "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";

define("NO_ACTION", TRUE);

require("cwbmulti.php");

?>

<rdf:RDF
        xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
        xmlns="http://purl.org/rss/1.0/"
        xmlns:dc="http://purl.org/dc/elements/1.1/"
        xmlns:taxo="http://purl.org/rss/1.0/modules/taxonomy/"
        xmlns:syn="http://purl.org/rss/1.0/modules/syndication/"
>

    <channel rdf:about="<?php echo INDEX_URL; ?>">
        <title><?php echo htmlspecialchars($BLOGINFO['title']); ?></title>
        <link><?php echo INDEX_URL; ?></link>
        <description><?php echo htmlspecialchars($BLOGINFO['title']); ?> :: by <?php echo htmlspecialchars($BLOGINFO['realname'] == NULL ? BLOGNAME : $BLOGINFO['realname']); ?></description>
        <dc:language>en-us</dc:language>
        <dc:rights>Copyright <?php echo $copyright_years; ?> - <?php echo htmlspecialchars($BLOGINFO['realname'] == NULL ? BLOGNAME : $BLOGINFO['realname']); ?></dc:rights>
        <dc:date>2005-10-16T02:00:01Z</dc:date>
        <dc:creator><?php echo htmlspecialchars($BLOGINFO['realname'] == NULL ? BLOGNAME : $BLOGINFO['realname']); ?></dc:creator>
        <items>
            <rdf:Seq>

<?php

if(isset($_GET['all_one_page']))
    $limit = "";
else
    $limit = "LIMIT 10"; // rdf spec says it must be 10
$q = $db->issue_query("SELECT tid,title,text,timestamp FROM topics WHERE blogid = '" . BLOGID . "' ORDER BY timestamp DESC $limit");
$data = $db->fetch_all($q);

foreach($data as $row)
{
    echo "                <rdf:li rdf:resource=\"" . INDEX_URL . "?tid=" . $row['tid'] . "\" />\n";
}

?>
            </rdf:Seq>
        </items>
    </channel>

<?php foreach($data as $row)
{
    echo "    <item rdf:about=\"" . INDEX_URL . "?tid=" . $row['tid'] . "\">
        <title>" . $row['title'] . "</title>
        <link>" . INDEX_URL . "?tid=" . $row['tid'] . "</link>
        <description>" . htmlspecialchars(textprocess($row['text'])) . "</description>
        <dc:date>" . iso8601_date($row['timestamp']) . "</dc:date>
    </item>";
}

?>

</rdf:RDF>