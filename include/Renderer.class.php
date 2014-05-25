<?php
  
class Renderer {

  private $page = 'home';
  public static $default_language = 'de';
  public static $language;
  private $obj;
  public static $nav = '';
  public static $renderers = array();
  public static $months_de = array('Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
                                   'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember');
  public static $months_en = array('January', 'February', 'March', 'April', 'May', 'June',
                                   'July','August', 'September', 'October', 'November', 'December');
  public static $render;

  public function __construct() {
    $this->dispatch();
    $this->navigation();
    $this->header();
    $this->body();
    $this->footer();
    echo self::$render;
  }

  private function dispatch() {
    if (isset($_GET['refetch']))
      (new Feed())->fetch(true);
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
    self::$render .= is_object($page) ? $this->add_nav_link(trim($page->title()).$add, $page->link(), $dropdown) : '';
  }
  
  public function link_to_language($language) {
  	$language = ($language == self::$default_language ? '' : $language);
  	$link = $this->obj->link(false);
  	return "$link$language";
  }
  
  public static function format_date($timestamp) {
    if (date('d.m.Y', $timestamp) == date('d.m.Y')) {
      switch (self::$language) {
      case 'en':
        return 'Today';
      case 'de':
        return 'Heute';
      }
    }
    if (date('d.m.Y', $timestamp) == date("d.m.Y", strtotime("yesterday"))) {
      switch (self::$language) {
      case 'en':
        return 'Yesterday';
      case 'de':
        return 'Gestern';
      }
    }
	  $day = date('j', $timestamp);
	  switch (self::$language) {
	  case 'en':
		  $month = date('F', $timestamp);
		  $date = "$day $month";
		  break;
		case 'de':
		  $month = self::$months_de[(int) date('m', $timestamp) - 1];
		  $date = "$day. $month";
		  break;
	  }
    return $date;
  }

  private function header() {
    $title = $this->obj->title();
    $nav = self::$nav;
  	$de = $this->link_to_language('de');
  	$en = $this->link_to_language('en');
  	$obj = new PageRenderer('home', self::$language);
  	$root = $obj->link();
    self::$render .= <<<code
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
    self::$render .= "\n<h1>" . $this->obj->title() . '</h1>';   
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
  $language = self::$language;
    self::$render .= <<<code
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
  
?>