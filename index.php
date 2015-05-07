<?php
require_once('card.php');
require_once('parse.php');
Site::main();

class Site{
  private static $settings;
  private static $db;
  private static $cssfiles = [
    'main.css' //primary css file
  ];
  private static $scriptlibs = [
    'jquery-2.1.3.min.js', //jquery
    'typeahead.bundle.js' //autocomplete (typeahead)
  ];
  private static $scripts = [
    'search.js'
  ];
  //builds the <head> element for the site
  protected static function head()
  {
    echo '<head>';
    echo '<meta charset="UTF-8">';
    foreach(self::$cssfiles as $css)
    {
      echo '<link rel="stylesheet" type="text/css" href="/css/'.$css.'">';
    }
    foreach (self::$scriptlibs as $script)
    {
      echo '<script src="/scripts/'.$script.'"></script>';
    }
    echo '</head>';
  }
  protected static function body()
  {
    echo '<body>';
    //this is where the magic happens
    $path = Parse::parsepath();
    if(isset($path[0]))
    {
      switch($path[0])
      {
        case "card":
          $cardno = Card::tocardno($path[1]);
          $stmt = self::$db->prepare('SELECT * FROM `ws_cards` WHERE `cardno` = ?');
          $stmt->execute(array($cardno));
          $card = $stmt->fetch();
          $card = new Card($card);
          echo Parse::parsetemplate('card', $card);
          break;
      }
    }
    else
    {
      //index
      echo Parse::parsetemplate('index', array());

    }
    foreach (self::$scripts as $script)
    {
      echo '<script src="/scripts/'.$script.'"></script>';
    }
    echo '</body>';
  }
  public static function main()
  {
    self::$settings = parse_ini_file('settings.ini', true);
    if(self::$settings['debug'])
    {
      require_once('/debug/krumo/krumo.php');
      //debug stylesheet
      self::$cssfiles[] = 'debug.css';
    }
    //dateabase
    self::$db = new PDO(self::$settings['database']['driver'].':'.
      'host='   .self::$settings['database']['host'].';'.
      'dbname=' .self::$settings['database']['name'].';'.
      'charset='.self::$settings['database']['charset'],
      self::$settings['database']['username'],
      self::$settings['database']['password']
    );
    //page output
    echo '<!DOCTYPE html><html>';
    echo self::head();
    echo self::body();
    echo '</html>';
  }
}
