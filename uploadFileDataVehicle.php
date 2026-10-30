<?php

    if(isset($_FILES['file'])){
        
        $arr_file_types = ['text/csv', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    
    if (!(in_array($_FILES['file']['type'], $arr_file_types))) {
        echo "false";
        return;
    }
    
    if (!file_exists('\\\\192.168.247.35\\DataDcMC\\Safty\\upload\\')) {
        mkdir('\\\\192.168.247.35\\DataDcMC\\Safty\\upload\\', 0777);
    }
    
    $filename =  'ทะเบียนยานยนต์.xlsx'; 
    move_uploaded_file($_FILES['file']['tmp_name'], '\\\\192.168.247.35\\DataDcMC\\Safty\\upload\\'.$filename);
    
    echo '\\\\192.168.247.35\\DataDcMC\\Safty\\'.$filename;
    die;

    }

?>