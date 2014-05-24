<?php
  
class FolderGrid {
  function get_pages_for_folder_grid($renderers, &$pages, $folder) {
    foreach($renderers as $renderer) {
      if (is_array($renderer))
        $this->get_pages_for_folder_grid($renderer, $pages, $folder);
      elseif ($renderer instanceof PageRenderer && 
        (($renderer->directories == $folder && $renderer->page != 'info') || // pages that are in this directory
        ($renderer->directories != $folder && strstr($renderer->directories, $folder) && $renderer->page == 'info'))) // info pages in sub dir
        $pages[] = $renderer;
    }
  }
  
  public function render($folder) {
    $pages = array();
    $this->get_pages_for_folder_grid(Renderer::$renderers, $pages, $folder);
    @usort($pages, 'PageRenderer::sort_by_order');
    $output = '<ul class="large-block-grid-3">';
    foreach($pages as $page) {
      $title = $page->title();
      $link = $page->link();
      $image = $page->image();
      if ($image)
        $image = "<img src=\"$image\" alt=\"$title\" />";
      $output .= <<<code
      <li>
        <a class="th" href="$link">
          $image
    	    <p class="sub big">$title</p>
        </a>
      </li>
code;
    }
    return "$output</ul>";
  }
}
  
?>