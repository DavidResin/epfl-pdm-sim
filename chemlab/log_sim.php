<?php
  $aResult = array();
  
  if( !isset($_POST['functionname']) ) { $aResult['error'] = 'No function name!'; }

  if( !isset($_POST['arguments']) ) { $aResult['error'] = 'No function arguments!'; }

  if( !isset($aResult['error']) ) {
    switch($_POST['functionname']) {
      case 'log_data':
        if( !is_array($_POST['arguments']) || (count($_POST['arguments']) < 3) ) {
          $aResult['error'] = 'Error in arguments!';
        }
        else {
          $t1 = $_POST['arguments'][0];
          $t2 = $_POST['arguments'][1];
          $fp = fopen("logs/{$t1}-{$t2}.log", 'a');
          fwrite($fp, $_POST['arguments'][2]);
          fclose($fp);
          $aResult['result'] = "";
        }
        break;
      default:
        $aResult['error'] = 'Not found function '.$_POST['functionname'].'!';
        break;
    }
  }

  echo json_encode($aResult);
?>