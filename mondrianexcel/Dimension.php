<?php
class Dimension {
  public static function getXmlOuter($_e){
  $res='<Dimension name="'.$_e->getAttribute("name").'" foreignKey="'.$_e->getAttribute("key").'">
    <Hierarchy hasAll="true" primaryKey="'.$_e->getAttribute("key").'">
      <Table name="'.$_e->getAttribute("name").'"/>
      <Level name="'.$_e->getAttribute("name").'_value" column="'.$_e->getAttribute("name").'_value" uniqueMembers="false"/>
    </Hierarchy>
  </Dimension>';
    return $res;
  }
}
