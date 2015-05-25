<?php
require_once('card.php');
require_once('parse.php');
require_once('Twig/Autoloader.php');
require_once('login/Auth.php');
session_start();
Site::main();

class Site{
  private static $settings;
  private static $db;
  public static function main()
  {
    self::$settings = json_decode(file_get_contents('settings.json'), true);
    if(self::$settings['debug'])
    {
      require_once('debug/krumo/krumo.php');
    }
    $tempvars['settings'] = self::$settings;
    $auth = new Hybrid_Auth(self::$settings['auth']);
    //dateabase
    self::$db = new PDO(self::$settings['database']['driver'].':'.
    'host='   .self::$settings['database']['host'].';'.
    'dbname=' .self::$settings['database']['name'].';'.
    'charset='.self::$settings['database']['charset'],
    self::$settings['database']['username'],
    self::$settings['database']['password']);
    //check login stuff
    if (isset($_POST['login'])) {
      switch ($_POST['login']) {
        case 'twitter':
        $_SESSION['provider'] = 'Twitter';
        break;
        case 'google':
        $_SESSION['provider'] = 'Google';
        break;
        case 'facebook':
        $_SESSION['provider'] = 'Facebook';
        break;
        case 'steam':
        $_SESSION['provider'] = 'Steam';
        break;
      }
      $auth->authenticate($_SESSION['provider']);
    }
    if (isset($_SESSION['provider']) && Hybrid_Auth::isConnectedWith($_SESSION['provider'])) {
      $user = $auth->authenticate($_SESSION['provider']);
      $profile = $user->getUserProfile();
      $query = 'SELECT id, username FROM ws_users WHERE provider = ? AND provider_id = ?';
      $stmt = self::$db->prepare($query);
      $stmt->execute(array($_SESSION['provider'], $profile->identifier));
      if($user = $stmt->fetch(PDO::FETCH_ASSOC))
      {
        $_SESSION['user'] = $user;
        unset($_SESSION['provider']);
      }
      else
      {
        if (isset($_POST['username']) && preg_match('|^[a-zA-Z]{3,15}$|', $_POST['username']))
        {
          $stmt = self::$db->prepare('INSERT INTO ws_users (provider, provider_id, username) VALUES (?,?,?)');
          $stmt->execute(array($_SESSION['provider'], $profile->identifier, $_POST['username']));
          $_SESSION['user'] = array(self::$db->lastInsertId(), $_POST['username']);
          unset($_SESSION['provider']);
        }
        else
        {
          $tempvars['user'] = $profile;
          $tempvars['validated'] = false;
        }
      }
    }
    //page output
    Twig_Autoloader::register();
    $loader = new Twig_Loader_Filesystem('templates');
    $twigop['cache'] = getcwd().'/cache';
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
        $stmt->execute(array($cardno));
        $card = $stmt->fetch(PDO::FETCH_ASSOC);
        $card = new Card($card);
        //check for another language version
        $relations;
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
        $altcard = $stmt->fetch(PDO::FETCH_NUM);
        $tempvars['card'] = $card->getvars();
        if($altcard)
        {
          $tempvars['altcard'] = Card::tourl($altcard[0]);
        }
        //get alternate rarities
        $rarities = 'SELECT `cardno` FROM `ws_cards` WHERE `name` = ? AND `cardno` != ?';
        $stmt = self::$db->prepare($rarities);
        $stmt->execute(array($card->getname(), $card->getcardno()));
        $altrarity = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($altrarity as $value) {
          $value['cardlink'] = Card::tourl($value['cardno']);
          $tempvars['altrarity'][] = $value;
        }
        $template = $twig->loadTemplate('card.html');
        echo $template->render($tempvars);
        break;
        case 'login':
        require_once( "login/Endpoint.php" );
        Hybrid_Endpoint::process();
        break;
      }
    }
    else
    {
      $template = $twig->loadTemplate('index.html');
      echo $template->render($tempvars);
    }
  }
}
