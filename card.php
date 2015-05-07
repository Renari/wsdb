<?php
class Card{
  private $cardno;
  private $name;
  private $kana;
  private $expansion;
  private $side;
  private $color;
  private $level;
  private $cost;
  private $power;
  private $soul;
  private $triggers;
  private $traits;
  private $text;
  private $flavor;
  private $locale;
  private $image;
  function __construct($card)
  {
    foreach ($card as $key => $value) {
      $this->{$key} = $value;
    }
    if(isset($this->traits))
      $this->traits = unserialize($this->traits);
    if(isset($this->triggers))
      $this->triggers = unserialize($this->triggers);
    $this->image = self::toimage($this->cardno).'.gif';
  }
  public static function tourl($cardno)
  {
    return strtolower(preg_replace('|[\/_]|', '-', $cardno));
  }
  public static function tocardno($cardno)
  {
    $cardno = preg_replace('|[-_]|', '/', $cardno, 1);
    $cardno = preg_replace('|_|', '-', $cardno);
    return strtoupper($cardno);
  }
  public static function toimage($cardno)
  {
    return strtolower(preg_replace('|[-\/]|', '_', $cardno));
  }
  public function getvars()
  {
    return get_object_vars($this);
  }
}
