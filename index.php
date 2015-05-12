<?php
require_once('card.php');
require_once('parse.php');
require_once('twig/Autoloader.php');
Site::main();

class Site{
  private static $settings;
  private static $db;
  public static function main()
  {
    self::$settings = parse_ini_file('settings.ini', true);
    if(self::$settings['debug'])
    {
      require_once('debug/krumo/krumo.php');
      //debug stylesheet
      //self::$cssfiles[] = 'debug.css';
    }
    $tempvars['settings'] = self::$settings;
    //dateabase
    self::$db = new PDO(self::$settings['database']['driver'].':'.
    'host='   .self::$settings['database']['host'].';'.
    'dbname=' .self::$settings['database']['name'].';'.
    'charset='.self::$settings['database']['charset'],
    self::$settings['database']['username'],
    self::$settings['database']['password']);
    //page output
    Twig_Autoloader::register();
    $loader = new Twig_Loader_Filesystem('templates');
    $twigop['cache'] = './cache';
    if (self::$settings['debug'])
    {
      $twigop['cache'] = false;
    }
    $twig = new Twig_Environment($loader, $twigop);
    $path = Parse::parsepath();
    if(isset($path[0]))
    {
      switch($path[0])
      {
        case "card":
        $cardno = Card::tocardno($path[1]);
        $query = 'SELECT
        wc.cardno cardno, wc.name name, wc.kana kana, wc.rarity rarity, wc.side side, wc.color color,
        wc.type type, wc.level level, wc.cost cost, wc.power power, wc.soul soul, wc.triggers triggers,
        wc.traits traits, wc.text text, wc.flavor flavor, wc.locale locale, CASE
        WHEN wc.locale = "en" THEN we.NAME
        WHEN wc.locale = "jp" THEN wj.NAME
        END expansion FROM ws_cards wc
        LEFT JOIN ws_ensets we ON wc.expansion = we.id
        LEFT JOIN ws_jpsets wj ON wc.expansion = wj.id WHERE wc.cardno = ?';
        $stmt = self::$db->prepare($query);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute(array($cardno));
        $card = $stmt->fetch();
        $card = new Card($card);
        //check for another language version
        $relations = '';
        switch($card->getlocale())
        {
          case 'en':
          $relations = 'SELECT `jpcardno` FROM `ws_relations` WHERE `encardno` = ?';
          break;
          case 'jp':
          $relations = 'SELECT `encardno` FROM `ws_relations` WHERE `jpcardno` = ?';
          break;
        }
        $stmt = self::$db->prepare($relations);
        $stmt->execute(array($cardno));
        $altcard = $stmt->fetch();
        $tempvars['cards'][] = $card->getvars();
        if($altcard)
        {
          $stmt = self::$db->prepare($query);
          $stmt->setFetchMode(PDO::FETCH_ASSOC);
          $stmt->execute(array($altcard[0]));
          $altcard = $stmt->fetch();
          $altcard = new Card($altcard);
          $tempvars['cards'][] = $altcard->getvars();
        }
        $template = $twig->loadTemplate('card.html');
        echo $template->render($tempvars);
        break;
      }
    }
    else
    {
      $template = $twig->loadTemplate('index.html');
      echo $template->render(array());
    }
  }
}
