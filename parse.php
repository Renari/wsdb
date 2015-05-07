<?php
class Parse{
  public static function parsetemplate($template, $array)
  {
    $html = file_get_contents('./templates/'.$template.'.html');
    if (gettype($array) == 'object' && get_class($array) == 'Card')
    {
      $array = $array->getvars();
    }
    foreach($array as $key => $element)
    {
      if (is_array($element))
      {
        preg_match('|\{'.$key.'\}\[(\w*?):(.*?)\]|', $html, $matches);
        switch($matches[1])
        {
            case "del":
              $output = $element[0];
              for ($i=1; $i < count($element); $i++) {
                $output .= $matches[2].$element[$i];
              }
              $html = preg_replace('|\{'.$key.'\}\[.*?\]|', $output, $html);
              break;
            case "rep":
              $output = "";
              foreach ($element as $elem) {
                $output .= preg_replace('|\{elem\}|', $elem, $matches[2]);
              }
              $html = preg_replace('|\{'.$key.'\}\[.*?\]|', $output, $html);
              break;
        }
      }
      else if(!is_numeric($array))
        $html = preg_replace('|\{'.$key.'\}(?:\[.*?\])?|', $element, $html);
    }
    $html = preg_replace('|{.*?}|', '', $html);
    return $html;
  }
  public static function parsepath()
  {
    return array_values(array_filter(preg_split('|\/|', $_SERVER['REQUEST_URI'])));
  }
}
