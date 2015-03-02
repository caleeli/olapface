<?php
class Measure {
  public static function getXmlOuter($_e){
    $res='<Measure name="'.$_e->getAttribute("name").'" column="'.$_e->getAttribute("name").'" aggregator="sum"
      formatString="Standard" />';
    return $res;
  }
}
