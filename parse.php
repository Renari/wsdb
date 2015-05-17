<?php
class Parse{
  public static function parsepath()
  {
    $path = explode('/', $_SERVER['REQUEST_URI']);
    if (count($path) > 0) {
        $arrlength = count($path) - 1;
        $path[$arrlength] = explode('?', $path[$arrlength])[0];
    }
    return array_values(array_filter($path));
  }
}
