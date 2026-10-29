 
<?php
// เรียก API โดยไม่ใช้ cURL
function fetchApiResponse($url)
{
    $options = [
        'http' => [
            'method'        => 'GET',
            'header'        => "Accept: application/json\r\n",
            'timeout'       => 15,
            'ignore_errors' => true
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        error_log('API request failed: ' . $url);
        echo 'Error: ไม่สามารถเชื่อมต่อ API ได้';
        return false;
    }

    return $response;
}


$urlAPI_Option = 'http://192.168.248.20:5003';

$api_url_Option = $urlAPI_Option . '/api/cdc/hygiene/selections';

$response_Option = fetchApiResponse($api_url_Option);

$data_Option = json_decode($response_Option, true);

/////////////////////////////////////////////////////////////////////////////////////////////////////

function findOneByHDetail(array $data, string $keyword, bool $like = false): ?array
{
    foreach ($data as $item) {
        if (!isset($item['hDetail'])) {
            continue;
        }

        if ($like && mb_strpos($item['hDetail'], $keyword) !== false) {
            return $item;
        }

        if (!$like && $item['hDetail'] === $keyword) {
            return $item;
        }
    }

    return null;
}
function formatText(string $text, array $data_Option, string $col): ?string
{
    $items = explode('  ,', $text);
    $iNoList = [];

    foreach ($items as $item) {

        $item = trim($item);

        $found = findOneByHDetail($data_Option, $item);

        if ($found && isset($found[$col])) {
            $iNoList[] = $found[$col];
        }
    }

    if (empty($iNoList)) {
        return null;
    } 
    
    $iNoList = array_values(array_unique($iNoList));

    return implode(',', $iNoList);
}

/////////////////////////////////////////////////////////////////////////////////////////////////////

$urlAPI = 'http://192.168.248.20:5003';

$api_url = $urlAPI . '/api/cdc/hygiene/reportTruck';
$response = fetchApiResponse($api_url);
$data = json_decode($response, true);

/////////////////////////////////////////////////////////////////////////////////////////////////////

if ($data === null) {
    echo "Failed to decode JSON.";
} else {

    $record = '';
    for ($i = 0; $i < sizeof($data); $i++) {

        $fields = [
            $data[$i]['hHealth'] ?? '',
            $data[$i]['hTruckAsst'] ?? '',
            $data[$i]['hTruckHygienes'] ?? '',
            $data[$i]['hHygienesDrive'] ?? '',
            $data[$i]['hBehavior'] ?? ''
        ];
        

        $fields = array_filter($fields, function ($val) {
            return trim($val) !== '';
        }); 
        
        $text = implode('  ,', $fields); 
         
        $result1 = formatText($text, $data_Option, 'iNo');
        $result2 = formatText($text, $data_Option, 'hDetail');
        $result3 = formatText($text, $data_Option, 'HygieneType');
  

        $record .= '<tr> 
                        <td style="text-align: center;color:#222222ff; border: 1px solid #919191ff;">' . json_decode(json_encode($i + 1)) . '</th>   
                        <td style="text-align: center;color:#222222ff; border: 1px solid #919191ff;">' . json_decode(json_encode($data[$i]['hRoute'])) . '</th> 
                        <td style="text-align: center;color:#222222ff; border: 1px solid #919191ff;">' . json_decode(json_encode($data[$i]['hDeptCompany'])) . '</th> 
                        <td style="text-align: center;color:#222222ff; border: 1px solid #919191ff;">' . json_decode(json_encode($data[$i]['hTagID'])) . '</th>   
                        <td style="text-align: left;color:#222222ff; border: 1px solid #919191ff;">' . json_decode(json_encode($data[$i]['hName'])) . '</th>  
                        <td style="text-align: left;color:#222222ff; border: 1px solid #919191ff;">' . $result1 . '</th> 
                        <td style="text-align: left;color:#222222ff; border: 1px solid #919191ff;">' . $result2 . '</th> 
                        <td style="text-align: left;color:#222222ff; border: 1px solid #919191ff;">' . $result3 . '</th> 
                        <td style="text-align: left;color:#222222ff; border: 1px solid #919191ff;">' . json_decode(json_encode($data[$i]['hOthers'])) . '</th> 
                        <td style="text-align: left;color:#222222ff; border: 1px solid #919191ff;">' . json_decode(json_encode($data[$i]['hRecBy'])) . '</th>   
                    </tr>';
    }

    echo  '<style>  
            table {
                width: fit-content;
                border-collapse: collapse;
             
            }
    
            th, td { 
                font-weight: 500; 
                padding: 8px;
                text-align: left;
                border: 0px solid rgba(145, 145, 145, 0);
            }
            
            th {
                text-align: center;
            }
        </style>
        <table border="1">
            <tr>
                <th colspan="10" rowspan="2" style="text-align: center; color:#222222ff; font-weight: 800;font-size: 20px;">บริษัท ซีพี ออลล์ จำกัด (มหาชน)</th> 
            </tr>
            <tr></tr>
            <tr>
                <th colspan="10" rowspan="2" style="text-align: center; color:#222222ff; font-weight: 800;font-size: 20px;">แบบฟอร์ม เรื่อง สรุปการควบคุมคุณภาพรถขนส่งของผู้รับเหมา CDC</th> 
            </tr>
            <tr></tr> 
            <tr>
                <th colspan="10" style="text-align: center; color:#222222ff; font-weight: 800;font-size: 16px"> รหัสเอกสาร B3-FM-06-02-C36  ปรับปรุงครั้งที่ 0  วันที่ประกาศใช้ 1 พฤษภาคม 2560</th> 
            </tr>
            <tr></tr> 
            <tr>
                <th colspan="10" style="text-align: left; color:#222222ff; font-weight: 800;font-size: .9rem;">วันที่ : ' . ((new DateTime()))->format('d/m/Y') . '</th> 
            </tr>
            <tr>
                <th style="text-align: center;width:40px; color:#222222ff;font-weight: 700;font-size: 1rem; border: 1px solid #919191ff;">ลำดับ</th>  
                <th style="text-align: center;width:95px; color:#222222ff;font-weight: 700;font-size: 1rem; border: 1px solid #919191ff;">สายรถ</th>
                <th style="text-align: center;width:220px; color:#222222ff;font-weight: 700;font-size: 1rem; border: 1px solid #919191ff;">บริษ้ท</th>
                <th style="text-align: center;width:95px; color:#222222ff;font-weight: 700;font-size: 1rem; border: 1px solid #919191ff;">ทะเบียนรถ</th> 
                <th style="text-align: center;width:170px; color:#222222ff;font-weight: 700;font-size: 1rem; border: 1px solid #919191ff;">ชื่อพนักงานผู้ขนส่ง</th>
                <th style="text-align: center;width:120px; color:#222222ff;font-weight: 700;font-size: 1rem; border: 1px solid #919191ff;">รหัสข้อบกพร่อง</th>
                <th style="text-align: center;width:220px; color:#222222ff;font-weight: 700;font-size: 1rem; border: 1px solid #919191ff;">สาเหตุ</th>
                <th style="text-align: center;width:110px; color:#222222ff;font-weight: 700;font-size: 1rem; border: 1px solid #919191ff;">ด้านที่พบ</th>
                <th style="text-align: center;width:155px; color:#222222ff;font-weight: 700;font-size: 1rem; border: 1px solid #919191ff;">อื่นๆ</th>
                <th style="text-align: center;width:100px; color:#222222ff;font-weight: 700;font-size: 1rem; border: 1px solid #919191ff;">ผู้บันทึก</th> 
            </tr>' . $record . '
                
        </table>';
}
