<?php
class DB {
  public static function connection(){
    return new PDO('mysql:host=localhost;dbname=mondrian', "mondrian", "mondrian");
  }
}
