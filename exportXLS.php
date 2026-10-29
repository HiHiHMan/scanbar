<?php
// header("Content-type: application/vnd.ms-excel");
// header("Content-Disposition: attachment; filename=file.xls");

$reportName = isset($_POST['reportName']) ? $_POST['reportName'] : null;
$urlAPI = 'http://192.168.247.35:4006';
function sumByKey($array, $key)
{
    return array_reduce($array, function ($carry, $item) use ($key) {
        if (is_object($item) && isset($item->$key)) {
            return $carry + $item->$key;
        } elseif (is_array($item) && isset($item[$key])) {
            return $carry + $item[$key];
        }
        return $carry;
    }, 0);
}

$api_url = $urlAPI . '/API/Workload/WaveTotal';
$countWaveInfo = curl_init();
curl_setopt($countWaveInfo, CURLOPT_URL, $api_url);
curl_setopt($countWaveInfo, CURLOPT_POST, true); 
curl_setopt($countWaveInfo, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($countWaveInfo);
if (curl_errno($countWaveInfo)) {
    echo 'Error:' . curl_error($countWaveInfo);
}
curl_close($countWaveInfo);
$dataCountWaveInfo = json_decode($response, true);


if ($reportName == 'workloadBefore') {
    $api_url = $urlAPI . '/API/Workload/SummaryBySec'; 
    $summaryBySection = curl_init(); 
    curl_setopt($summaryBySection, CURLOPT_URL, $api_url);
    curl_setopt($summaryBySection, CURLOPT_POST, true);
    curl_setopt($summaryBySection, CURLOPT_RETURNTRANSFER, true); 
    $response = curl_exec($summaryBySection); 
    if (curl_errno($summaryBySection)) {
        echo 'Error: ' . curl_error($summaryBySection);
    } 
    curl_close($summaryBySection); 
    $dataBySec = json_decode($response, true);
 

    $api_url = $urlAPI . '/API/Workload/BothInfo';
    $both = curl_init();
    curl_setopt($both, CURLOPT_URL, $api_url);
    curl_setopt($both, CURLOPT_POST, true);
    curl_setopt($both, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($both);
    if (curl_errno($both)) {
        echo 'Error:' . curl_error($both);
    }
    curl_close($both);
    $dataBoth = json_decode($response, true);

    $api_url = $urlAPI . '/API/Workload/SummaryByZone';
    $summaryByZone = curl_init();
    curl_setopt($summaryByZone, CURLOPT_URL, $api_url);
    curl_setopt($summaryByZone, CURLOPT_POST, true);
    curl_setopt($summaryByZone, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($summaryByZone);
    if (curl_errno($summaryByZone)) {
        echo 'Error:' . curl_error($summaryByZone);
    }
    curl_close($summaryByZone);
    $dataByZone = json_decode($response, true);

    $api_url = $urlAPI . '/API/Workload/PickTotal';
    $countPickpackBreakNFull = curl_init();
    curl_setopt($countPickpackBreakNFull, CURLOPT_URL, $api_url);
    curl_setopt($countPickpackBreakNFull, CURLOPT_POST, true);
    curl_setopt($countPickpackBreakNFull, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($countPickpackBreakNFull);
    if (curl_errno($countPickpackBreakNFull)) {
        echo 'Error:' . curl_error($countPickpackBreakNFull);
    }
    curl_close($countPickpackBreakNFull);
    $dataBCPickpack = json_decode($response, true);

    $api_url = $urlAPI . '/API/Workload/MaxCap';
    $MaxCap = curl_init();
    curl_setopt($MaxCap, CURLOPT_URL, $api_url);
    curl_setopt($MaxCap, CURLOPT_POST, true);
    curl_setopt($MaxCap, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($MaxCap);
    if (curl_errno($MaxCap)) {
        echo 'Error:' . curl_error($MaxCap);
    }
    curl_close($MaxCap);
    $dataMaxCap = json_decode($response, true);
    $BCMaxCap = 0;
    $FullMaxCap = 0;
    for ($i = 0; $i < sizeof($dataMaxCap); $i++) {
        if ($dataMaxCap[$i]['maxCapName'] == 'FullCase') {
            $FullMaxCap = $dataMaxCap[$i]['maxCap'];
        } else  if ($dataMaxCap[$i]['maxCapName'] == 'BreakCase') {
            $BCMaxCap = $dataMaxCap[$i]['maxCap'];
        }
    }

    if ($dataBoth === null) {
        echo "Failed to decode JSON.";
    } else {

        $bothOverMap = '<tr>';
        for ($i = 6; $i < sizeof($dataBoth); $i++) {
            if ($i == 6) {
                $bothOverMap .= '<td colspan="2">' . json_decode(json_encode($dataByZone[12]['PickZone'])) . '</td>
                                    <td>' . json_decode(json_encode($dataByZone[12]['Section'])) . '</td>
                                    <td style="text-align: center;">' . json_decode(json_encode($dataByZone[12]['Code'])) . '</td> 
                                    <td style="text-align: center;">' . number_format((int)json_decode(json_encode($dataByZone[12]['ProductTotal']))) . '</td> 
                                    <td style="text-align: center;background : #DAE1E6;">' . number_format((int)json_decode(json_encode($dataByZone[12]['OrderTotal']))) . '</td> 
                                    <td style="text-align: center;background : #ECEFF1;">0</td> 
                                    <td style="text-align: center;background : #ECEFF1;">Olpn</td>';
            }

            if ($i == 7) {
                $bothOverMap .= '<td colspan="2">' . json_decode(json_encode($dataByZone[13]['PickZone'])) . '</td>
                                    <td>' . json_decode(json_encode($dataByZone[13]['Section'])) . '</td>
                                    <td style="text-align: center;">' . json_decode(json_encode($dataByZone[13]['Code'])) . '</td> 
                                    <td style="text-align: center;">' . number_format((int)json_decode(json_encode($dataByZone[13]['ProductTotal']))) . '</td> 
                                    <td style="text-align: center;background : #DAE1E6;">' . number_format((int)json_decode(json_encode($dataByZone[13]['OrderTotal']))) . '</td> 
                                    <td style="text-align: center;background : #ECEFF1;">0</td> 
                                    <td style="text-align: center;background : #ECEFF1;">Olpn</td> ';
            }

            if ($i == 8) {
                $bothOverMap .= '<td colspan="2">' . json_decode(json_encode($dataByZone[14]['PickZone'])) . '</td>
                                    <td>' . json_decode(json_encode($dataByZone[14]['Section'])) . '</td>
                                    <td style="text-align: center;">' . json_decode(json_encode($dataByZone[14]['Code'])) . '</td> 
                                    <td style="text-align: center;">' . number_format((int)json_decode(json_encode($dataByZone[14]['ProductTotal']))) . '</td> 
                                    <td style="text-align: center;background : #DAE1E6;">' . number_format((int)json_decode(json_encode($dataByZone[14]['OrderTotal']))) . '</td> 
                                    <td style="text-align: center;background : #ECEFF1;">0</td> 
                                    <td style="text-align: center;background : #ECEFF1;">ลัง</td>';
            }

            if ($i == 9) {
                $bothOverMap .= '<td colspan="2">' . json_decode(json_encode($dataByZone[15]['PickZone'])) . '</td>
                                    <td>' . json_decode(json_encode($dataByZone[15]['Section'])) . '</td>
                                    <td style="text-align: center;">' . json_decode(json_encode($dataByZone[15]['Code'])) . '</td> 
                                    <td style="text-align: center;">' . number_format((int)json_decode(json_encode($dataByZone[15]['ProductTotal']))) . '</td> 
                                    <td style="text-align: center;background : #DAE1E6;">' . number_format((int)json_decode(json_encode($dataByZone[15]['OrderTotal']))) . '</td> 
                                    <td style="text-align: center;background : #ECEFF1;">0</td> 
                                    <td style="text-align: center;background : #ECEFF1;">-</td>';
            }


            if ($i == 10) {
                $bothOverMap .= '<td colspan="4" style="text-align: center;background : #B0BEC5;font-weight: 500;">Total</td>   
                                    <td style="text-align: center;background : #B0BEC5;font-weight: 500;">' . number_format((int)sumByKey($dataByZone, 'ProductTotal')) . '</td> 
                                    <td style="text-align: center;background : #B0BEC5;font-weight: 500;">' . number_format((int)sumByKey($dataByZone, 'OrderTotal')) . '</td> 
                                    <td style="text-align: center;background : #B0BEC5;">0</td> 
                                    <td style="text-align: center;background : #B0BEC5;"></td>';
            }

            if ($i == 11) {
                $bothOverMap .= '<td colspan="8" rowspan="' . (sizeof($dataBoth) - 9) . '"></td>  ';
            }

            $bothOverMap .= '   <td style="text-align: center;">' . json_decode(json_encode($dataBoth[$i]['Product'])) . '</td> 
                                    <td>' . json_decode(json_encode($dataBoth[$i]['Pdesc'])) . '</td> 
                                    <td>' . json_decode(json_encode($dataBoth[$i]['Module1'])) . '</td> 
                                    <td>' . json_decode(json_encode($dataBoth[0]['Module2'])) . '</td> 
                                    <td style="text-align: center;background: #DDF4B9;">' . number_format((int)json_decode(json_encode($dataBoth[$i]['iOrderBoth']))) . '</td> 
                                    <td style="text-align: center;background: #DDF4B9;">' . number_format((int)json_decode(json_encode($dataBoth[$i]['iBoth']))) . '</td>
                                </tr>';
        }


        echo  '<style>  
                        table {
                            width: fit-content;
                            border-collapse: collapse;
                            border:1px solid #ccc; 
                        }
                
                        th, td { 
                            font-weight: 500;
                            border:1px solid #ccc;
                            padding: 8px;
                            text-align: left;
                        }
                        
                        th {
                            text-align: center;
                        }
                    </style>
                    <table border="1">
                        <tr>
                            <th colspan="15" rowspan="2" style="text-align: center;background : #00AB94;color:#fff;font-weight: 800;font-size: 20px;">รายงาน WORKLOAD เช้า DCMC</th> 
                        </tr>
                        <tr></tr>
                        <tr>
                            <th colspan="15" style="text-align: left;background : #32CBB7;color:#fff;font-weight: 800;font-size: 2rem;">WH : 901</th> 
                        </tr>
                        <tr>
                            <th colspan="15" style="text-align: left;background : #32CBB7;color:#fff;font-weight: 800;font-size: 2rem;">Date : ' . $dataCountWaveInfo[0]['dateInfo'] . '</th> 
                        </tr>
                        <tr>
                            <th colspan="2" rowspan="2" style="text-align: center;width:200px;font-weight: 500;">กลุ่มสินค้า</th>
                            <th rowspan="2" style="text-align: center;background: #FF8B32;font-weight: 500;">ยอดสั่ง</th>
                            <th rowspan="2" style="text-align: center;font-weight: 500;">Zone Code</th>
                            <th colspan="2" rowspan="2" style="text-align: center;font-weight: 500;">ชิ้น/ร้าน</th>
                            <th colspan="2" rowspan="2" style="text-align: center;font-weight: 500;">%</th>
                            <th rowspan="' . (sizeof($dataBoth) + 25) . '" style="text-align: center;width:15px;font-weight: 500;"></th>
                            <th colspan="2" rowspan="2" style="text-align: center;font-weight: 500;background: #FF8B32;">Max Cap.</th>
                            <th colspan="4" rowspan="2" style="text-align: center;font-weight: 500;background: #FF8B32;">Over Qty</th> 
                        </tr>
                        <tr>
                        </tr>
                        <tr> 
                            <td colspan="2" style="background: #FD92FF;">' . json_decode(json_encode($dataBySec[0]['Section'])) . '</td>
                            <td style="text-align: right;background: #FFE2CC;">' . number_format((int)json_decode(json_encode($dataBySec[0]['SumOfOrder']))) . '</td>
                            <td style="text-align: center;background: #FD92FF;">' . json_decode(json_encode($dataBySec[0]['Code'])) . '</td>
                            <td style="text-align: center;background: #FD92FF;">' . number_format((int)json_decode(json_encode($dataBySec[0]['Usc_Store']))) . '</td>
                            <td style="text-align: center;background: #FD92FF;">Usc\Store</td>
                            <td colspan="2" style="text-align: center;background: #FD92FF;">' . ((json_decode(json_encode($dataBySec[0]['Usc_Store'])) * 100) / sumByKey($dataBySec, 'Usc_Store')) . '%</td>
                            <td colspan="2" style="text-align: center;background: #FFE2CC;">' . number_format($BCMaxCap) . '</td>
                            <td colspan="4" style="text-align: center;background: #FFE2CC;color: #FF5E5E;">' . (number_format((int)json_decode(json_encode($dataBySec[0]['SumOfOrder'])) - (int)$BCMaxCap)) . '</td>
                        </tr>
                        <tr>
                            <td colspan="2" style="background: #9DF4E9;" >' . json_decode(json_encode($dataBySec[1]['Section'])) . '</td>
                            <td style="text-align: right;background: #FFE2CC;">' . number_format((int)json_decode(json_encode($dataBySec[1]['SumOfOrder']))) . '</td>
                            <td style="text-align: center;background: #9DF4E9;">' . json_decode(json_encode($dataBySec[1]['Code'])) . '</td>
                            <td style="text-align: center;background: #9DF4E9;">' . number_format((int)json_decode(json_encode($dataBySec[1]['Usc_Store']))) . '</td>
                            <td style="text-align: center;background: #9DF4E9;">Usc\Store</td>
                            <td colspan="2" style="text-align: center;background: #9DF4E9;">' . ((json_decode(json_encode($dataBySec[1]['Usc_Store'])) * 100) / sumByKey($dataBySec, 'Usc_Store')) . '%</td> 
                            <td colspan="2" style="text-align: center;background: #FFE2CC;">' . number_format($FullMaxCap) . '</td>
                            <td colspan="4" style="text-align: center;background: #FFE2CC;color: #FF5E5E;">' . (number_format((int)(json_decode(json_encode($dataBySec[1]['SumOfOrder'])) + json_decode(json_encode($dataBySec[2]['SumOfOrder']))) - (int)$FullMaxCap)) . '</td>
                        </tr>
                        <tr>
                            <td colspan="2" style="background: #5BE1D0;">' . json_decode(json_encode($dataBySec[2]['Section'])) . '</td>
                            <td style="text-align: right;background: #FFE2CC;">' . number_format((int)json_decode(json_encode($dataBySec[2]['SumOfOrder']))) . '</td>
                            <td style="text-align: center;background: #5BE1D0;">' . json_decode(json_encode($dataBySec[2]['Code'])) . '</td>
                            <td style="text-align: center;background: #5BE1D0;">' . number_format((int)json_decode(json_encode($dataBySec[2]['Usc_Store']))) . '</td>
                            <td style="text-align: center;background: #5BE1D0;">Usc\Store</td>
                            <td colspan="2" style="text-align: center;background: #5BE1D0;">' . ((json_decode(json_encode($dataBySec[2]['Usc_Store'])) * 100) / sumByKey($dataBySec, 'Usc_Store')) . '%</td> 
                            <td colspan="6" rowspan="11"></td>
                        </tr>
                        <tr>
                            <td colspan="2" style="background: #FFEC91;">' . json_decode(json_encode($dataBySec[3]['Section'])) . '</td>
                            <td style="text-align: right;background: #FFE2CC;">' . number_format((int)json_decode(json_encode($dataBySec[3]['SumOfOrder']))) . '</td>
                            <td style="text-align: center;background: #FFEC91;">' . json_decode(json_encode($dataBySec[3]['Code'])) . '</td>
                            <td style="text-align: center;background: #FFEC91;">' . number_format((int)json_decode(json_encode($dataBySec[3]['Usc_Store']))) . '</td>
                            <td style="text-align: center;background: #FFEC91;">Usc\Store</td>
                            <td colspan="2" style="text-align: center;background: #FFEC91;">' . ((json_decode(json_encode($dataBySec[3]['Usc_Store'])) * 100) / sumByKey($dataBySec, 'Usc_Store')) . '%</td>  
                        </tr>
                        <tr>
                            <td colspan="2" style="background: #FF9A4C;">' . json_decode(json_encode($dataBySec[4]['Section'])) . '</td>
                            <td style="text-align: right;background: #FFE2CC;">' . number_format((int)json_decode(json_encode($dataBySec[4]['SumOfOrder']))) . '</td>
                            <td style="text-align: center;background: #FF9A4C;">' . json_decode(json_encode($dataBySec[4]['Code'])) . '</td>
                            <td style="text-align: center;background: #FF9A4C;">' . number_format((int)json_decode(json_encode($dataBySec[4]['Usc_Store']))) . '</td>
                            <td style="text-align: center;background: #FF9A4C;">Usc\Store</td>
                            <td colspan="2" style="text-align: center;background: #FF9A4C;">' . ((json_decode(json_encode($dataBySec[4]['Usc_Store'])) * 100) / sumByKey($dataBySec, 'Usc_Store')) . '%</td>  
                        </tr>
                        <tr>
                            <td colspan="2" style="background: #9DE96C;">' . json_decode(json_encode($dataBySec[5]['Section'])) . '</td>
                            <td style="text-align: right;background: #FFE2CC;">' . number_format(json_decode(json_encode($dataBySec[5]['SumOfOrder']))) . '</td>
                            <td style="text-align: center;background: #9DE96C;">' . json_decode(json_encode($dataBySec[5]['Code'])) . '</td>
                            <td style="text-align: center;background: #9DE96C;">' . number_format((int)json_decode(json_encode($dataBySec[5]['Usc_Store']))) . '</td>
                            <td style="text-align: center;background: #9DE96C;">Usc\Store</td>
                            <td colspan="2" style="text-align: center;background: #9DE96C;">' . ((json_decode(json_encode($dataBySec[5]['Usc_Store'])) * 100) / sumByKey($dataBySec, 'Usc_Store')) . '%</td> 
                            
                        </tr>
                        <tr>
                            <td colspan="2" style="background: #E2A4FF;">' . json_decode(json_encode($dataBySec[6]['Section'])) . '</td>
                            <td style="text-align: right;background: #FFE2CC;">' . number_format((int)json_decode(json_encode($dataBySec[6]['SumOfOrder']))) . '</td>
                            <td style="text-align: center;background: #E2A4FF;">' . json_decode(json_encode($dataBySec[6]['Code'])) . '</td>
                            <td style="text-align: center;background: #E2A4FF;">' . number_format((int)json_decode(json_encode($dataBySec[6]['Usc_Store']))) . '</td>
                            <td style="text-align: center;background: #E2A4FF;">Usc\Store</td>
                            <td colspan="2" style="text-align: center;background: #E2A4FF;">' . ((json_decode(json_encode($dataBySec[6]['Usc_Store'])) * 100) / sumByKey($dataBySec, 'Usc_Store')) . '%</td> 
                            
                        </tr>
                        <tr>
                            <td colspan="2">' . json_decode(json_encode($dataBySec[7]['Section'])) . '</td>
                            <td style="text-align: right;background: #FFE2CC;">' . number_format((int)json_decode(json_encode($dataBySec[7]['SumOfOrder']))) . '</td>
                            <td style="text-align: center;">' . json_decode(json_encode($dataBySec[7]['Code'])) . '</td>
                            <td style="text-align: center;">' . number_format((int)json_decode(json_encode($dataBySec[7]['Usc_Store']))) . '</td>
                            <td style="text-align: center;">Usc\Store</td>
                            <td colspan="2" style="text-align: center;">' . ((json_decode(json_encode($dataBySec[7]['Usc_Store'])) * 100) / sumByKey($dataBySec, 'Usc_Store')) . '%</td> 
                            
                        </tr> 
                        <tr>
                            <td colspan="2" style="background: #FF5E5E;font-weight: 500;">Grand Total</td>
                            <td style="text-align: right;background: #FF5E5E;font-weight: 500;">' . number_format((int)sumByKey($dataBySec, 'SumOfOrder')) . '</td>
                            <td style="text-align: center;background: #FF5E5E;font-weight: 500;">AVG.\Store</td>
                            <td style="text-align: center;background: #FF5E5E;font-weight: 500;">' . number_format((int)sumByKey($dataBySec, 'Usc_Store')) . '</td>
                            <td style="text-align: center;background: #FF5E5E;font-weight: 500;">Usc\Store</td>
                            <td colspan="2" style="text-align: center;background: #FF5E5E;font-weight: 500;">100%</td> 
                       </tr>  
                        <tr>
                            <td colspan="35"></td> 
                        </tr>
                        <tr>
                            <td colspan="2" style="font-weight: 500;">Number of Store Delivery</td>
                            <td style="text-align: center;vertical-align: middle;background: #FF5E5E;font-weight: 500;">' . number_format((int)json_decode(json_encode($dataCountWaveInfo[0]['totalCustomer']))) . '</td>
                            <td style="text-align: center;vertical-align: middle;font-weight: 500;">Store</td>
                            <td rowspan="2" style="text-align: center;vertical-align: middle;font-weight: 500;">จำนวน Wave</td>
                            <td rowspan="2" style="text-align: center;vertical-align: middle;background: #FF5E5E;font-weight: 500;">' . number_format((int)json_decode(json_encode($dataCountWaveInfo[0]['totalWave']))) . '</td>
                            <td rowspan="2" colspan="2" style="text-align: center;vertical-align: middle;font-weight: 500;">Wave</td> 
                        
                        </tr>
                        <tr>
                            <td colspan="2">Number of Route</td>
                            <td style="text-align: center;vertical-align: middle;background: #FF5E5E;font-weight: 500;">' . number_format((int)json_decode(json_encode($dataCountWaveInfo[0]['totalRoute']))) . '</td>
                            <td style="text-align: center;vertical-align: middle;font-weight: 500;">Route</td> 
                           
                        </tr>
                        <tr>
                            <td colspan="8"></td> 
                          
                        </tr>
                        <tr>
                            <th colspan="8" rowspan="2" style="text-align: center;font-weight: 500;background: #8C989D;">Distribution Orders Daily Report</th>
                            <th colspan="6" rowspan="2" style="text-align: center;background: #FF8B32;font-weight: 500;">Work Load Pickpack</th>
                          
                        </tr>
                        <tr></tr>
                        <tr>
                            <th colspan="2" style="text-align: center;background : #B0BEC5;font-weight: 500;">Pick Zone Desc.</th>
                            <th style="text-align: center;background : #B0BEC5;font-weight: 500;">Section</th>
                            <th style="text-align: center;background : #B0BEC5;font-weight: 500;">Code</th> 
                            <th style="text-align: center;background : #B0BEC5;font-weight: 500;">Items</th> 
                            <th style="text-align: center;background : #B0BEC5;font-weight: 500;">Order Qty</th> 
                            <th style="text-align: center;background : #B0BEC5;font-weight: 500;">OLPNs</th> 
                            <th style="text-align: center;background : #B0BEC5;font-weight: 500;">หน่วย</th>
                            <th colspan="2" style="text-align: center;background: #FD92FF;">Break Case</th>
                            <th colspan="4" style="text-align: center;background: #FD92FF;">' . number_format((int)json_decode(json_encode($dataBCPickpack[0]['QtyBC']))) . '</th> 
                            
                        </tr> 
                        <tr>
                            <td colspan="2">' . json_decode(json_encode($dataByZone[0]['PickZone'])) . '</td>
                            <td>' . json_decode(json_encode($dataByZone[0]['Section'])) . '</td>
                            <td style="text-align: center;">' . json_decode(json_encode($dataByZone[0]['Code'])) . '</td> 
                            <td style="text-align: center;">' . number_format((int)json_decode(json_encode($dataByZone[0]['ProductTotal']))) . '</td> 
                            <td style="text-align: center;background : #DAE1E6;">' . number_format((int)json_decode(json_encode($dataByZone[0]['OrderTotal']))) . '</td> 
                            <td style="text-align: center;background : #ECEFF1;">0</td> 
                            <td style="text-align: center;background : #ECEFF1;">ลัง</td> 
                            <td colspan="2" style="text-align: center;background: #9DF4E9;">Full Case</td>
                            <td colspan="4" style="text-align: center;background: #9DF4E9;">' . number_format((int)json_decode(json_encode($dataBCPickpack[0]['QtyFull']))) . '</td> 
                        </tr>
                        <tr>
                            <td colspan="2">' . json_decode(json_encode($dataByZone[1]['PickZone'])) . '</td>
                            <td>' . json_decode(json_encode($dataByZone[1]['Section'])) . '</td>
                            <td style="text-align: center;">' . json_decode(json_encode($dataByZone[1]['Code'])) . '</td> 
                            <td style="text-align: center;">' . number_format((int)json_decode(json_encode($dataByZone[1]['ProductTotal']))) . '</td> 
                            <td style="text-align: center;background : #DAE1E6;">' . number_format((int)json_decode(json_encode($dataByZone[1]['OrderTotal']))) . '</td> 
                            <td style="text-align: center;background : #ECEFF1;">0</td> 
                            <td style="text-align: center;background : #ECEFF1;">ลัง</td>  
                            <td colspan="2" style="text-align: center;background: #FF5E5E;font-weight: 500;">Grands Total</td>
                            <td colspan="4" style="text-align: center;background: #FF5E5E;font-weight: 500;">' . number_format((int)json_decode(json_encode($dataBCPickpack[0]['QtyBC'])) + (int)json_decode(json_encode($dataBCPickpack[0]['QtyFull']))) . '</td> 
                        
                        </tr>
                        <tr>
                            <td colspan="2">' . json_decode(json_encode($dataByZone[2]['PickZone'])) . '</td>
                            <td>' . json_decode(json_encode($dataByZone[2]['Section'])) . '</td>
                            <td style="text-align: center;">' . json_decode(json_encode($dataByZone[2]['Code'])) . '</td> 
                            <td style="text-align: center;">' . number_format((int)json_decode(json_encode($dataByZone[2]['ProductTotal']))) . '</td> 
                            <td style="text-align: center;background : #DAE1E6;">' . number_format((int)json_decode(json_encode($dataByZone[2]['OrderTotal']))) . '</td> 
                            <td style="text-align: center;background : #ECEFF1;">0</td> 
                            <td style="text-align: center;background : #ECEFF1;">ลัง</td>  
                            <td colspan="6"</td>

                        </tr>
                        <tr>
                            <td colspan="2">' . json_decode(json_encode($dataByZone[3]['PickZone'])) . '</td>
                            <td>' . json_decode(json_encode($dataByZone[3]['Section'])) . '</td>
                            <td style="text-align: center;">' . json_decode(json_encode($dataByZone[3]['Code'])) . '</td> 
                            <td style="text-align: center;">' . number_format((int)json_decode(json_encode($dataByZone[3]['ProductTotal']))) . '</td> 
                            <td style="text-align: center;background : #DAE1E6;">' . number_format((int)json_decode(json_encode($dataByZone[3]['OrderTotal']))) . '</td> 
                            <td style="text-align: center;background : #ECEFF1;">0</td> 
                            <td style="text-align: center;background : #ECEFF1;">Olpn</td>
                            <td colspan="6" rowspan="2" style="text-align: center;background: #66C4FF;font-weight: 500;">BOHT</td> 
                          
                        </tr>
                        <tr>
                            <td colspan="2">' . json_decode(json_encode($dataByZone[4]['PickZone'])) . '</td>
                            <td>' . json_decode(json_encode($dataByZone[4]['Section'])) . '</td>
                            <td style="text-align: center;">' . json_decode(json_encode($dataByZone[4]['Code'])) . '</td> 
                            <td style="text-align: center;">' . number_format((int)json_decode(json_encode($dataByZone[4]['ProductTotal']))) . '</td> 
                            <td style="text-align: center;background : #DAE1E6;">' . number_format((int)json_decode(json_encode($dataByZone[4]['OrderTotal']))) . '</td> 
                            <td style="text-align: center;background : #ECEFF1;">0</td> 
                            <td style="text-align: center;background : #ECEFF1;">Olpn</td>  
                            
                        </tr>
                        <tr>
                            <td colspan="2">' . json_decode(json_encode($dataByZone[5]['PickZone'])) . '</td>
                            <td>' . json_decode(json_encode($dataByZone[5]['Section'])) . '</td>
                            <td style="text-align: center;">' . json_decode(json_encode($dataByZone[5]['Code'])) . '</td> 
                            <td style="text-align: center;">' . number_format((int)json_decode(json_encode($dataByZone[5]['ProductTotal']))) . '</td> 
                            <td style="text-align: center;background : #DAE1E6;">' . number_format((int)json_decode(json_encode($dataByZone[5]['OrderTotal']))) . '</td> 
                            <td style="text-align: center;background : #ECEFF1;">0</td> 
                            <td style="text-align: center;background : #ECEFF1;">Olpn</td> 
                            <td style="text-align: center; width:90px;background: #B2E1FF;font-weight: 500;">Item</td> 
                            <td style="text-align: center; width:200px;background: #B2E1FF;font-weight: 500;">Description</td> 
                            <td style="text-align: center;background: #B2E1FF;font-weight: 500;">Module1</td> 
                            <td style="text-align: center;background: #B2E1FF;font-weight: 500;">Module2</td> 
                            <td style="text-align: center;background: #B2E1FF;font-weight: 500;">Total (ชิ้น)</td> 
                            <td style="text-align: center;background: #B2E1FF;font-weight: 500;">Total (หีบ)</td>
                            
                        </tr>
                        <tr>
                            <td colspan="2">' . json_decode(json_encode($dataByZone[6]['PickZone'])) . '</td>
                            <td>' . json_decode(json_encode($dataByZone[6]['Section'])) . '</td>
                            <td style="text-align: center;">' . json_decode(json_encode($dataByZone[6]['Code'])) . '</td> 
                            <td style="text-align: center;">' . number_format((int)json_decode(json_encode($dataByZone[6]['ProductTotal']))) . '</td> 
                            <td style="text-align: center;background : #DAE1E6;">' . number_format((int)json_decode(json_encode($dataByZone[6]['OrderTotal']))) . '</td> 
                            <td style="text-align: center;background : #ECEFF1;">0</td> 
                            <td style="text-align: center;background : #ECEFF1;">Olpn</td>   
                            <td style="text-align: center;">' . json_decode(json_encode($dataBoth[0]['Product'])) . '</td> 
                            <td>' . json_decode(json_encode($dataBoth[0]['Pdesc'])) . '</td> 
                            <td>' . json_decode(json_encode($dataBoth[0]['Module1'])) . '</td> 
                            <td>' . json_decode(json_encode($dataBoth[0]['Module2'])) . '</td> 
                            <td style="text-align: center;background: #DDF4B9;">' . number_format((int)json_decode(json_encode($dataBoth[0]['iOrderBoth']))) . '</td> 
                            <td style="text-align: center;background: #DDF4B9;">' . number_format((int)json_decode(json_encode($dataBoth[0]['iBoth']))) . '</td>  
                        </tr>
                        <tr>
                            <td colspan="2">' . json_decode(json_encode($dataByZone[7]['PickZone'])) . '</td>
                            <td>' . json_decode(json_encode($dataByZone[7]['Section'])) . '</td>
                            <td style="text-align: center;">' . json_decode(json_encode($dataByZone[7]['Code'])) . '</td> 
                            <td style="text-align: center;">' . number_format((int)json_decode(json_encode($dataByZone[7]['ProductTotal']))) . '</td> 
                            <td style="text-align: center;background : #DAE1E6;">' . number_format((int)json_decode(json_encode($dataByZone[7]['OrderTotal']))) . '</td> 
                            <td style="text-align: center;background : #ECEFF1;">0</td> 
                            <td style="text-align: center;background : #ECEFF1;">Olpn</td>  
                            <td style="text-align: center;">' . json_decode(json_encode($dataBoth[1]['Product'])) . '</td> 
                            <td>' . json_decode(json_encode($dataBoth[1]['Pdesc'])) . '</td> 
                            <td>' . json_decode(json_encode($dataBoth[1]['Module1'])) . '</td> 
                            <td>' . json_decode(json_encode($dataBoth[0]['Module2'])) . '</td> 
                            <td style="text-align: center;background: #DDF4B9;">' . number_format((int)json_decode(json_encode($dataBoth[1]['iOrderBoth']))) . '</td> 
                            <td style="text-align: center;background: #DDF4B9;">' . number_format((int)json_decode(json_encode($dataBoth[1]['iBoth']))) . '</td>  
                        </tr>
                        <tr>
                            <td colspan="2">' . json_decode(json_encode($dataByZone[8]['PickZone'])) . '</td>
                            <td>' . json_decode(json_encode($dataByZone[8]['Section'])) . '</td>
                            <td style="text-align: center;">' . json_decode(json_encode($dataByZone[0]['Code'])) . '</td> 
                            <td style="text-align: center;">' . number_format((int)json_decode(json_encode($dataByZone[8]['ProductTotal']))) . '</td> 
                            <td style="text-align: center;background : #DAE1E6;">' . number_format((int)json_decode(json_encode($dataByZone[8]['OrderTotal']))) . '</td> 
                            <td style="text-align: center;background : #ECEFF1;">0</td> 
                            <td style="text-align: center;background : #ECEFF1;">Olpn</td>
                            <td style="text-align: center;">' . json_decode(json_encode($dataBoth[2]['Product'])) . '</td> 
                            <td>' . json_decode(json_encode($dataBoth[2]['Pdesc'])) . '</td> 
                            <td>' . json_decode(json_encode($dataBoth[2]['Module1'])) . '</td> 
                            <td>' . json_decode(json_encode($dataBoth[0]['Module2'])) . '</td> 
                            <td style="text-align: center;background: #DDF4B9;">' . number_format((int)json_decode(json_encode($dataBoth[2]['iOrderBoth']))) . '</td> 
                            <td style="text-align: center;background: #DDF4B9;">' . number_format((int)json_decode(json_encode($dataBoth[2]['iBoth']))) . '</td>  
                        </tr>
                        <tr>
                            <td colspan="2">' . json_decode(json_encode($dataByZone[9]['PickZone'])) . '</td>
                            <td>' . json_decode(json_encode($dataByZone[9]['Section'])) . '</td>
                            <td style="text-align: center;">' . json_decode(json_encode($dataByZone[9]['Code'])) . '</td> 
                            <td style="text-align: center;">' . number_format((int)json_decode(json_encode($dataByZone[9]['ProductTotal']))) . '</td> 
                            <td style="text-align: center;background : #DAE1E6;">' . number_format((int)json_decode(json_encode($dataByZone[9]['OrderTotal']))) . '</td> 
                            <td style="text-align: center;background : #ECEFF1;">0</td> 
                            <td style="text-align: center;background : #ECEFF1;">ลัง</td> 
                            <td style="text-align: center;">' . json_decode(json_encode($dataBoth[3]['Product'])) . '</td> 
                            <td>' . json_decode(json_encode($dataBoth[3]['Pdesc'])) . '</td> 
                            <td>' . json_decode(json_encode($dataBoth[3]['Module1'])) . '</td> 
                            <td>' . json_decode(json_encode($dataBoth[0]['Module2'])) . '</td> 
                            <td style="text-align: center;background: #DDF4B9;">' . number_format((int)json_decode(json_encode($dataBoth[3]['iOrderBoth']))) . '</td> 
                            <td style="text-align: center;background: #DDF4B9;">' . number_format((int)json_decode(json_encode($dataBoth[3]['iBoth']))) . '</td>  
                        </tr>
                        <tr>
                            <td colspan="2">' . json_decode(json_encode($dataByZone[10]['PickZone'])) . '</td>
                            <td>' . json_decode(json_encode($dataByZone[10]['Section'])) . '</td>
                            <td style="text-align: center;">' . json_decode(json_encode($dataByZone[10]['Code'])) . '</td> 
                            <td style="text-align: center;">' . number_format((int)json_decode(json_encode($dataByZone[10]['ProductTotal']))) . '</td> 
                            <td style="text-align: center;background : #DAE1E6;">' . number_format((int)json_decode(json_encode($dataByZone[10]['OrderTotal']))) . '</td> 
                            <td style="text-align: center;background : #ECEFF1;">0</td> 
                            <td style="text-align: center;background : #ECEFF1;">ลัง</td>
                            <td style="text-align: center;">' . json_decode(json_encode($dataBoth[4]['Product'])) . '</td> 
                            <td>' . json_decode(json_encode($dataBoth[4]['Pdesc'])) . '</td> 
                            <td>' . json_decode(json_encode($dataBoth[4]['Module1'])) . '</td> 
                            <td>' . json_decode(json_encode($dataBoth[0]['Module2'])) . '</td> 
                            <td style="text-align: center;background: #DDF4B9;">' . number_format((int)json_decode(json_encode($dataBoth[4]['iOrderBoth']))) . '</td> 
                            <td style="text-align: center;background: #DDF4B9;">' . number_format((int)json_decode(json_encode($dataBoth[4]['iBoth']))) . '</td>
                        </tr>
                        <tr>
                            <td colspan="2">' . json_decode(json_encode($dataByZone[11]['PickZone'])) . '</td>
                            <td>' . json_decode(json_encode($dataByZone[11]['Section'])) . '</td>
                            <td style="text-align: center;">' . json_decode(json_encode($dataByZone[11]['Code'])) . '</td> 
                            <td style="text-align: center;">' . number_format((int)json_decode(json_encode($dataByZone[11]['ProductTotal']))) . '</td> 
                            <td style="text-align: center;background : #DAE1E6;">' . number_format((int)json_decode(json_encode($dataByZone[11]['OrderTotal']))) . '</td> 
                            <td style="text-align: center;background : #ECEFF1;">0</td> 
                            <td style="text-align: center;background : #ECEFF1;">Olpn</td>
                            <td style="text-align: center;">' . json_decode(json_encode($dataBoth[5]['Product'])) . '</td> 
                            <td>' . json_decode(json_encode($dataBoth[5]['Pdesc'])) . '</td> 
                            <td>' . json_decode(json_encode($dataBoth[5]['Module1'])) . '</td> 
                            <td>' . json_decode(json_encode($dataBoth[0]['Module2'])) . '</td> 
                            <td style="text-align: center;background: #DDF4B9;">' . number_format((int)json_decode(json_encode($dataBoth[5]['iOrderBoth']))) . '</td> 
                            <td style="text-align: center;background: #DDF4B9;">' . number_format((int)json_decode(json_encode($dataBoth[5]['iBoth']))) . '</td>
                        </tr> '
            .
            $bothOverMap
            .
            '<tr> 
                            
                            <td colspan="4" style="background: #66C4FF;font-weight: 500;" >Grand Total</td>   
                            <td style="text-align: center;background: #92E75C;font-weight: 500;">' . number_format((int)sumByKey($dataBoth, 'iOrderBoth')) . '</td>  
                            <td style="text-align: center;background: #92E75C;font-weight: 500;">' . number_format((int)sumByKey($dataBoth, 'iBoth')) . '</td> 
                        </tr> 
                    </table>';
    }
} else if ($reportName == 'workloadDelivery') {
    $api_url = $urlAPI . '/API/Workload/WLDeliBySec';
    $DeliBySec = curl_init();
    curl_setopt($DeliBySec, CURLOPT_URL, $api_url);
    curl_setopt($DeliBySec, CURLOPT_POST, true);
    curl_setopt($DeliBySec, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($DeliBySec);
    if (curl_errno($DeliBySec)) {
        echo 'Error:' . curl_error($DeliBySec);
    }
    curl_close($DeliBySec);
    $dataDeliBySec = json_decode($response, true);

    $api_url = $urlAPI . '/API/Workload/WLDeliByType';
    $DeliByType = curl_init();
    curl_setopt($DeliByType, CURLOPT_URL, $api_url);
    curl_setopt($DeliByType, CURLOPT_POST, true);
    curl_setopt($DeliByType, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($DeliByType);
    if (curl_errno($DeliByType)) {
        echo 'Error:' . curl_error($DeliByType);
    }
    curl_close($DeliByType);
    $dataDeliByType = json_decode($response, true);

    $sumUscStoreBySec = 0;
    foreach ($dataDeliBySec as $row) {
        if (isset($row['UscStore'])) {
            $sumUscStoreBySec += (float)$row['UscStore'];
        }
    }

    $sumItemByType = 0;
    $sumOrderByType = 0;
    foreach ($dataDeliByType as $row) {
        if (isset($row['Items'])) {
            $sumItemByType += (float)$row['Items'];
        }

        if (isset($row['OderQty'])) {
            $sumOrderByType += (float)$row['OderQty'];
        }
    }


    if ($dataDeliBySec === null) {
        echo "Failed to decode JSON.";
    } else {

        echo  '<style>  
                        table {
                            width: fit-content;
                            border-collapse: collapse;
                            border:1px solid #ccc; 
                        }
                
                        th, td { 
                            font-weight: 500;
                            border:1px solid #ccc;
                            padding: 8px;
                            text-align: left;
                        }
                        
                        th {
                            text-align: center;
                        }
                    </style>

                    <table  border="1">
                        <tr>
                            <th colspan="7" style="text-align: center;width:200px;font-weight: 700;font-size: 50px;"><span style="color: rgb(244, 140, 3);">7</span> <span style="color: green;"> - ELEVEN.</span> <span style="color: gray;">Delivery</span></th> 
                        </tr>
                        <tr>
                            <th colspan="7" style="text-align: center;width:200px;font-weight: 500;font-size: 20px;">Workload DCMC Delivery ณ วันที่ ' . json_decode(json_encode($dataDeliBySec[0]['idate'])) . ' </th> 
                        </tr>
                        <tr>
                            <th style="text-align: center;width:100px;font-weight: 600">กลุ่มสินค้า</th>
                            <th style="text-align: center;width:100px;font-weight: 600">ยอดสั่ง</th>
                            <th style="text-align: center;width:150px;font-weight: 600">กลุ่มสินค้า</th>
                            <th colspan="2" style="text-align: center;width:100px;font-weight: 600">ชิ้น/ร้าน</th>
                            <th style="text-align: center;width:100px;font-weight: 600">%</th>
                            <th rowspan="10" style="text-align: center;width:500px;font-weight: 600"></th>
                        </tr>
                        <tr>
                            <td style="color: #b539e2;font-weight: 600">Total Break Case</td> 
                            <td style="text-align: center;color: ;">' . (isset($dataDeliBySec[0]['TotalOrderQty_Deli']) ? json_decode(json_encode($dataDeliBySec[0]['TotalOrderQty_Deli'])) : '0') . '</td>
                            <td style="text-align: center;">Break Case</td>
                            <td style="text-align: center;color: #d30505;">' . (isset($dataDeliBySec[0]['UscStore']) ? round(json_decode(json_encode($dataDeliBySec[0]['UscStore']))) : '0') . '</td>
                            <td style="text-align: center;">Usc\Store</td>
                            <td style="text-align: center;">' . (isset($dataDeliBySec[0]['Per']) ? round(json_decode(json_encode($dataDeliBySec[0]['Per']))) : '0') . '%</td> 
                        </tr>
                        <tr> 
                            <td style="color: #5dcaee ;font-weight: 600">Total FullCase</td> 
                            <td style="text-align: center;">' . (isset($dataDeliBySec[1]['TotalOrderQty_Deli']) ? json_decode(json_encode($dataDeliBySec[1]['TotalOrderQty_Deli'])) : '0') . '</td>
                            <td style="text-align: center;">Full Case</td>
                            <td style="text-align: center;color: #d30505;">' . (isset($dataDeliBySec[1]['UscStore']) ? round(json_decode(json_encode($dataDeliBySec[1]['UscStore']))) : '0') . '</td>
                            <td style="text-align: center;">Usc\Store</td>
                            <td style="text-align: center;">' . (isset($dataDeliBySec[1]['Per']) ? round(json_decode(json_encode($dataDeliBySec[1]['Per']))) : '0') . '%</td>
                        </tr>
                        <tr> 
                            <td style="color: #4866ff;font-weight: 600">Total BulkPull</td> 
                            <td style="text-align: center;">' . (isset($dataDeliBySec[2]['TotalOrderQty_Deli']) ? json_decode(json_encode($dataDeliBySec[2]['TotalOrderQty_Deli'])) : '0') . '</td>
                            <td style="text-align: center;">Bulk Pull</td>
                            <td style="text-align: center;color: #d30505;">' . (isset($dataDeliBySec[2]['UscStore']) ? round(json_decode(json_encode($dataDeliBySec[2]['UscStore']))) : '0') . '</td>
                            <td style="text-align: center;">Usc\Store</td>
                            <td style="text-align: center;">' . (isset($dataDeliBySec[2]['Per']) ? round(json_decode(json_encode($dataDeliBySec[2]['Per']))) : '0') . '%</td>
                        </tr>
                        <tr> 
                            <td style="color: #08bc56;font-weight: 600">Total Liqour</td> 
                            <td style="text-align: center;">' . (isset($dataDeliBySec[3]['TotalOrderQty_Deli']) ? json_decode(json_encode($dataDeliBySec[3]['TotalOrderQty_Deli'])) : '0') . '</td>
                            <td style="text-align: center;">Liqour</td>
                            <td style="text-align: center;color: #d30505;">' . (isset($dataDeliBySec[3]['UscStore']) ? round(json_decode(json_encode($dataDeliBySec[3]['UscStore']))) : '0') . '</td>
                            <td style="text-align: center;">Usc\Store</td>
                            <td style="text-align: center;">' . (isset($dataDeliBySec[3]['Per']) ? round(json_decode(json_encode($dataDeliBySec[3]['Per']))) : '0') . '%</td>
                        </tr>
                        <tr> 
                            <td style="color: #ffc824;font-weight: 600">Total Security</td> 
                            <td style="text-align: center;">' . (isset($dataDeliBySec[4]['TotalOrderQty_Deli']) ? json_decode(json_encode($dataDeliBySec[4]['TotalOrderQty_Deli'])) : '0') . '</td>
                            <td style="text-align: center;">Security</td>
                            <td style="text-align: center;color: #d30505;">' . (isset($dataDeliBySec[4]['UscStore']) ? round(json_decode(json_encode($dataDeliBySec[4]['UscStore']))) : '0') . '</td>
                            <td style="text-align: center;">Usc\Store</td>
                            <td style="text-align: center;">' . (isset($dataDeliBySec[4]['Per']) ? round(json_decode(json_encode($dataDeliBySec[4]['Per']))) : '0') . '%</td>  
                        </tr>
                        <tr> 
                            <td style="color: #c74cff;font-weight: 600">Total Strong Room</td> 
                            <td style="text-align: center;">' . (isset($dataDeliBySec[5]['TotalOrderQty_Deli']) ? json_decode(json_encode($dataDeliBySec[5]['TotalOrderQty_Deli'])) : '0') . '</td>
                            <td style="text-align: center;">Strong Room</td>
                            <td style="text-align: center;color: #d30505;">' . (isset($dataDeliBySec[5]['UscStore']) ? round(json_decode(json_encode($dataDeliBySec[5]['UscStore']))) : '0') . '</td>
                            <td style="text-align: center;">Usc\Store</td>
                            <td style="text-align: center;">' . (isset($dataDeliBySec[5]['Per']) ? round(json_decode(json_encode($dataDeliBySec[5]['Per']))) : '0') . '%</td> 
                        </tr>
                        <tr> 
                            <td style="color: #5ee236;font-weight: 600">Total Entertrain</td> 
                            <td style="text-align: center;">' . (isset($dataDeliBySec[6]['TotalOrderQty_Deli']) ? json_decode(json_encode($dataDeliBySec[6]['TotalOrderQty_Deli'])) : '0') . '</td>
                            <td style="text-align: center;">Entertrain</td>
                            <td style="text-align: center;color: #d30505;">' . (isset($dataDeliBySec[6]['UscStore']) ? round(json_decode(json_encode($dataDeliBySec[6]['UscStore']))) : '0') . '</td>
                            <td style="text-align: center;">Usc\Store</td>
                            <td style="text-align: center;">' . (isset($dataDeliBySec[6]['Per']) ? round(json_decode(json_encode($dataDeliBySec[6]['Per']))) : '0') . '%</td>
                        </tr>
                        <tr>  
                            <td style="color: #714F26;font-weight: 600">Total No : Location</td> 
                            <td style="text-align: center;">' . (isset($dataDeliBySec[7]['TotalOrderQty_Deli']) ? json_decode(json_encode($dataDeliBySec[7]['TotalOrderQty_Deli'])) : '0') . '</td>
                            <td style="text-align: center;">No : Location</td>
                            <td style="text-align: center;color: #d30505;">' . (isset($dataDeliBySec[7]['UscStore']) ? round(json_decode(json_encode($dataDeliBySec[7]['UscStore']))) : '0') . '</td>
                            <td style="text-align: center;">Usc\Store</td>
                            <td style="text-align: center;">' . (isset($dataDeliBySec[7]['Per']) ? round(json_decode(json_encode($dataDeliBySec[7]['Per']))) : '0') . '%</td>
                        </tr>
                        <tr>  
                            <td style="font-weight: 600;color: #d30505;">Grand Total</td>
                            <td style="text-align: center;">' . (isset($dataDeliBySec[0]['GrandsTotal']) ? json_decode(json_encode($dataDeliBySec[0]['GrandsTotal'])) : '0') . '</td>
                            <td style="text-align: center;font-weight: 600;color: #d30505;">AVG.\Store</td>
                            <td style="text-align: center;color: #d30505;">' . round(json_decode(json_encode($sumUscStoreBySec))) . '</td>
                            <td style="text-align: center;">Usc\Store</td>
                            <td style="text-align: center;">100%</td>
                        </tr> 
                        <tr>  
                            <td colspan="7"></td> 
                        </tr> 
                        <tr>  
                            <td style="font-weight: 600;">Number of Store Delivery</td>
                            <td style="text-align: center;color: #d30505;">' . json_decode(json_encode($dataDeliBySec[0]['TotalStore'])) . '</td>
                            <td style="text-align: center;font-weight: 600;">Store</td> 
                        </tr> 
                        <tr>  
                            <td style="font-weight: 600;">Number of Route</td>
                            <td style="text-align: center;color: #d30505;">' . json_decode(json_encode($dataDeliBySec[0]['TotalRoute'])) . '</td>
                            <td style="text-align: center;font-weight: 600;">Route</td> 
                        </tr> 
                        <tr>  
                            <td colspan="7"></td> 
                        </tr> 
                        <tr>  
                            <td colspan="7"></td> 
                        </tr> 

                        <tr >
                            <th style="text-align: center;width:200px;background-color: #6fc1ff;font-weight: 600">Pick Zone Desc.</th>
                            <th style="text-align: center;width:200px;background-color: #6fc1ff;font-weight: 600">Carton Type</th>
                            <th style="text-align: center;width:200px;background-color: #6fc1ff;font-weight: 600">Items</th>
                            <th style="text-align: center;width:200px;background-color: #6fc1ff;font-weight: 600">Oder Qty</th> 
                        </tr>
                        <tr>
                            <td>' . json_decode(json_encode($dataDeliByType[0]['PickZone'])) . '</td> 
                            <td style="text-align: center;">' . json_decode(json_encode($dataDeliByType[0]['CartonType'])) . '</td>
                            <td style="text-align: center;">' . json_decode(json_encode($dataDeliByType[0]['Items'])) . '</td>
                            <td style="text-align: center;">' . json_decode(json_encode($dataDeliByType[0]['OderQty'])) . '</td> 
                        </tr>
                        <tr>
                            <td>' . json_decode(json_encode($dataDeliByType[1]['PickZone'])) . '</td> 
                            <td style="text-align: center;">' . json_decode(json_encode($dataDeliByType[1]['CartonType'])) . '</td>
                            <td style="text-align: center;">' . json_decode(json_encode($dataDeliByType[1]['Items'])) . '</td>
                            <td style="text-align: center;">' . json_decode(json_encode($dataDeliByType[1]['OderQty'])) . '</td>  
                        </tr>
                        <tr>
                            <td>' . json_decode(json_encode($dataDeliByType[2]['PickZone'])) . '</td> 
                            <td style="text-align: center;">' . json_decode(json_encode($dataDeliByType[2]['CartonType'])) . '</td>
                            <td style="text-align: center;">' . json_decode(json_encode($dataDeliByType[2]['Items'])) . '</td>
                            <td style="text-align: center;">' . json_decode(json_encode($dataDeliByType[2]['OderQty'])) . '</td> 
                        </tr>
                        <tr>
                            <td>' . json_decode(json_encode($dataDeliByType[3]['PickZone'])) . '</td> 
                            <td style="text-align: center;">' . json_decode(json_encode($dataDeliByType[3]['CartonType'])) . '</td>
                            <td style="text-align: center;">' . json_decode(json_encode($dataDeliByType[3]['Items'])) . '</td>
                            <td style="text-align: center;">' . json_decode(json_encode($dataDeliByType[3]['OderQty'])) . '</td> 
                        </tr>
                        <tr>
                            <td colspan="2"  style="text-align: center;font-weight: 600">Total</td>  
                            <td style="text-align: center;">' . round(json_decode(json_encode($sumItemByType))) . '</td>
                            <td style="text-align: center;">' . round(json_decode(json_encode($sumOrderByType))) . '</td> 
                        </tr>
                    </table>
                ';
    }
} else if ($reportName == 'byWaveBefore') {

    $api_url = $urlAPI . '/API/Workload/ReportByWave';
    $rp18bywave = curl_init();
    curl_setopt($rp18bywave, CURLOPT_URL, $api_url);
    curl_setopt($rp18bywave, CURLOPT_POST, true);
    curl_setopt($rp18bywave, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($rp18bywave);
    if (curl_errno($rp18bywave)) {
        echo 'Error:' . curl_error($rp18bywave);
    }
    curl_close($rp18bywave);
    $dataBywave = json_decode($response, true);


    if ($dataBywave === null) {
        echo "Failed to decode JSON.";
    } else {


        $record = '';
        for ($i = 0; $i < sizeof($dataBywave); $i++) {
            $txt = '';
            if ($i == 0) {
                $txt = 'FMCG';
            }
            $record .= '<tr> 
                                <td style="text-align: center;background : #E0E4E6;">' . (($dataBywave[$i]['Wave_no']) . ' ' . $txt) . '</td>  
                                <td style="text-align: right;background : #FEC8FF;">' . number_format((int)json_decode(json_encode($dataBywave[$i]['Break Case'] ?? '0'))) . '</td> 
                                <td style="text-align: right;background : #FFCCA5;">' . number_format((int)json_decode(json_encode($dataBywave[$i]['Overflow'] ?? '0'))) . '</td> 
                                <td style="text-align: right;background : #D7FAF6;">' . number_format((int)json_decode(json_encode($dataBywave[$i]['FullCase'] ?? '0'))) . '</td>  
                                <td style="text-align: right;background : #CDF6F0;">' . number_format((int)json_decode(json_encode($dataBywave[$i]['Bulk Pull (Fast)'] ?? '0'))) . '</td>  
                                <td style="text-align: right;background : #C5E1DE;">' . number_format((int)json_decode(json_encode($dataBywave[$i]['FullCase + BUL'] ?? '0'))) . '</td>   
                                <td style="text-align: right;background : #FFF1B1;">' . number_format((int)json_decode(json_encode($dataBywave[$i]['Security'] ?? '0'))) . '</td>  
                                <td style="text-align: right;background : #FFE87F;">' . number_format((int)json_decode(json_encode($dataBywave[$i]['Security เหล้า Break']))) . '</td>  
                                <td style="text-align: right;background : #F3DAFF;">' . number_format((int)json_decode(json_encode($dataBywave[$i]['Strong Room'] ?? '0'))) . '</td>  
                                <td style="text-align: right;background : #CEF4B5;">' . number_format((int)json_decode(json_encode($dataBywave[$i]['POP'] ?? '0'))) . '</td>  
                                <td style="text-align: right;">' . number_format((int)json_decode(json_encode($dataBywave[$i]['No : Location'] ?? '0'))) . '</td> 
                            </tr>';
        }
        if (isset($dataBywave[$i]['No : Location'])) {
            $dataBywave[$i]['No : Location'];
        } else {
            echo '0';
        }
        echo  '<style>  
                        table {
                            width: fit-content;
                            border-collapse: collapse;
                            border: 1px solid #ccc; 
                        }
                
                        th, td { 
                            font-weight: 500;
                            border: 1px solid #ccc;
                            padding: 8px;
                            text-align: left;
                        }
                        
                        th {
                            text-align: center;
                        }
                    </style>
                    <table border="1">
                        <tr>
                            <th colspan="11" rowspan="2" style="text-align: center;background : #00AB94;color:#fff;font-weight: 800;font-size: 20px;">ยอดงาน ก่อน WAVE</th> 
                        </tr>
                        <tr></tr>
                        <tr>
                            <th colspan="11" style="text-align: left;background : #32CBB7;color:#fff;font-weight: 800;font-size: 2rem;">WH : 901</th> 
                        </tr>
                        <tr>
                            <th colspan="11" style="text-align: left;background : #32CBB7;color:#fff;font-weight: 800;font-size: 2rem;">Date : ' . $dataCountWaveInfo[0]['dateInfo'] . '</th> 
                        </tr>
                        <tr>
                            <th style="text-align: center;width:95px;background : #B2BBC1;">Wave No</th> 
                            <th style="text-align: center;width:95px;background : #FB4EFF;">Break Case</th>
                            <th style="text-align: center;width:95px;background : #FF7B15;">Overflow</th> 
                            <th style="text-align: center;width:95px;background : #5FFFEB;">FullCase</th>
                            <th style="text-align: center;width:95px;background : #5BE1D0;">Bulk Pull (Fast)</th>
                            <th style="text-align: center;width:95px;background : #3F9D91;">FullCase + BUL</th> 
                            <th style="text-align: center;width:95px;background : #FFDD3D;">Security</th>
                            <th style="text-align: center;width:95px;background : #FFD200;">' . json_decode(json_encode('Security เหล้า Break')) . '</th>
                            <th style="text-align: center;width:95px;background : #E2A4FF;">Strong Room</th>
                            <th style="text-align: center;width:95px;background : #9DE96C;">POP</th>
                            <th style="text-align: center;width:95px;">No : Location</th> 
                        </tr>' . $record . '
                        <tr>
                            <td style="text-align: center;background : #B2BBC1;">Grand Total</td> 
                            <td style="text-align: right;background : #FB4EFF;">' . number_format((int)sumByKey($dataBywave, 'Break Case')) . '</td>
                            <td style="text-align: right;background : #FF7B15;">' . number_format((int)sumByKey($dataBywave, 'Overflow')) . '</td> 
                            <td style="text-align: right;background : #5FFFEB;">' . number_format((int)sumByKey($dataBywave, 'FullCase')) . '</td>
                            <td style="text-align: right;background : #5BE1D0;">' . number_format((int)sumByKey($dataBywave, 'Bulk Pull (Fast)')) . '</td>
                            <td style="text-align: right;background : #3F9D91;">' . number_format((int)sumByKey($dataBywave, 'FullCase + BUL')) . '</td>
                            <td style="text-align: right;background : #FFDD3D;">' . number_format((int)sumByKey($dataBywave, 'Security')) . '</td>
                            <td style="text-align: right;background : #FFD200;">' . number_format((int)sumByKey($dataBywave, 'Security เหล้า Break')) . '</td>
                            <td style="text-align: right;background : #E2A4FF;">' . number_format((int)sumByKey($dataBywave, 'Strong Room')) . '</td>
                            <td style="text-align: right;background : #9DE96C;">' . number_format((int)sumByKey($dataBywave, 'POP')) . '</td>
                            <td style="text-align: right;">' . number_format((int)sumByKey($dataBywave, 'No : Location')) . '</td> 
                        </tr> 
                    </table>';
    }
} else if ($reportName == 'workloadAfter') {

    $api_url = $urlAPI . '/API/Workload/ReportRP18After';
    $rp18bSectionAfter = curl_init();
    curl_setopt($rp18bSectionAfter, CURLOPT_URL, $api_url);
    curl_setopt($rp18bSectionAfter, CURLOPT_POST, true);
    curl_setopt($rp18bSectionAfter, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($rp18bSectionAfter);
    if (curl_errno($rp18bSectionAfter)) {
        echo 'Error:' . curl_error($rp18bSectionAfter);
    }
    curl_close($rp18bSectionAfter);
    $dataBySectionAfter = json_decode($response, true);

    if ($dataBySectionAfter === null) {
        echo "Failed to decode JSON.";
    } else {
        $record = '';
        for ($i = 0; $i < sizeof($dataBySectionAfter); $i++) {
            $bg = '';
            if ($dataBySectionAfter[$i]['iZone'] == 'Break Case') {
                $bg = 'background : #FD92FF;';
            } else if ($dataBySectionAfter[$i]['iZone'] == 'FullCase') {
                $bg = 'background : #9DF4E9;';
            } else if ($dataBySectionAfter[$i]['iZone'] == 'Dynamic Bulk Pull') {
                $bg = 'background : #5BE1D0;';
            } else if ($dataBySectionAfter[$i]['iZone'] == 'Security') {
                $bg = 'background : #FFEC91;';
            } else if ($dataBySectionAfter[$i]['iZone'] == 'Overflow') {
                $bg = 'background : #FF9A4C;';
            } else if ($dataBySectionAfter[$i]['iZone'] == 'POP') {
                $bg = 'background : #9DE96C;';
            } else if ($dataBySectionAfter[$i]['iZone'] == 'Strong Room') {
                $bg = 'background : #E2A4FF;';
            }

            $record .= '<tr> 
                                <td style="text-align: left;' . $bg . '">' . json_decode(json_encode($dataBySectionAfter[$i]['iZone'])) . '</td>  
                                <td style="text-align: center;' . $bg . '">' . json_decode(json_encode($dataBySectionAfter[$i]['iZoneCode'])) . '</td> 
                                <td style="text-align: right;' . $bg . '">' . number_format((int)json_decode(json_encode($dataBySectionAfter[$i]['iItem']))) . '</td> 
                                <td style="text-align: right;' . $bg . '">' . number_format((int)json_decode(json_encode($dataBySectionAfter[$i]['iOrderQty']))) . '</td>  
                                <td style="text-align: right;background : #FFE5D1;">' . number_format((int)json_decode(json_encode($dataBySectionAfter[$i]['iShipQty']))) . '</td>  
                                <td style="text-align: right;">' . number_format((int)json_decode(json_encode($dataBySectionAfter[$i]['iNotshipBOH']))) . '</td>  
                                <td style="text-align: right;background : #FFD7D7;">' . number_format((int)json_decode(json_encode($dataBySectionAfter[$i]['Short']))) . '</td>  
                                <td style="text-align: right;">' . number_format((int)json_decode(json_encode($dataBySectionAfter[$i]['iNotShipQty']))) . '</td>  
                                <td style="text-align: right;background : #D4E4FF;">' . number_format((int)json_decode(json_encode($dataBySectionAfter[$i]['oLPN']))) . '</td>  
                                <td style="text-align: right;background : #FFE5D1;">' . json_decode(json_encode($dataBySectionAfter[$i]['FillinRate'])) . '%</td> 
                            </tr>';
        }
        echo  '<style>  
                        table {
                            width: fit-content;
                            border-collapse: collapse;
                            border: 1px solid #ccc; 
                        }
                
                        th, td { 
                            font-weight: 500;
                            border: 1px solid #ccc;
                            padding: 8px;
                            text-align: left;
                        }
                        
                        th {
                            text-align: center;
                        }
                    </style>
                    <table border="1">
                        <tr>
                            <th colspan="15" rowspan="2" style="text-align: center;background : #00AB94;color:#fff;font-weight: 800;font-size: 20px;">รายงาน WORKLOAD หลัง WAVE  DCMC</th> 
                        </tr>
                        <tr></tr>
                        <tr>
                            <th colspan="1" style="text-align: left;background : #32CBB7;color:#fff;font-weight: 800;font-size: 2rem;">WH : 901</th> 
                            <th colspan="14" style="text-align: left;background : #32CBB7;color:#fff;font-weight: 800;font-size: 2rem;">' . number_format((int)json_decode(json_encode($dataCountWaveInfo[0]['totalCustomerP']))) . ' สาขา</th>
                        </tr>
                        <tr>
                            <th colspan="15" style="text-align: left;background : #32CBB7;color:#fff;font-weight: 800;font-size: 2rem;">Date : ' . ((new DateTime())->modify('-1 day'))->format('d/m/Y') . '</th> 
                        </tr>
                        <tr>
                            <th style="text-align: center;width:130px;background : #657884;font-weight: 700;font-size: 1rem;">Zone</th> 
                            <th style="text-align: center;width:95px;background : #657884;font-weight: 700;font-size: 1rem;">Zone Code</th>
                            <th style="text-align: center;width:95px;background : #657884;font-weight: 700;font-size: 1rem;">Item</th> 
                            <th style="text-align: center;width:95px;background : #657884;font-weight: 700;font-size: 1rem;">Order Qty</th>
                            <th style="text-align: center;width:95px;background : #FF8B32;font-weight: 700;font-size: 1rem;">Ship Qty</th>
                            <th style="text-align: center;width:95px;background : #657884;font-weight: 700;font-size: 1rem;">Not ship BOH</th>
                            <th style="text-align: center;width:95px;background : #FF3A3A;font-weight: 700;font-size: 1rem;">Short</th>
                            <th style="text-align: center;width:95px;background : #657884;font-weight: 700;font-size: 1rem;">Not Ship Qty</th>
                            <th style="text-align: center;width:95px;background : #478CFF;font-weight: 700;font-size: 1rem;">oLPN</th>
                            <th style="text-align: center;width:95px;background : #FF8B32;font-weight: 700;font-size: 1rem;">Fill in Rate</th> 
                        </tr>' . $record . '
                        <tr>
                            <td style="text-align: center;background : #657884;font-weight: 600;font-size: 1rem;" colspan="2">Grand Total</td> 
                            <td style="text-align: right;background : #657884;font-weight: 600;">' . number_format((int)sumByKey($dataBySectionAfter, 'iItem')) . '</td>
                            <td style="text-align: right;background : #657884;font-weight: 600;">' . number_format((int)sumByKey($dataBySectionAfter, 'iOrderQty')) . '</td> 
                            <td style="text-align: right;background : #FF8B32;font-weight: 600;">' . number_format((int)sumByKey($dataBySectionAfter, 'iShipQty')) . '</td>
                            <td style="text-align: right;background : #657884;font-weight: 600;">' . number_format((int)sumByKey($dataBySectionAfter, 'iNotshipBOH')) . '</td>
                            <td style="text-align: right;background : #FF3A3A;font-weight: 600;">' . number_format((int)sumByKey($dataBySectionAfter, 'Short')) . '</td> 
                            <td style="text-align: right;background : #657884;font-weight: 600;">' . number_format((int)sumByKey($dataBySectionAfter, 'iNotShipQty')) . '</td>
                            <td style="text-align: right;background : #478CFF;font-weight: 600;">' . number_format((int)sumByKey($dataBySectionAfter, 'oLPN')) . '</td>
                            <td style="text-align: right;background : #FF8B32;font-weight: 600;">' . number_format(100 - (sumByKey($dataBySectionAfter, 'iNotShipQty') * 100) / sumByKey($dataBySectionAfter, 'iOrderQty'), 2) . '%</td> 
                        </tr> 
                    </table>';
    }
} else if ($reportName == 'workloadAfterDeli') {

    // $api_url = $urlAPI . '/api/reportRP18/rp18DeliAfterbySection';
    // $rp18bSectionAfter = curl_init();
    // curl_setopt($rp18bSectionAfter, CURLOPT_URL, $api_url);
    // curl_setopt($rp18bSectionAfter, CURLOPT_POST, true);
    // curl_setopt($rp18bSectionAfter, CURLOPT_RETURNTRANSFER, true);
    // $response = curl_exec($rp18bSectionAfter);
    // if (curl_errno($rp18bSectionAfter)) {
    //     echo 'Error:' . curl_error($rp18bSectionAfter);
    // }
    // curl_close($rp18bSectionAfter);
    // $dataBySectionAfter = json_decode($response, true);

    // if ($dataBySectionAfter === null) {
    //     echo "Failed to decode JSON.";
    // } else {
    //     $record = '';
    //     for ($i = 0; $i < sizeof($dataBySectionAfter); $i++) {
    //         $bg = '';
    //         if ($dataBySectionAfter[$i]['iZone'] == 'Break Case') {
    //             $bg = 'background : #FD92FF;';
    //         } else if ($dataBySectionAfter[$i]['iZone'] == 'FullCase') {
    //             $bg = 'background : #9DF4E9;';
    //         } else if ($dataBySectionAfter[$i]['iZone'] == 'Dynamic Bulk Pull') {
    //             $bg = 'background : #5BE1D0;';
    //         } else if ($dataBySectionAfter[$i]['iZone'] == 'Security') {
    //             $bg = 'background : #FFEC91;';
    //         } else if ($dataBySectionAfter[$i]['iZone'] == 'Overflow') {
    //             $bg = 'background : #FF9A4C;';
    //         } else if ($dataBySectionAfter[$i]['iZone'] == 'POP') {
    //             $bg = 'background : #9DE96C;';
    //         } else if ($dataBySectionAfter[$i]['iZone'] == 'Strong Room') {
    //             $bg = 'background : #E2A4FF;';
    //         }

    //         $record .= '<tr> 
    //                             <td style="text-align: left;' . $bg . '">' . json_decode(json_encode($dataBySectionAfter[$i]['iZone'])) . '</td>  
    //                             <td style="text-align: center;' . $bg . '">' . json_decode(json_encode($dataBySectionAfter[$i]['iZoneCode'])) . '</td> 
    //                             <td style="text-align: right;' . $bg . '">' . number_format((int)json_decode(json_encode($dataBySectionAfter[$i]['iItem']))) . '</td> 
    //                             <td style="text-align: right;' . $bg . '">' . number_format((int)json_decode(json_encode($dataBySectionAfter[$i]['iOrderQty']))) . '</td>  
    //                             <td style="text-align: right;background : #FFE5D1;">' . number_format((int)json_decode(json_encode($dataBySectionAfter[$i]['iShipQty']))) . '</td>  
    //                             <td style="text-align: right;">' . number_format((int)json_decode(json_encode($dataBySectionAfter[$i]['iNotshipBOH']))) . '</td>  
    //                             <td style="text-align: right;background : #FFD7D7;">' . number_format((int)json_decode(json_encode($dataBySectionAfter[$i]['Short']))) . '</td>  
    //                             <td style="text-align: right;">' . number_format((int)json_decode(json_encode($dataBySectionAfter[$i]['iNotShipQty']))) . '</td>  
    //                             <td style="text-align: right;background : #D4E4FF;">' . number_format((int)json_decode(json_encode($dataBySectionAfter[$i]['oLPN']))) . '</td>  
    //                             <td style="text-align: right;background : #FFE5D1;">' . json_decode(json_encode($dataBySectionAfter[$i]['FillinRate'])) . '%</td> 
    //                         </tr>';
    //     }
    //     echo  '<style>  
    //                     table {
    //                         width: fit-content;
    //                         border-collapse: collapse;
    //                         border: 1px solid #ccc; 
    //                     }
                
    //                     th, td { 
    //                         font-weight: 500;
    //                         border: 1px solid #ccc;
    //                         padding: 8px;
    //                         text-align: left;
    //                     }
                        
    //                     th {
    //                         text-align: center;
    //                     }
    //                 </style>
    //                 <table border="1">
    //                     <tr>
    //                         <th colspan="15" rowspan="2" style="text-align: center;background : #00AB94;color:#fff;font-weight: 800;font-size: 20px;">รายงาน WORKLOAD หลัง WAVE  DCMC</th> 
    //                     </tr>
    //                     <tr></tr>
    //                     <tr>
    //                         <th colspan="1" style="text-align: left;background : #32CBB7;color:#fff;font-weight: 800;font-size: 2rem;">WH : 901</th> 
    //                         <th colspan="14" style="text-align: left;background : #32CBB7;color:#fff;font-weight: 800;font-size: 2rem;">' . number_format((int)json_decode(json_encode($dataCountWaveInfo[0]['totalCustomerP']))) . ' สาขา</th>
    //                     </tr>
    //                     <tr>
    //                         <th colspan="15" style="text-align: left;background : #32CBB7;color:#fff;font-weight: 800;font-size: 2rem;">Date : ' . ((new DateTime())->modify('-1 day'))->format('d/m/Y') . '</th> 
    //                     </tr>
    //                     <tr>
    //                         <th style="text-align: center;width:130px;background : #657884;font-weight: 700;font-size: 1rem;">Zone</th> 
    //                         <th style="text-align: center;width:95px;background : #657884;font-weight: 700;font-size: 1rem;">Zone Code</th>
    //                         <th style="text-align: center;width:95px;background : #657884;font-weight: 700;font-size: 1rem;">Item</th> 
    //                         <th style="text-align: center;width:95px;background : #657884;font-weight: 700;font-size: 1rem;">Order Qty</th>
    //                         <th style="text-align: center;width:95px;background : #FF8B32;font-weight: 700;font-size: 1rem;">Ship Qty</th>
    //                         <th style="text-align: center;width:95px;background : #657884;font-weight: 700;font-size: 1rem;">Not ship BOH</th>
    //                         <th style="text-align: center;width:95px;background : #FF3A3A;font-weight: 700;font-size: 1rem;">Short</th>
    //                         <th style="text-align: center;width:95px;background : #657884;font-weight: 700;font-size: 1rem;">Not Ship Qty</th>
    //                         <th style="text-align: center;width:95px;background : #478CFF;font-weight: 700;font-size: 1rem;">oLPN</th>
    //                         <th style="text-align: center;width:95px;background : #FF8B32;font-weight: 700;font-size: 1rem;">Fill in Rate</th> 
    //                     </tr>' . $record . '
    //                     <tr>
    //                         <td style="text-align: center;background : #657884;font-weight: 600;font-size: 1rem;" colspan="2">Grand Total</td> 
    //                         <td style="text-align: right;background : #657884;font-weight: 600;">' . number_format((int)sumByKey($dataBySectionAfter, 'iItem')) . '</td>
    //                         <td style="text-align: right;background : #657884;font-weight: 600;">' . number_format((int)sumByKey($dataBySectionAfter, 'iOrderQty')) . '</td> 
    //                         <td style="text-align: right;background : #FF8B32;font-weight: 600;">' . number_format((int)sumByKey($dataBySectionAfter, 'iShipQty')) . '</td>
    //                         <td style="text-align: right;background : #657884;font-weight: 600;">' . number_format((int)sumByKey($dataBySectionAfter, 'iNotshipBOH')) . '</td>
    //                         <td style="text-align: right;background : #FF3A3A;font-weight: 600;">' . number_format((int)sumByKey($dataBySectionAfter, 'Short')) . '</td> 
    //                         <td style="text-align: right;background : #657884;font-weight: 600;">' . number_format((int)sumByKey($dataBySectionAfter, 'iNotShipQty')) . '</td>
    //                         <td style="text-align: right;background : #478CFF;font-weight: 600;">' . number_format((int)sumByKey($dataBySectionAfter, 'oLPN')) . '</td>
    //                         <td style="text-align: right;background : #FF8B32;font-weight: 600;">' . sumByKey($dataBySectionAfter, 'iOrderQty')  ? '0.00' : number_format(100 - (sumByKey($dataBySectionAfter, 'iNotShipQty') * 100) / sumByKey($dataBySectionAfter, 'iOrderQty'), 2) . '%</td> 
                            
    
    //                     </tr> 
    //                 </table>';
    // }
}



// $rawData = file_get_contents("php://input");

// $dataBoth = json_decode($rawData, true);
// $dataBoth = $dataBoth['data'];

// if ($dataBoth) {

//     $response = [
//         'status' => 'success',
//         'message' => 'Data received',
//         'data' =>  $dataBoth
//     ];
// } else {
//     $response = [
//         'status' => 'error',
//         'message' => 'Invalid JSON'
//     ];
// }

// header('Content-Type: application/json');
// echo json_encode($response); 
