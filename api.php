<?php
class api
{
  //used to limit the results returned by a query
  const LIMIT = 10;
  //maximum allowed keywords
  const MAXKEYWORD = 10;
  public static function main( $path = NULL, $db = NULL)
  {
  if(is_null($path))
  {
    require_once('parse.php');
    $path = Parse::parsepath();
  }
  if(is_null($db))
  {
    $settings = json_decode(file_get_contents('settings.json'), true);
    //dateabase
    $db = new PDO($settings['database']['driver'].':'.
    'host='   .$settings['database']['host'].';'.
    'dbname=' .$settings['database']['name'].';'.
    'charset='.$settings['database']['charset'],
    $settings['database']['username'],
    $settings['database']['password']
  );
  }
  if (isset($path[1]))
  {
    switch($path[1])
    {
      case 'search':
      $limit = self::LIMIT;
      $input = urldecode($path[2]);
      //exact match
      $stmt = $db->prepare('SELECT `cardno`, `name` FROM
        `ws_cards` m1 WHERE `name` LIKE ? AND `cardno` =
        (SELECT MIN(m2.cardno) FROM `ws_cards` m2 WHERE m1.name = m2.name)
        ORDER BY m1.name LIMIT '.$limit);
      $stmt->setFetchMode(PDO::FETCH_ASSOC);
      $stmt->execute(array($input.'%'));
      $results = $stmt->fetchAll();
      $limit -= count($results);
      $keywords = explode(' ', $input);
      if (count($keywords) > self::MAXKEYWORD)
      $keywords = array_splice($keywords, 0, self::MAXKEYWORD);
      if(is_array($keywords))
      $keywords = array_map(function($v) { return '%'.$v.'%'; }, $keywords);
      //all keywords
      if($limit > 0 && count($keywords) > 1)
      {
        $query = 'SELECT `cardno`, `name` FROM `ws_cards` m1 WHERE ';
        for ($i=0; $i < count($keywords); $i++) {
          $query .= '`name` LIKE ? AND ';
        }
        $query .= '`cardno` = (SELECT MIN(m2.cardno) FROM
        `ws_cards` m2 WHERE m1.name = m2.name)';
        if(count($results) >= 1)
        {
          $query .= ' AND `cardno` NOT IN (?';
          for ($i=1; $i < count($results); $i++) {
            $query .= ', ?';
          }
          $query .= ')';
        }
        $query .= 'ORDER BY m1.name LIMIT '.$limit;
        $stmt = $db->prepare($query);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $param = array_merge($keywords,
        array_map(function($v){
          return $v['cardno'];
        }, $results));
        $stmt->execute($param);
        $results = array_merge($results, $stmt->fetchAll());
        $limit = self::LIMIT - count($results);
      }
      //any keywords
      if ($limit > 0)
      {
        $query = 'SELECT `cardno`, `name` FROM `ws_cards` m1 WHERE `name` LIKE ? ';
        for ($i=1; $i < count($keywords); $i++) {
          $query .= 'OR `name` LIKE ? ';
        }
        $query .= ' AND `cardno` = (SELECT MIN(m2.cardno) FROM
        `ws_cards` m2 WHERE m1.name = m2.name)';
        if(count($results) >= 1)
        {
          $query .= ' AND `cardno` NOT IN (?';
          for ($i=1; $i < count($results); $i++) {
            $query .= ', ?';
          }
          $query .= ')';
        }
        $query .= 'ORDER BY m1.name LIMIT '.$limit;
        $stmt = $db->prepare($query);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $param = array_merge($keywords,
        array_map(function($v){
          return $v['cardno'];
        }, $results));
        $stmt->execute($param);
        $results = array_merge($results, $stmt->fetchAll());
      }
      echo json_encode($results);
      break;
      case 'username':
      if (!isset($_POST['username']))
      {
        echo 'false';
        break;
      }
      if (strlen($_POST['username']) < 3) {
        echo 'false';
        break;
      }
      $stmt = $db->prepare('SELECT username FROM ws_users WHERE username = ?');
      $stmt->execute(array($_POST['username']));
      if ($stmt->fetch(PDO::FETCH_ASSOC))
      {
        echo 'false';
      }
      else
      {
        echo 'true';
      }
      break;
      }
    }
  }
}
