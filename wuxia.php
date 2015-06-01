<?php

require_once 'Wuxia.php';

$wuxia = new Wuxia();

$result = -1;

$counter = 0;

$outer = 0;

while($result == -1){

    $time = date("H:i:s");

    $time_arr = explode(':', $time);

    $hour = (int)$time_arr[0];
    $minute = (int)$time_arr[1];
    $second = (int)$time_arr[2];

    if( ($minute === 8 && $second > 50 ) || ( $minute === 9 && $second <1 ) ){

        $info = $wuxia->keygen();

        $result = $info->r;

        if($result != -1 || $counter > 100){
            break;
        }

        $counter++;
        usleep(50);
        $counter++;
    }

    $outer++;

    if($outer>100){
        break;
    }

}

echo 'time=' . $time;

echo 'r=' . $result . '\r\n';

echo 'c=' . $counter;


exit;