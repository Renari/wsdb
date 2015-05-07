<?php
require_once('../parse.php');
Query::main();
class Query
{
  //used to limit the results returned by a query
  private static $limit = 10;
  public static function main()
  {
    $settings = parse_ini_file('../settings.ini', true);
    //dateabase
    $db = new PDO($settings['database']['driver'].':'.
      'host='   .$settings['database']['host'].';'.
      'dbname=' .$settings['database']['name'].';'.
      'charset='.$settings['database']['charset'],
      $settings['database']['username'],
      $settings['database']['password']
    );
    $path = Parse::parsepath();
    if (isset($path[1]))
    {
      switch($path[1])
      {
        case 'search':
          $limit = self::$limit;
          $input = urldecode($path[2]);
          //exact match
          $stmt = $db->prepare('SELECT * FROM
            `ws_cards` m1 WHERE `name` LIKE ? AND `cardno` =
            (SELECT MIN(m2.cardno) FROM `ws_cards` m2 WHERE m1.name = m2.name)
            LIMIT '.$limit);
          $stmt->setFetchMode(PDO::FETCH_ASSOC);
          $stmt->execute(array($input.'%'));
          $results = $stmt->fetchAll();
          $limit -= count($results);
          $keywords = explode(' ', $input);
          if(is_array($keywords))
            $keywords = array_map(function($v) { return '%'.$v.'%'; }, $keywords);
          //all keywords
          if($limit > 0 && count($keywords) > 1)
          {
            $query = 'SELECT * FROM `ws_cards` m1 WHERE ';
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
            $query .= ' LIMIT '.$limit;
            $stmt = $db->prepare($query);
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $param = array_merge($keywords,
            array_map(function($v){
              return $v['cardno'];
            }, $results));
            $stmt->execute($param);
            $results = array_merge($results, $stmt->fetchAll());
            $limit = self::$limit - count($results);
          }
          //any keywords
          if ($limit > 0)
          {
            $query = 'SELECT * FROM `ws_cards` m1 WHERE `name` LIKE ? ';
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
            $query .= ' LIMIT '.$limit;
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
      }
    }
  }
}
