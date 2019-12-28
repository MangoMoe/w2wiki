<?php

/*
 * W2
 *
 * Copyright (C) 2007-2011 Steven Frank <http://stevenf.com/>
 *
 * Code may be re-used as long as the above copyright notice is retained.
 * See README.txt for full details.
 *
 * Written with Coda: <http://panic.com/coda/>
 *
 * Updated to new version by Ionel BOBOC (Bobby)
 * 2019
 * https://github.com/iboboc/w2wiki
 *
 */
 
// Install PSR-4-compatible class autoloader
spl_autoload_register(function($class){
	require str_replace('\\', DIRECTORY_SEPARATOR, ltrim($class, '\\')).'.php';
});

// Get Markdown class
use Michelf\MarkdownExtra;

// User configurable options:
include_once "config.php";

ini_set('session.gc_maxlifetime', W2_SESSION_LIFETIME);

session_set_cookie_params(W2_SESSION_LIFETIME);
session_name(W2_SESSION_NAME);
session_start();

if ( count($allowedIPs) > 0 )
{
	$ip = $_SERVER['REMOTE_ADDR'];
	$accepted = false;
	
	foreach ( $allowedIPs as $allowed )
	{
		if ( strncmp($allowed, $ip, strlen($allowed)) == 0 )
		{
			$accepted = true;
			break;
		}
	}
	
	if ( !$accepted )
	{
		print "<html><body><h1>W2Wiki <small>Access from IP address $ip is not allowed</small></h1>";
		print "</body></html>";
		exit;
	}
}

if ( REQUIRE_PASSWORD && !isset($_SESSION['password']) )
{
	if ( !defined('W2_PASSWORD_HASH') || W2_PASSWORD_HASH == '' )
		define('W2_PASSWORD_HASH', sha1(W2_PASSWORD));
	
	if ( (isset($_POST['p'])) && (sha1($_POST['p']) == W2_PASSWORD_HASH) )
		$_SESSION['password'] = W2_PASSWORD_HASH;
	else
	{
		print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
		print "<html>\n";
		print "<head>\n";
		print "<link rel=\"apple-touch-icon\" href=\"icon.png\"/>";
		print "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, minimum-scale=1.0, user-scalable=false\" />\n";
		
		print "<link type=\"text/css\" rel=\"stylesheet\" href=\"" . BASE_URI . "/mini.css\" />\n";
		print "<title>Log In</title>\n";
		print "</head>\n";
		print "<body><div class=\"container\"><form method=\"post\">";
		print "<img src=\"icon.png\" >\n";
		print "<label for=\"password\">Password:</label>\n";
		print "<input type=\"password\" name=\"p\">\n";
		print "<input type=\"submit\" value=\"Go\"></form></div>";
		print "</body></html>";
		exit;
	}
}

// Support functions
function markdown_toc($file)
{

  // ensure using only "\n" as line-break
  $source = str_replace(["\r\n", "\r"], "\n", $file);

  // look for markdown TOC items
  preg_match_all(
    '/^(#).*$/m',
    $source,
    $matches,
    PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE
  );

  // preprocess: iterate matched lines to create an array of items
  // where each item is an array(level, text)
  $file_size = strlen($source);
  foreach ($matches[0] as $item) {
    $found_mark = substr($item[0], 0, 1);
    if ($found_mark == '#') {
      // text is the found item
      $item_text = $item[0];
      $item_level = strrpos($item_text, '#') + 1;
      $item_text = substr($item_text, $item_level);
    } else {
      // text is the previous line (empty if <hr>)
      $item_offset = $item[1];
      $prev_line_offset = strrpos($source, "\n", -($file_size - $item_offset + 2));
      $item_text =
        substr($source, $prev_line_offset, $item_offset - $prev_line_offset - 1);
      $item_text = trim($item_text);
      $item_level = $found_mark == '=' ? 1 : 2;
    }
    if (!trim($item_text) OR strpos($item_text, '|') !== FALSE) {
      // item is an horizontal separator or a table header, don't mind
      continue;
    }
    $raw_toc[] = ['level' => $item_level, 'text' => trim($item_text)];
  }
	$toc_text = "<h1>Table of Content</h1>";
	foreach ($raw_toc as $key => $value)
	{
		$toc_text .= "<a href=\"#".$value['text']."\">".$value['text']."</a><br>\n";
	}
	$toc_text .= "<hr>";
	return $toc_text;
}


