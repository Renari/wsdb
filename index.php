<?php
Site::main();

class Site{
  //css files used
  private static $cssfiles = [
    "main.css" //primary css file
  ];
  private static $scripts = [
    "jquery-2.1.3.min.js" //jquery
  ];
  //builds the <head> element for the site
  protected static function head()
  {
    echo "<head>";
    foreach(self::$cssfiles as $css)
      echo "<link rel='stylesheet' type='text/css' href='".$css."'>";
    foreach (self::$scripts as $script)
      echo "<script src='".$script."'></script>";
  }
  protected static function body()
  {
    //this is where the magic happens
  }
  public static function main()
  {
    //doctype
    echo "<!DOCTYPE html><html>";
    //output head element
    echo self::head();
    //output body
    echo self::body();
    //end page
    echo "</html>";
  }
}
?>
