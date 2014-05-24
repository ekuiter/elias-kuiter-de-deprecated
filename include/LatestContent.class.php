<?php
  
class LatestContent {
  public function render($max, $sort_by) {	
    $renderers = array();
    foreach (Renderer::$renderers[0] as $entry) {
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
      $image = $renderer->image();
      if ($image)
        $image = "<img src=\"$image\" alt=\"$title\" />";
      else
        continue;
  	  $date = Renderer::format_date($renderer->$sort_by);
  	  $output .= <<<code
        <li>
          <a class="th" href="$link">
            $image
            <p><strong>$title</strong><small>$date</small></p>
          </a>
        </li>
code;
      $i++;
  	}
  	return "$output</ul>";
  }
}
  
?>