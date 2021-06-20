<?php
  $aResult = array();
  
  if( !isset($_POST['functionname']) ) { $aResult['error'] = 'No function name!'; }

  if( !isset($_POST['arguments']) ) { $aResult['error'] = 'No function arguments!'; }

  if( !isset($aResult['error']) ) {
    switch($_POST['functionname']) {
      case 'log_data':
        if( !is_array($_POST['arguments']) || (count($_POST['arguments']) != 3) ) {
          $aResult['error'] = 'Error in arguments!';
        }
        else {

          $fn = "../logs/" . $_POST['arguments'][0] . '-q.json';
          $jsonString = file_get_contents($fn);
          $data = json_decode($jsonString, true);
          $progress = $_POST['arguments'][1];

          $a_sliders = array();
          $a_ranks = $_POST['arguments'][2];

          $data[$progress] = array("sliders" => $a_sliders, "ranks" => $a_ranks, "time" => time());

          $newJsonString = json_encode($data);
          file_put_contents($fn, $newJsonString);
        }
        break;
      default:
        $aResult['error'] = 'Not found function '.$_POST['functionname'].'!';
        break;
    }
  }

  echo json_encode($aResult);
?>