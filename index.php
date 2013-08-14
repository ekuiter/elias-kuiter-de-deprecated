<?php

error_reporting(-1);
date_default_timezone_set('Europe/Berlin');

new Renderer();

class Renderer {

  private $page = 'home';
  public static $default_language = 'de';
  public static $language;
  private $obj;
  public static $nav = '';
  private static $renderers = array();

  public function __construct() {
    $this->dispatch();
    $this->navigation();
    $this->header();
    $this->body();
    $this->footer();
  }

  private function dispatch() {
    if (isset($_GET['p']))
        $this->page = str_replace('.', '', $_GET['p']);
	if (isset($_GET['l']))
        self::$language = $_GET['l'];
	switch (self::$language) {
		case 'en':
		case 'de':
			break;
		default:
			self::$language = self::$default_language;
	}
    $this->include_page();
  }

  private function include_page() {
    try {
	  $this->obj = new PageRenderer($this->page, self::$language);
	} catch (Exception $e) {
	  $this->obj = new PageRenderer('errors/404', self::$language);
	}
  }
  
  private function navigation() {
    $this->get_pages('', self::$renderers);
	$this->get_nav();
  }

  private function get_pages($dir, &$renderers) {
	$dir_handle = @opendir("pages$dir");
	if (!$dir_handle)
	  return;
	$renderers[count($renderers)] = array($dir);
	$renderers = &$renderers[count($renderers) - 1];
	while ($entry = readdir($dir_handle)) {
	  if ($entry != '.' && $entry != '..') {
		if (is_file("pages/$dir/$entry")) {
		  if ($renderer = PageRenderer::by_file("$dir/$entry"))
		    $renderers[] = $renderer;
		} else
		  $this->get_pages("$dir/$entry", $renderers);
	  }
	}
	closedir($dir_handle);
	@usort($renderers, 'PageRenderer::sort_by_order');
  }
  
  private function get_nav() {
    foreach (self::$renderers[0] as $entry) {
	  if (is_array($entry)) {
	    $info = PageRenderer::array_info($entry);
		if ($info) {
		  $this->add_nav_page($info, true, '.');
		  foreach ($entry as $entry2) {
		    if (is_array($entry2)) {
		      $info2 = PageRenderer::array_info($entry2);
			  if ($info2) {
			    $this->add_nav_page($info2, true);
		        foreach ($entry2 as $entry3)
		          $info2 == $entry3 ? 0 : $this->add_nav_page($entry3);
			    self::$nav .= '</ul></li>';
		      }
	        } else
	          $info == $entry2 ? 0 : $this->add_nav_page($entry2);
		  }
		  self::$nav .= '</ul></li>';
		}
	  } else
	    $this->add_nav_page($entry, false, '.');
	}
  }

  private function add_nav_link($title, $page, $dropdown = false) {
    self::$nav .= '<li class="' . ($this->page == str_replace('/', '', $page) ? 'active' : '') . ($dropdown ? ' has-dropdown' : '') .
      "\"><a href=\"$page\">$title</a>" . ($dropdown ? '<ul class="dropdown">' : '</li>');
  }
  
  private function add_nav_page($page, $dropdown = false, $add = '') {
    echo is_object($page) ? $this->add_nav_link(trim($page->title()).$add, $page->link(), $dropdown) : '';
  }
  
  public function link_to_language($language) {
	$language = ($language == self::$default_language ? '' : $language);
	$link = $this->obj->link(false);
	return "$link$language";
  }
  
