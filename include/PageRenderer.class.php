<?php

class PageRenderer {

  public $page;
  public $directories = '/';
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
  
  public function link($language = true) {
    $language = $language ? ($this->language == Renderer::$default_language ? '' : $this->language) : '';
    return "$this->directories$this->page/$language";
  }
  
  public function image() {
    return stristr($this->data[2], '.jpg') || stristr($this->data[2], '.png') ? $this->data[2] : null;
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
    		  $row = (new LatestContent())->render(isset($args[0]) ? (int) trim($args[0]) : 4, isset($args[1]) ? trim($args[1]) : 'updated');
    		} elseif (strstr($widget[1], 'folder_grid')) {
  		    preg_match('|folder_grid\((.*)\)|Uis', $widget[1], $args);
  		    if (isset($args[1]))
  		      $row = (new FolderGrid())->render(trim($args[1]));
                } elseif (strstr($widget[1], 'feed')) {
                  preg_match('|feed\((.*)\)|Uis', $widget[1], $args);
    		  $args = isset($args[1]) ? explode(',', $args[1]) : array();                  
                  $row = (new Feed())->render(isset($args[0]) ? (int) trim($args[0]) : 4, isset($args[1]) ? trim($args[1]) : false);
                }
  	  }
  	}
    Renderer::$render .= implode("\n", $data);
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