function printDrawer()
{
	print "<label for=\"drawer-control\" class=\"drawer-toggle persistent\"></label>\n";
	print "<input type=\"checkbox\" id=\"drawer-control\" class=\"drawer persistent\">\n<div>\n";
	print "<label for=\"drawer-control\" class=\"drawer-close\"></label>\n";
	print "<h5><b>Markdown Syntax Helper</b>\n";
	print "<small>[TOC]</small>\n";	
	print "<small># Header 1</small>\n";
	print "<small>## Header 2</small>\n";
	print "<small>### Header 3</small>\n";
	print "<small>#### Header 4</small>\n";
	print "<small>##### Header 5</small>\n";
	print "<small>###### Header 6</small>\n\n";
	print "<small>**Bold**</small>\n";
	print "<small>*Emphasize*</small>\n";
	print "<small>++Underline++</small>\n";
	print "<small>~~Strikethrouh~~</small>\n";
	print "<small>==Highlight==</small>\n";
	print "<small>^Superscript^</small>\n";
	print "<small>~Subscript~</small>\n\n";
	print "<small>[[Link to page]]</small>\n";
	print "<small><http://example.com/></small>\n";
	print "<small>~[Alt text](http://url)</small>\n";
	print "<small>[link text](http://url)</small>\n\n";
	print "<small>{{uploadimagename}}</small>\n";
	print "<small>![Alt text](/path/to/img.jpg)</small>\n";
	print "<small>![Alt text](/path/to/img.jpg \"Optional title\")</small>\n\n";
	print "<small>- Unordered list</small>\n";
	print "<small>+ Unordered list</small>\n";
	print "<small>* Unordered list</small>\n";
	print "<small>1. Ordered list</small>\n\n";
	print "<small>>Blockquotes</small>\n";
	print "<small>    Code block</small>\n";
	print "<small>``Code``</small>\n\n";
	print "<small>*** Horizontal rule</small>\n";
	print "<small>--- Horizontal rule</small></h5>\n";
	print "</div>\n";
}


function printToolbar()
{
	global $upage, $page, $action;

	print "<header class=\"sticky\">";
 	print "<a class=\"logo\" href=\"" . SELF . "\">". DEFAULT_PAGE . "</a>";
	print "<a class=\"button first\" href=\"" . SELF . "?action=edit&amp;page=$upage\"><span class=\"icon-edit\"></span> Edit</a> ";
	print "<a class=\"button\" href=\"" . SELF . "?action=new\">New</a> ";

	if ( !DISABLE_UPLOADS )
		print "<a class=\"button\" href=\"" . SELF . VIEW . "?action=upload\"><span class=\"icon-upload\"></span> Upload</a> ";

 	print "<a class=\"button\" href=\"" . SELF . "?action=all_name\">All</a> ";
	print "<a class=\"button\" href=\"" . SELF . "?action=all_date\">Recent</a> ";
	print "<a class=\"button\" href=\"" . SELF . "?action=all_cards\">Cards</a> ";
 	
	if ( REQUIRE_PASSWORD )
		print '<a class="button" href="' . SELF . '?action=logout"><span class=\"icon-lock\"></span> Exit</a>';

	print "</header>\n";
}


function descLengthSort($val_1, $val_2) 
{ 
	$retVal = 0;

	$firstVal = strlen($val_1); 
	$secondVal = strlen($val_2);

	if ( $firstVal > $secondVal ) 
		$retVal = -1; 
	
	else if ( $firstVal < $secondVal ) 
		$retVal = 1; 

	return $retVal; 
}


