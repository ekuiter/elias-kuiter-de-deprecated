<?php

class GridImage {
  public function render($alt, $image) {
    $thumbnail = (new Thumbnail($image))->path(350);
    return <<<html
  <li class="no-margin">
    <a class="th" href="$image">
      <img src="$thumbnail" alt="$alt" data-caption="$alt" />
    </a>
  </li>
html;
  } 
}

?>
