<?php
class VirtualCube {
  public static function getXmlOuter($_e){
    return '<VirtualCube name="'.$_e->getAttribute('name').'">
'.VirtualCube::getXml($_e).'
</VirtualCube>';
  }
  public static function getXml($_e){
    $_res="";
    foreach($_e->childNodes as $_ch):
    if($_ch->nodeName=="Cube"):
      $_cube=$_ch->getAttribute('name');
      foreach($_ch->childNodes as $_ch1):
      if($_ch1->nodeName=="Dimension"):
        $_dim=$_ch1->getAttribute('name');
        $_res.='  <VirtualCubeDimension cubeName="'.$_cube.'" name="'.$_dim.'"/>'."\n";
      endif;
      endforeach;
    endif;
    endforeach;
    foreach($_e->childNodes as $_ch):
    if($_ch->nodeName=="Cube"):
      $_cube=$_ch->getAttribute('name');
      foreach($_ch->childNodes as $_ch1):
      if($_ch1->nodeName=="Measure"):
        $_mea=$_ch1->getAttribute('name');
        $_res.='  <VirtualCubeMeasure cubeName="'.$_cube.'" name="[Measures].['.$_mea.']"/>'."\n";
      endif;
      endforeach;
    endif;
    endforeach;
    
    return $_res;
  }
}