function toHTML($inText)
{
	global $page;
	
	$dir = opendir(PAGES_PATH);
	while ( $filename = readdir($dir) )
	{
		if ( $filename{0} == '.' )
			continue;
			
		$filename = preg_replace("/(.*?)\.md/", "\\1", $filename);
		$filenames[] = $filename;
	}
	closedir($dir);
	
	uasort($filenames, "descLengthSort"); 

	if ( AUTOLINK_PAGE_TITLES )
	{	
		foreach ( $filenames as $filename )
		{
	 		$inText = preg_replace("/(?<![\>\[\/])($filename)(?!\]\>)/im", "<a href=\"" . SELF . VIEW . "/$filename\">\\1</a>", $inText);
		}
	}
	
 	$inText = preg_replace("/\[\[(.*?)\]\]/", "<a href=\"" . SELF . VIEW . "/\\1\">\\1</a>", $inText);
	$inText = preg_replace("/\{\{(.*?)\}\}/", "<img src=\"" . BASE_URI . "/images/\\1\" alt=\"\\1\" />", $inText);
	$inText = preg_replace("/message:(.*?)\s/", "[<a href=\"message:\\1\">email</a>]", $inText);

	$tocText = markdown_toc($inText);
	$inText = preg_replace("/\[TOC\]/", $tocText, $inText);

	$html = MarkdownExtra::defaultTransform($inText);
	$inText = htmlentities($inText);

	return $html;
}

function sanitizeFilename($inFileName)
{
	return str_replace(array('..', '~', '/', '\\', ':'), '-', $inFileName);
}

function destroy_session()
{
	if ( isset($_COOKIE[session_name()]) )
		setcookie(session_name(), '', time() - 42000, '/');

	session_destroy();
	unset($_SESSION["password"]);
	unset($_SESSION);
}

// Support PHP4 by defining file_put_contents if it doesn't already exist
if ( !function_exists('file_put_contents') )
{
    function file_put_contents($n, $d)
    {
		$f = @fopen($n, "w");
		
		if ( !$f )
		{
			return false;
		}
		else
		{
			fwrite($f, $d);
			fclose($f);
			return true;
		}
    }
}

// Main code
	global $text;

if ( isset($_REQUEST['action']) )
	$action = $_REQUEST['action'];
else 
	$action = 'view';

// Look for page name following the script name in the URL, like this:
// http://stevenf.com/w2demo/index.php/Markdown%20Syntax
//
// Otherwise, get page name from 'page' request variable.

if ( preg_match('@^/@', @$_SERVER["PATH_INFO"]) ) 
	$page = sanitizeFilename(substr($_SERVER["PATH_INFO"], 1));
else 
	$page = sanitizeFilename(@$_REQUEST['page']);

$upage = urlencode($page);

if ( $page == "" )
	$page = DEFAULT_PAGE;

$filename = PAGES_PATH . "/$page.md";

if ( file_exists($filename) )
{
	$text = file_get_contents($filename);
}
else
{
	if ( $action != "save" && $action != "all_name" && $action != "all_date" && $action != "upload" && $action != "new" && $action != "logout" && $action != "uploaded" && $action != "search" && $action != "view" )
	{
		$action = "edit";
	}
}

