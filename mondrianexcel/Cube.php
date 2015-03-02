<?php
class Cube {
  public $measures;
  public $dimensions=array();

  public static function getXmlOuter($_e){
    $res='<Cube name="'.$_e->getAttribute("name").'">
      <Table name="'.$_e->getAttribute("name").'" />
      <DimensionUsage name="Time" source="Time" foreignKey="time_id"/>';
      foreach($_e->childNodes as $_ch){
        if($_ch->nodeName=='Dimension'){
          $res.=Dimension::getXmlOuter($_ch);
        }
      }
      foreach($_e->childNodes as $_ch){
        if($_ch->nodeName=='Measure'){
          $res.=Measure::getXmlOuter($_ch);
        }
      }
      $res.='</Cube>';
    return $res;
  }
  //$_e=dimension
  public function createDimensionTable($_e){
    $db=DB::connection();
    $name=$_e->getAttribute("name");
    //$db->query("drop table if exists $name");
    $sql="CREATE TABLE IF NOT EXISTS $name (
      {$name}_id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      {$name}_value varchar(30) NULL
      )";echo $sql;
    $db->query($sql);
    $this->dimensions[]="{$name}_id INT(11) UNSIGNED";
  }
  public function createTables($_e){
    $this->dimensions=array();
    foreach($_e->childNodes as $_ch){
      if($_ch->nodeName=="Dimension"){
        $this->createDimensionTable($_ch);
      }
    }
    $columns=array();
    foreach($_e->childNodes as $_ch){
      if($_ch->nodeName=="Measure"){
        $columns[]=$_ch->getAttribute("name").' decimal(10,4)';
      }
    }
    $db=DB::connection();
    $name=$_e->getAttribute("name");
    //$db->query("drop table if exists $name");
    $sql="CREATE TABLE IF NOT EXISTS $name (
      {$name}_id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      ".implode(",",$columns).",
      time_id INT(11) UNSIGNED,
      ".implode(",",$this->dimensions)." 
      )";echo $sql;
    $db->query($sql);
  }
  //$_e=Cube
  public static function recreateTables($_e){
    $a=new Cube();
    return $a->createTables($_e);
  }
}
