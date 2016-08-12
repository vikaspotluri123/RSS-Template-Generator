<?php

define("DEBUG", isset($_GET['debug']) && $_GET['debug'] !=0 ? TRUE : FALSE); //If Query arg debug is != 0 and has a value, enable debug mode

$RSS_URL = "https://roundrockisd.org/category/enews/feed/";
$num_articles = 4;
$max_words = 60;
$template = '<table style="margin-top: 20px; margin-left: 12px;" width="526"><tbody><tr><td width="50%"><a href="{{PERMALINK}}" style="text-decoration:none;border:none;"><img src="{{IMAGE}}" alt="{{IMAGE_ALT}}" style="display: block; line-height: 19px;" width="236" height="236"></a></td><td style="padding-top: 0px; padding-right: 5px; padding-bottom: 5px; padding-left: 5px;" valign="top" width="50%"><h2 style="font-family: Helvetica,Arial,sans-serif !important;font-size:21px;font-weight:normal;color:#00948d;margin-bottom:0px;"><span style="font-weight: normal; margin-bottom: 0px; font-size: 21px; color: rgb(203, 96, 21);"><a href="{{PERMALINK}}" style="text-decoration:none;color:#00948d;"><strong>{{TITLE}}</strong></a></span></h2><p style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #000; line-height: 19px;">{{CONTENT}}</p></td></tr></tbody></table>'; //Default Template
$eTemplate = '<table bgcolor="#eeece8" style="margin-top: 20px; margin-left: 12px;" width="526"><tbody><tr><td width="50%"><a href="{{PERMALINK}}" style="text-decoration:none;border:none;"><img src="{{IMAGE}}" alt="{{IMAGE_ALT}}" style="display: block; line-height: 19px;" width="236" height="236"></a></td><td style="padding-top: 0px; padding-right: 5px; padding-bottom: 5px; padding-left: 5px;" valign="top" width="50%"><h2 style="font-family: Helvetica,Arial,sans-serif !important;font-size:21px;font-weight:normal;color:#00948d;margin-bottom:0px;"><span style="font-weight: normal; margin-bottom: 0px; font-size: 21px; color: rgb(203, 96, 21);"><a href="{{PERMALINK}}" style="text-decoration:none;color:#00948d;"><strong>{{TITLE}}</strong></a></span></h2><p style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #000; line-height: 19px;">{{CONTENT}}</p></td></tr></tbody></table>'; //Template for Even Rows (adds grey background)

function print_debug($info,$break = true)
{
    $print = $break ? "<br/>".PHP_EOL : "";
    echo DEBUG ? $info.$print : "";
}

function truncate($text, $max = 100)
{
    return str_word_count($text, 0) > $max ? substr($text, 0, array_keys(str_word_count($text, 2))[$max]) . '...' : $text;
}

function getHTML($title, $content, $perma, $image, $alt, $starter)
{
    //Replaces the handlebars ({{VARIABLE}}) with the content. Can be done with one line, but it looks cleaner like this. 
    
    global $template, $max_words;
    $start = $starter ? $starter : $template;
    $title = str_replace("Round Rock ISD","RRISD",$title);
    $ret = str_replace("{{TITLE}}",truncate($title,$max_words),$start);
    $ret = str_replace("{{CONTENT}}",truncate($content,$max_words),$ret);
    $ret = str_replace("{{PERMALINK}}",truncate($perma,$max_words),$ret);
    $ret = str_replace("{{IMAGE}}",truncate($image,$max_words),$ret);
    $ret = str_replace("{{IMAGE_ALT}}",truncate($alt,$max_words),$ret);
    return $ret;
}

print_debug("Starting RSS Script");
print_debug("Loading RSS Library...","");
require(__DIR__."/inc/rss_fetch.inc");
print_debug("done");
print_debug("Getting RSS Feed (and parsing as RSS)...","");
$rss = fetch_rss(filter_var($RSS_URL, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)); //Code to load the RSS from $RSS_URI
print_debug("done");
print_debug("Checking count...","");
$num_articles = (count($rss->items) >= $num_articles) ? $num_articles : count($rss->items); //Make sure there are enough feeds. Otherwise, set $num_articles as the number of feeds
print_debug("done. Number of articles: {$num_articles}");

for($i = 0; $i < $num_articles; $i++)
{
    global $template, $eTemplate; //Load both options for templates
    $now = $rss->items[$i]; //Get current 
    $parser = new DOMDocument; //Create a DOM Parser
    $parser->loadHTML($now["description"]); //Load the description into rss
    
    $title = $now["title"]; //Get the title from RSS
    $permalink = $now["link"]; //Get the link from RSS
    
    $img = $parser->getElementsByTagName("img")->item(0); //Get First Image from desc.
    $image = $img->getAttribute("src"); //Get the source from the Parser
    $image_alt = $img->getAttribute("alt"); //Get Alt Text from Parser
    
    foreach($parser->getElementsByTagName("img") as $im): //Remove all image tags (only html entities in parser)
        $im->parentNode->removeChild($im);
    endforeach;
    
    //$desc = html_entity_decode($parser->textContent); //All that's left is the description. Convert escaped html into normal content
    $desc = $parser->textContent; //NOT unescaping characters. ^ is. All that's left is the description. Convert escaped html into normal content
    
    $t = $i % 2 === 0 ? $template : $eTemplate; //What color row?
    if(isset($_GET["view"]) && $_GET["view"] != 0) echo getHTML($title, $desc, $permalink, $image, $image_alt,$t);
    else echo htmlentities(getHTML($title, $desc, $permalink, $image, $image_alt,$t));
}

if(isset($_GET["view"]) && $_GET["view"] != 0):
    echo "<p><a href='?view=0'>Code view</a></p>";
else:
    echo "<p><a href='?view=1'>Preview</a></p>";
endif;