if ( $action == "edit" || $action == "new" )
{
	$formAction = SELF . (($action == 'edit') ? "/$page" : "");
	$html = "<form id=\"edit\" method=\"post\" action=\"$formAction\">\n";
	$html .= "<fieldset>\n";


	if ( $action == "edit" )
		$html .= "<input type=\"hidden\" name=\"page\" value=\"$page\" />\n";
	else
		$html .= "<label for=\"title\">Title</label><input id=\"title\" type=\"text\" name=\"page\" style=\"width:100%;\" />\n";


	if ( $action == "new" )
		$text = " ";

	$html .= "<textarea id=\"text\" name=\"newText\" style=\"width:100%;\" rows=\"" . EDIT_ROWS . "\">$text</textarea>\n";
	$html .= "<input type=\"hidden\" name=\"action\" value=\"save\" />";
	$html .= "<input id=\"save\" type=\"submit\" value=\"Save\" />\n";
	$html .= "<input id=\"cancel\" type=\"button\" onclick=\"history.go(-1);\" value=\"Cancel\" />\n";
	$html .= "</fieldset>\n";
	$html .= "</form>\n";

}
else if ( $action == "logout" )
{
	destroy_session();
	header("Location: " . SELF);
	exit;
}
else if ( $action == "upload" )
{
	if ( DISABLE_UPLOADS )
	{
		$html = "<p>Image uploading has been disabled on this installation.</p>";
	}
	else
	{
		$html = "<form id=\"upload\" method=\"post\" action=\"" . SELF . "\" enctype=\"multipart/form-data\"><p>\n";
		$html .= "<input type=\"hidden\" name=\"action\" value=\"uploaded\" />";
		$html .= "<input id=\"file\" type=\"file\" name=\"userfile\" style=\"display:none\" />\n";
		$html .= "<label for=\"file\" class=\"button\">Select file</label>";

		$html .= "<input id=\"upload\" type=\"submit\" value=\"Upload\" />\n";
		$html .= "<input id=\"cancel\" type=\"button\" onclick=\"history.go(-1);\" value=\"Cancel\" />\n";
		$html .= "</p></form>\n";
	}
}
else if ( $action == "uploaded" )
{
	if ( !DISABLE_UPLOADS )
	{
		$dstName = sanitizeFilename($_FILES['userfile']['name']);
		$fileType = $_FILES['userfile']['type'];
		preg_match('/\.([^.]+)$/', $dstName, $matches);
		$fileExt = isset($matches[1]) ? $matches[1] : null;
		
		if (in_array($fileType, explode(',', VALID_UPLOAD_TYPES)) &&
			in_array($fileExt, explode(',', VALID_UPLOAD_EXTS)))
		{
			$errLevel = error_reporting(0);

			if ( move_uploaded_file($_FILES['userfile']['tmp_name'], 
				BASE_PATH . "/images/$dstName") === true ) 
			{
				$html = "<span class=\"toast\">File '$dstName' uploaded</span>\n";
			}
			else
			{
				$html = "<span class=\"toast\">Upload error</span>\n";
			}

			error_reporting($errLevel);
		} else {
			$html = "<span class=\"toast\">Upload error: invalid file type</span>\n";
		}
	}

	$html .= toHTML($text);
}
else if ( $action == "save" )
{
	$newText = $_REQUEST['newText'];

	$errLevel = error_reporting(0);
	$success = file_put_contents($filename, $newText);
 	error_reporting($errLevel);

	if ( $success )	
		$html = "<span class=\"toast\">Saved!</span>";
	else
		$html = "<span class=\"toast\">Error saving changes! Make sure your web server has write access to " . PAGES_PATH . "</span>\n";

	$html .= toHTML($newText);
}


//experimental
else if ( $action == "delete" )
{
	$html = "<form id=\"delete\" method=\"post\" action=\"" . SELF . "\">";
	$html .= "<input id=\"title\" type=\"hidden\" name=\"page\" value=\"" . htmlspecialchars($page) . "\" ";
	$html .= "<p>".$text."</p>\n";

	$html .= "<input id=\"delete\" type=\"submit\" value=\"Delete\">";
	$html .= "<input id=\"cancel\" type=\"button\" onclick=\"history.go(-1);\" value=\"Cancel\" />\n";
	$html .= "<input type=\"hidden\" name=\"action\" value=\"deleted\" />";
	$html .= "<input type=\"hidden\" name=\"prevpage\" value=\"" . htmlspecialchars($page) . "\" />";
	$html .= "</p></form>";
}

else if ( $action == "deleted" )
{
	$filename = PAGES_PATH . "/$page.md";

	$errLevel = error_reporting(0);
	$success = unlink($filename);
 	error_reporting($errLevel);

	if ( $success )	
		$html = "<span class=\"toast\">Deleted</span>\n";
	else
		$html = "<span class=\"toast\">Error deleting file! Make sure your web server has write access to " . PAGES_PATH . "</span>\n";
}


else if ( $action == "rename" )
{
	$html = "<form id=\"rename\" method=\"post\" action=\"" . SELF . "\">";
	$html .= "<p>Title: <input id=\"title\" type=\"text\" name=\"page\" value=\"" . htmlspecialchars($page) . "\" />";
	$html .= "<input id=\"rename\" type=\"submit\" value=\"Rename\">";
	$html .= "<input id=\"cancel\" type=\"button\" onclick=\"history.go(-1);\" value=\"Cancel\" />\n";
	$html .= "<input type=\"hidden\" name=\"action\" value=\"renamed\" />";
	$html .= "<input type=\"hidden\" name=\"prevpage\" value=\"" . htmlspecialchars($page) . "\" />";
	$html .= "</p></form>";
}

