<?php

/*
** RDF Feed Generator
** for CodewiseBlog Multi-User
**
** by William R. Fraser <wrf@codewise.org>
** Copyright (c) 2005-2008 Codewise.org
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

// prevent a SQL error if there are no topics
if($db->num_rows[$db->issue_query("SELECT tid FROM topics WHERE blogid = '".BLOGID."'")] > 0)
{
    $first_post = $db->fetch_var($db->issue_query("SELECT timestamp FROM topics WHERE blogid = '".BLOGID."' ORDER by TIMESTAMP ASC LIMIT 1"));
    $latest_post =  $db->fetch_var($db->issue_query("SELECT timestamp FROM topics WHERE blogid = '".BLOGID."' ORDER by TIMESTAMP DESC LIMIT 1"));
    $copyright_years = date("Y", $first_post) . "-" . date("Y", $latest_post);
} else {
    $copyright_years = date("Y");
}

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
        <dc:rights>Copyright (c) <?php echo $copyright_years; ?> <?php echo htmlspecialchars($BLOGINFO['realname'] == NULL ? BLOGNAME : $BLOGINFO['realname']); ?></dc:rights>
        <dc:date><?php echo iso8601_date($latest_post); ?></dc:date>
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
    echo "                <rdf:li rdf:resource=\"" . INDEX_URL . "article/" . string_to_url_goodness($row['title']) . "\" />\n";
}

?>
            </rdf:Seq>
        </items>
    </channel>

<?php foreach($data as $row)
{
    echo "    <item rdf:about=\"" . INDEX_URL . "article/" . string_to_url_goodness($row['title']) . "\">
        <title>" . $row['title'] . "</title>
        <link>" . INDEX_URL . "article/" . string_to_url_goodness($row['title']) . "</link>
        <description>" . htmlspecialchars(output_topic_text($row['text'])) . "</description>
        <dc:date>" . iso8601_date($row['timestamp']) . "</dc:date>
    </item>";
}

?>

</rdf:RDF>