  public static function latest_content($max, $sort_by) {	
    $renderers = array();
    foreach (self::$renderers[0] as $entry) {
	  if (is_array($entry)) {
	    $info = PageRenderer::array_info($entry);
		if ($info) {
		  $renderers[] = $info;
		 foreach ($entry as $entry2) {
		    if (is_array($entry2)) {
		      $info2 = PageRenderer::array_info($entry2);
			  if ($info2) {
			    $renderers[] = $info2;
		        foreach ($entry2 as $entry3)
		          $info2 == $entry3 ? 0 : (is_object($entry3) ? $renderers[] = $entry3 : 0);
			  }
	        } else
	          $info == $entry2 ? 0 : (is_object($entry2) ? $renderers[] = $entry2 : 0);
		  }
		}
	  } else
	     is_object($entry) ? $renderers[] = $entry : 0;
	}
	usort($renderers, "PageRenderer::sort_by_$sort_by");
	$output = '<ul class="large-block-grid-'.$max.' latest-content">';
	$i = 0;
	foreach($renderers as $renderer) {
	  if ($i >= $max)
	    break;
	  $title = $renderer->title();
	  $link = $renderer->link();
	  $day = date('j', $renderer->$sort_by);
	  switch (self::$language) {
	    case 'en':
		  $month = date('F', $renderer->$sort_by);
		  $date = "$day $month";
		  break;
		case 'de':
		  $months = array('Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember');
		  $month = $months[(int) date('m', $renderer->$sort_by) - 1];
		  $date = "$day. $month";
		  break;
	  } 
	  $output .= <<<code
	  <li><a class="th" href="$link"><strong>$title</strong><small>$date</small></a></li>
code;
      $i++;
	}
	return "$output</ul>";
  }

  private function header() {
    $title = $this->obj->title();
    $nav = self::$nav;
	$de = $this->link_to_language('de');
	$en = $this->link_to_language('en');
	$obj = new PageRenderer('home', self::$language);
	$root = $obj->link();
    echo <<<code
<!DOCTYPE html>
<html lang="de">
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<meta name="viewport" content="width=device-width" />
		<link rel="stylesheet" href="/assets/include/normalize.css" />
		<link rel="stylesheet" href="/assets/include/foundation.min.css" />
		<link rel="stylesheet" href="/assets/include/style.css" />
		<script type="text/javascript" src="/assets/include/custom.modernizr.js"></script>
		<script type="text/javascript" src="/assets/include/jquery.min.js"></script>
		<script type="text/javascript" src="/assets/include/turbolinks.min.js"></script>
		<script type="text/javascript" src="/assets/include/jwplayer.js"></script>
		<script type="text/javascript">
//<![CDATA[
		var _gaq = _gaq || [];
		_gaq.push(['_setAccount', 'UA-36930408-1']);
		_gaq.push(['_setDomainName', 'elias-kuiter.de']);
		_gaq.push(['_trackPageview']);

		(function() {
		var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		})();
		//]]>
		</script>

		<title>$title | app &amp; web design.</title>
		<link rel="icon" href="/assets/favicon.ico" type="image/x-icon" />
	</head>
	<body>
	    <nav class="top-bar">
		  <ul class="title-area">
			<li class="name">
		      <h1><a href="$root">app &amp; web design.<span id="loading" style="visibility:hidden"></span></a></h1>
		    </li>
            <li class="toggle-topbar menu-icon"><a href="#"><span></span></a></li>
          </ul>
	      <section class="top-bar-section">
		    <ul class="left">
	          $nav
	        </ul>
			<ul class="right">
			  <li><a href="$de"><img src="/assets/de.gif" alt="Deutsch" /></a></li>
			  <li><a href="$en"><img src="/assets/en.gif" alt="English" /></a></li>
			</ul>
	      </section>
		</nav>
	    <div class="row">
		  <div class="small-12 columns">
code;
  }

  private function body() {  
    echo "\n<h1>" . $this->obj->title() . '</h1>';   
    $this->obj->render();
  }

  private function footer() {
	$year = date('Y');
	switch (self::$language) {
	  case 'en':
	    $updated = date('j F Y', $this->obj->updated);
	    $footer = <<<code
		Last updated on $updated.<br />
	    Uses <a href="http://foundation.zurb.com/">Foundation 4</a>.<br />
        &copy; $year Elias Kuiter. <a href="/imprint/en">Imprint</a>.
code;
        break;
      case 'de':
	    $updated = date('d.m.Y', $this->obj->updated);
	    $footer = <<<code
        Zuletzt aktualisiert am $updated.<br />
	    Nutzt <a href="http://foundation.zurb.com/">Foundation 4</a>.<br />
        &copy; $year Elias Kuiter. <a href="/imprint/">Impressum</a>.
code;
	}
    echo <<<code
		</div>
    </div>
	<div class="row margin-top" style="text-align:right;color:#aaa">
	<p style="line-height:0.8em">
  <a href="http://www.w3.org/html/logo/">
  <img src="/assets/html5.png" width="131" height="32" alt="HTML5 Powered with CSS3 / Styling, Device Access, Multimedia, Performance &amp; Integration, and Semantics" title="HTML5 Powered with CSS3 / Styling, Device Access, Multimedia, Performance &amp; Integration, and Semantics">
  </a>
	<small style="display:block;float:right;margin-left:20px">
	  $footer
	</small>
	</p>
	</div>
    <script type="text/javascript" src="/assets/include/prettify.min.js"></script>
	<script type="text/javascript" src="/assets/include/foundation.min.js"></script>
    <script type="text/javascript">
      $(document).foundation();
    </script>
  </body>
  </html>
code;
  }

}