else if ( $action == "renamed" )
{
	$pp = $_REQUEST['prevpage'];
	$pg = $_REQUEST['page'];

	$prevpage = sanitizeFilename($pp);
	$prevpage = urlencode($prevpage);
	
	$prevfilename = PAGES_PATH . "/$prevpage.md";

	if ( rename($prevfilename, $filename) )
	{
		// Success.  Change links in all pages to point to new page
		if ( $dh = opendir(PAGES_PATH) )
		{
			while ( ($file = readdir($dh)) !== false )
			{
				$content = file_get_contents($file);
				$pattern = "/\[\[" . $pp . "\]\]/g";
				preg_replace($pattern, "[[$pg]]", $content);
				file_put_contents($file, $content);
			}
		}
	}
	else
	{
		$html = "<p class=\"note\">Error renaming file</p>\n";
	}
}


else if ( $action == "all_name" )
{
	$dir = opendir(PAGES_PATH);
	$filelist = array();

	while ( $file = readdir($dir) )
	{
		if ( $file{0} == "." )
			continue;

		$afile = preg_replace("/(.*?)\.md/", "<a href=\"" . SELF . VIEW . "/\\1\">\\1</a>", $file);
		$efile = preg_replace("/(.*?)\.md/", "<a href=\"?action=edit&amp;page=\\1\">edit</a>", urlencode($file));
		$rfile = preg_replace("/(.*?)\.md/", "<a href=\"?action=delete&amp;page=\\1\">delete</a>", urlencode($file));
		$rmfile = preg_replace("/(.*?)\.md/", "<a href=\"?action=rename&amp;page=\\1\">rename</a>", urlencode($file));
		$sfile = filesize(PAGES_PATH . "/$file");
		$dfile = date(TITLE_DATE, filemtime(PAGES_PATH . "/$file"));

		array_push($filelist, "<tr><td data-label=\"File\">$afile</td><td data-label=\"Date\">$dfile</td><td data-label=\"Size\">$sfile</td><td data-label=\"Action\">$efile $rmfile $rfile</td></tr>");
	}

	closedir($dir);

	natcasesort($filelist);
	
	$html = "<table class=\"hoverable striped\"><thead><tr><th>File</th><th>Date</th><th>Size</th><th>Action</th></tr></thead><tbody>\n";

	for ($i = 0; $i < count($filelist); $i++)
	{
		$html .= $filelist[$i];
	}

	$html .= "</tbody></table>\n";
}


else if ( $action == "all_date" )
{
	$html = "<table class=\"hoverable striped\"><thead><tr><th>File</th><th>Date</th><th>Size</th><th>Action</th></tr></thead><tbody>\n";

	$dir = opendir(PAGES_PATH);
	$filelist = array();
	while ( $file = readdir($dir) )
	{
		if ( $file{0} == "." )
			continue;
			
		$filelist[$file] = filemtime(PAGES_PATH . "/$file");

	}

	closedir($dir);

	arsort($filelist, SORT_NATURAL);

	foreach ($filelist as $key => $value)
	{
		$afile = preg_replace("/(.*?)\.md/", "<a href=\"" . SELF . VIEW . "/\\1\">\\1</a>", $key);
		$efile = preg_replace("/(.*?)\.md/", "<a href=\"?action=edit&amp;page=\\1\">edit</a>", urlencode($key));
		$rfile = preg_replace("/(.*?)\.md/", "<a href=\"?action=delete&amp;page=\\1\">delete</a>", urlencode($key));
		$rmfile = preg_replace("/(.*?)\.md/", "<a href=\"?action=rename&amp;page=\\1\">rename</a>", urlencode($key));
		$sfile = filesize(PAGES_PATH . "/$key");

		$html .= "<tr><td data-label=\"File\">$afile</td><td data-label=\"Date\">" . date(TITLE_DATE, $value) . "</td><td data-label=\"Size\">$sfile</td><td data-label=\"Action\">$efile $rmfile $rfile</td></tr>\n";
		
	}
	$html .= "</tbody></table>\n";
}

