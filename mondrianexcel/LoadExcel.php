<?php
require_once("DB.php");
require_once("Cube.php");
require_once("Dimension.php");
require_once("Measure.php");
require_once("VirtualCube.php");

class LoadExcel {
  public static function updateSchemaXML($schemaPath, $dom, $virtualCube){
    $vs=$dom->getElementsByTagName('VirtualCube');
    $vc=false;var_dump($vs);
    foreach($vs as $v){
      if($v->getAttribute('name')=='Entreparentesys'){
        $vc=$v;break;
      }
    }
    if(!$vc){
      $vc=$dom->createElement('VirtualCube');
      $vc->setAttribute('name', 'Entreparentesys');
      $dom->getElementsByTagName('Schema')[0]->appendChild($vc);
    }
    foreach($virtualCube->childNodes as $_ch){
      if($_ch->nodeName=='Cube'){
        $cc=$dom->getElementsByTagName('Cube');
        $c=false;
        //echo 'buscando cubo:';
        foreach($cc as $v){
        //echo $v->getAttribute('name').',';
          if($v->getAttribute('name')==$_ch->getAttribute('name')){
            $c=$v;break;
          }
        }
        if(!$c){
          $f=$dom->createDocumentFragment();
          $f->appendXML(Cube::getXMLOuter($_ch));
          $dom->getElementsByTagName('Schema')[0]->insertBefore($f, $vc);
        }
        //Add to virtualcube
        $dimensions=$vc->getElementsByTagName('VirtualCubeDimension');
        $measures=$vc->getElementsByTagName('VirtualCubeMeasure');
        $firstMeasure=$measures[0];
        foreach($_ch->childNodes as $_ch1){
          $ok=true;
          foreach($dimensions as $d)if($d->getAttribute('name')==$_ch1->getAttribute('name')){
            $ok=false;break;
          }
          if($_ch1->nodeName=='Dimension' && $ok){
            $e=$dom->createElement('VirtualCubeDimension');
            $e->setAttribute('cubeName',$_ch->getAttribute('name'));
            $e->setAttribute('name',$_ch1->getAttribute('name'));
            $vc->insertBefore($e, $firstMeasure);
          }
        }
        foreach($_ch->childNodes as $_ch1){
          $ok=true;
          foreach($measures as $d)if($d->getAttribute('name')==$_ch1->getAttribute('name')){
            $ok=false;break;
          }
          if($_ch1->nodeName=='Measure' && $ok){
            $e=$dom->createElement('VirtualCubeMeasure');
            $e->setAttribute('cubeName',$_ch->getAttribute('name'));
            $e->setAttribute('name','[Measures].['.$_ch1->getAttribute('name').']');
            $vc->appendChild($e);
          }
        }
      }
    }
    $dom->save($schemaPath);
  }
  public static function loadVirtualCube($schemaPath){
    $dom=new DomDocument();
    $dom->loadXML(file_get_contents($schemaPath));
    return $dom;
  }
  public static function doTask($file){

    set_include_path(get_include_path() . PATH_SEPARATOR . 'phpexcel2/Classes');
    
    define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');
    date_default_timezone_set('Europe/London');
    // Include PHPExcel_IOFactory 
    require_once 'PHPExcel/IOFactory.php';
    
    echo date('H:i:s') , " Load from Excel2007 file" , EOL;
    $callStartTime = microtime(true);
    
    $objPHPExcel = PHPExcel_IOFactory::load($file);
    
    $callEndTime = microtime(true);
    $callTime = $callEndTime - $callStartTime;
    echo 'Call time to read Workbook was ' , sprintf('%.4f',$callTime) , " seconds" , EOL;
    
    $db=DB::connection();
    //$dom = new DomDocument();
    $schemaPath='/home/david/tomcat/webapps/mondrian/WEB-INF/queries/FoodMart.xml';
    $dom=LoadExcel::loadVirtualCube($schemaPath);
    $virtualCube=$dom->createElement('VirtualCube');
    $virtualCube->setAttribute('name', 'Entreparentesys');


    $sheet = $objPHPExcel->getSheet(0);
    //$r=1..n
    //$c=0..n
    $r=1;
    $dimensiones=array();
    $dimensiones_id=array();
    for($c=9; ; $c++){
      $cell=$sheet->getCellByColumnAndRow($c,$r)->__toString();
      if(!$cell) break;
      $cell=strtolower(preg_replace('/\W+/', '_', $cell));
      $dimensiones[]=$cell;
      $dimensiones_id[]=$cell.'_id';
    }
    for($r=2; ; $r++){
      $c=0;
      $cell=$sheet->getCellByColumnAndRow($c,$r)->__toString();
      if(!$cell) break;

      $cell=$sheet->getCellByColumnAndRow(1,$r)->__toString();
      $variable=strtolower(preg_replace('/\W+/', '_', $cell));

      $cell=$sheet->getCellByColumnAndRow(2,$r)->__toString();
      $unidad=strtolower(preg_replace('/\W+/', '_', $cell));

      $cell=$sheet->getCellByColumnAndRow(3,$r)->__toString();
      $frecuencia=strtolower(preg_replace('/\W+/', '_', $cell));

      $cantidad=$sheet->getCellByColumnAndRow(4,$r)->getValue();

      $fecha=$sheet->getCellByColumnAndRow(5,$r)->getValue();
      $fecha=PHPExcel_Shared_Date::ExcelToPHP($fecha);

      $dia=$sheet->getCellByColumnAndRow(6,$r)->__toString();
      $mes=$sheet->getCellByColumnAndRow(7,$r)->__toString();
      $anio=$sheet->getCellByColumnAndRow(8,$r)->__toString();

      //Verifica si existe la variable;
      if(!LoadExcel::existsCube($variable)){
        $cube=LoadExcel::createCube($dom, $variable);
        LoadExcel::createMeasure($cube, 'cantidad');
        foreach($dimensiones as $i=>$d){
          LoadExcel::createDimension($cube, $d);
        }
        Cube::recreateTables($cube);
        $virtualCube->appendChild($cube);
      }

      $insDims=array();
      foreach($dimensiones as $i=>$d){
        $dim=$sheet->getCellByColumnAndRow(9+$i,$r)->getValue();
        $dimId=query($db,"select {$d}_id as id from $d where {$d}_value=?",array($dim));
        $dimId=@$dimId[0]['id'];
        if(!$dimId){
//          var_dump("insert into $d({$d}_value) values (?)",array($dim));
          query($db, "insert into $d({$d}_value) values (?)",array($dim));
          $dimId=$db->lastInsertId();
        }
        $insDims[]=$dimId;
      }
      $insDims=implode(',', $insDims);
      $time_id=LoadExcel::getTimeId($db, $fecha);
      $sql="insert into $variable(cantidad,time_id,".implode(",",$dimensiones_id).")values($cantidad,$time_id,$insDims)";
      query($db, $sql);
      //echo $sql;

//      break;
    }
//    var_dump($dimensiones);

    //Update mondrian schema
    LoadExcel::updateSchemaXML($schemaPath, $dom, $virtualCube);

  }
  public static function existsCube($name){
    //return false;
    $tables=query(DB::connection(),"SHOW TABLES LIKE '$name'");
    return (count($tables)==1);
  }
  public static function createCube($dom, $name){
    $cube=$dom->createElement('Cube');
    $cube->setAttribute('name',$name);
    $dom->childNodes[0]->appendChild($cube);
    return $cube;
  }
  public static function createMeasure($cube, $name){
    $meas=$cube->ownerDocument->createElement('Measure');
    $meas->setAttribute('name',$name);
    $cube->appendChild($meas);
    return $meas;
  }
  public static function createDimension($cube, $name){
    $dime=$cube->ownerDocument->createElement('Dimension');
    $dime->setAttribute('name',$name);
    $dime->setAttribute('key',$name.'_id');
    $cube->appendChild($dime);
    return $dime;
  }
  public static function getIdOf($db, $table, $id, $value, $valueOf){
    $dimId=query($db,"select {$id} as id from $table where $value=?",array($valueOf));
    $dimId=@$dimId[0]['id'];
    if(!$dimId){
      var_dump("insert into $table({$value}) values (?)",array($valuesOf));
      query($db, "insert into $table({$value}) values (?)",array($valueOf));
      $dimId=$db->lastInsertId();
    }
  }
  public static function getTimeId($db, $valueOf){
    $table='time_by_day';$id='time_id'; $value='the_date';
    $dimId=query($db,"select {$id} as id from $table where $value=?",array(Date('Y-m-d',$valueOf)));
    $dimId=@$dimId[0]['id'];
    if(!$dimId){
/*      var_dump("insert into $table(
          {$value},
          the_day,
          the_month,
          the_year,
          day_of_month,
          week_of_year,
          month_of_year,
          quarter) values (?,?,?,?,?,?,?,?)",
        array(
          Date('Y-m-d',$valueOf),
          Date('l',$valueOf),
          Date('F',$valueOf),
          Date('Y',$valueOf),
          Date('j',$valueOf),
          Date('W',$valueOf),
          Date('n',$valueOf),
          'Q1'
        )
      );*/
      query($db, "insert into $table(
          {$value},
          the_day,
          the_month,
          the_year,
          day_of_month,
          week_of_year,
          month_of_year,
          quarter) values (?,?,?,?,?,?,?,?)",
        array(
          Date('Y-m-d',$valueOf),
          Date('L',$valueOf),
          Date('F',$valueOf),
          Date('Y',$valueOf),
          Date('j',$valueOf),
          Date('W',$valueOf),
          Date('n',$valueOf),
          'Q1'
        )
      );
      $dimId=$db->lastInsertId();
    }
    return $dimId;
  }
}

LoadExcel::doTask('/home/david/Downloads/PAQUETE DE PRODUCCION BRUTA DE GAS NATURAL.xls');

function query($db, $query, $params=null){
  $s=$db->prepare($query);
  if($params) $s->execute($params);
  else $s->execute();
  return $s->fetchAll();
}