class PageRenderer {

  public $page;
  private $directories = '/';
  private $language;
  private $data;
  public $order;
  public $created;
  public $updated;

  public function __construct($page, $language = null) {
    //$page: directory/subdirectory/page ($language = en) - or - directory/subdirectory/page.en.html ($language = null)
    $array = explode('/', $page);
	  for ($i = 0; $i < (count($array) - 1); $i++)
	    if ($array[$i])
	      $this->directories .= "$array[$i]/";
    //$page: page - or - page.en.html
    $page = $array[count($array) - 1];
    if (!$language) {
	  $array = explode('.', $page);
	  // $page: page
	  $page = $array[0];
	  $language = $array[1];
	}
	$file = "pages/$this->directories$page.$language.html";
    if (!is_file($file))
	  throw new Exception("Page $file not found");
    $this->page = $page;
	$this->language = $language;
	$this->data = explode("\n", file_get_contents($file));
	$this->order = (int) $this->data[1];
	$this->created = filectime($file);
	$this->updated = filemtime($file);
  }

  public function title() {
    return $this->data[0];
  }
  
  public function render() {
    $data = $this->data;
	unset($data[0], $data[1], $data[2]);
	foreach ($data as &$row) {
	  if (strstr($row, 'render_widget[')) {
	    preg_match('|render_widget\[(.*)\]|Uis', $row, $widget);
		if (strstr($widget[1], 'latest_content')) {
		  preg_match('|latest_content\((.*)\)|Uis', $widget[1], $args);
		  $args = isset($args[1]) ? explode(',', $args[1]) : array();
		  $row = Renderer::latest_content(isset($args[0]) ? (int) trim($args[0]) : 4, isset($args[1]) ? trim($args[1]) : 'updated');
		}
	  }
	}
    echo implode("\n", $data);
  }
  
  public function link($language = true) {
    $language = $language ? ($this->language == Renderer::$default_language ? '' : $this->language) : '';
    return "$this->directories$this->page/$language";
  }
  
  public static function sort_by_order($obj1, $obj2) {
    if (is_array($obj1))
	  $obj1 = self::array_info($obj1);
	if (is_array($obj2))
	  $obj2 = self::array_info($obj2);
	if (is_string($obj1) || is_string($obj2))
	  return -1;
    return $obj1->order < $obj2->order ? -1 : ($obj1->order > $obj2->order ? 1 : 0);
  }
  
  public static function sort_by_created($obj1, $obj2) {
    return $obj1->created > $obj2->created ? -1 : ($obj1->created < $obj2->created ? 1 : 0);
  }
  
  public static function sort_by_updated($obj1, $obj2) {
    return $obj1->updated > $obj2->updated ? -1 : ($obj1->updated < $obj2->updated ? 1 : 0);
  }
  
  public static function array_info($array) {
    foreach ($array as $entry)
	  if (is_string($entry))
		  $directory = $entry;
	$directory = explode('/', $directory);
	$directory = $directory[count($directory) - 1];
	foreach ($array as $entry) {
	  if (is_object($entry) && $entry->page == 'info')
	    return $entry;
	}
	return false;
  }
  
  public static function by_file($file) {
    $array = explode('.', $file);
	if ($array[1] == Renderer::$language) {
	  $renderer = new PageRenderer($file);
	  if ($renderer->order)
		return $renderer;
	  else
	    return false;
	}
  }

}

?>