else if ( $action == "all_cards" )
{
	$html = "<div class=\"row\">\n";

	$dir = opendir(PAGES_PATH);
	$filelist = array();
	while ( $file = readdir($dir) )
	{
		if ( $file{0} == "." )
			continue;
			
		$filelist[$file] = filemtime(PAGES_PATH . "/$file");

	}

	closedir($dir);

	arsort($filelist, SORT_NATURAL);


		$html .= "<div class=\"container\"><div class=\"row\">";

	foreach ($filelist as $key => $value)
	{
		$afile = preg_replace("/(.*?)\.md/", "<a href=\"" . SELF . VIEW . "/\\1\">\\1</a>", $key);
		$efile = preg_replace("/(.*?)\.md/", "<a href=\"?action=edit&amp;page=\\1\">edit</a>", urlencode($key));
		$rfile = preg_replace("/(.*?)\.md/", "<a href=\"?action=delete&amp;page=\\1\">delete</a>", urlencode($key));
		$rmfile = preg_replace("/(.*?)\.md/", "<a href=\"?action=rename&amp;page=\\1\">rename</a>", urlencode($key));
		$sfile = filesize(PAGES_PATH . "/$key");

		$html .= "<div class=\"col-sm-12\"><div class=\"card fluid\">";
		$html .= "<p class=\"doc\">". date(TITLE_DATE, $value) ."</p>";
		$html .= "<h3 class=\"doc\">$afile</h3>";
		$html .= "<p class=\"doc\">Size: $sfile</p>";
		$html .= "<p class=\"doc\">$efile $rmfile $rfile</p></div>";
		
	}
	$html .= "</div></div></div>\n";
}


else if ( $action == "search" )
{
	$matches = 0;
	$q = $_REQUEST['q'];
	$html = "<h1>Search: $q</h1>\n<ul>\n";

	if ( trim($q) != "" )
	{
		$dir = opendir(PAGES_PATH);
		
		while ( $file = readdir($dir) )
		{
			if ( $file{0} == "." )
				continue;

			$text = file_get_contents(PAGES_PATH . "/$file");
			
                        if ( preg_match("/{$q}/i", $text) || preg_match("/{$q}/i", $file) )
			{
				++$matches;
				$file = preg_replace("/(.*?)\.md/", "<a href=\"" . SELF . VIEW . "/\\1\">\\1</a>", $file);
				$html .= "<li>$file</li>\n";
			}
		}
		
		closedir($dir);
	}

	$html .= "</ul>\n";
	$html .= "<p>$matches matched</p>\n";
}
else
{
	global $text;
	$html = toHTML($text);
}

$datetime = '';

if ($action == "all_name")
	$title = "All Pages";

else if ($action == "all_date")
	$title = "Recent Pages";
	
else if ( $action == "upload" )
	$title = "Upload Image";

else if ( $action == "new" )
	$title = "New";

else if ( $action == "search" )
	$title = "Search";

else
{
	$title = $page;

	if ( TITLE_DATE )
	{
		$datetime = "<span class=\"titledate\">" . date(TITLE_DATE, @filemtime($filename)) . "</span>";
	}
}

// Disable caching on the client (the iPhone is pretty agressive about this
// and it can cause problems with the editing function)
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n";
print "<html>\n";
print "<head>\n";
print "<link rel=\"apple-touch-icon\" href=\"icon.png\"/>";
print "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, minimum-scale=1.0, user-scalable=false\" />\n";

print "<link type=\"text/css\" rel=\"stylesheet\" href=\"" . BASE_URI . "/mini.css\" />\n";
print "<title>$title</title>\n";
print "</head>\n";
print "<body>\n";

printToolbar();

print "<header class=\"sticky\">";
print "<span class=\"logo\">$title</span>\n";
if ($datetime == "")
 $datetime= date(TITLE_DATE);
print "<span class=\"button\">$datetime</span>\n";
printDrawer();
print "</header>";

print "<div class=\"main\">\n";
print "$html\n";
print "</div>\n";

print "<form method=\"post\" action=\"" . SELF . "?action=search\">\n";
print "<input class=\"button\" placeholder=\"Search\" id=\"search\" type=\"text\" name=\"q\" /><input id=\"ok\" type=\"submit\" value=\"Search\" /></form>\n";

print "<footer class=\"sticky\">\n";
print "<p><img src=\"icon.png\" ></p>\n";
print "<p>". FOOTER_TEXT . "</p>\n";
print "</footer>\n";

print "</body>\n";
print "</html>\n";

?>
