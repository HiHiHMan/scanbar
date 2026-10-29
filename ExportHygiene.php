 
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
 
// $reportName = isset($_POST['reportName']) ? $_POST['reportName'] : null;
$urlAPI = 'http://192.168.248.20:5003';

$api_url = $urlAPI . '/api/cdc/hygiene/report';
$response = fetchApiResponse($api_url);
$data = json_decode($response, true);

if ($data === null) {
    echo "Failed to decode JSON.";

} else { 
     
    $record = '';
    for ($i = 0; $i < sizeof($data); $i++) {
        $record .= '<tr> 
                        <td style="text-align: center;color:#222222ff;">'.json_decode(json_encode($i + 1)).'</th>  
                        <td style="text-align: center;color:#222222ff;">'.json_decode(json_encode($data[$i]['hRoute'])).'</th>  
                        <td style="text-align: center;color:#222222ff;">'.json_decode(json_encode($data[$i]['hTagID'])).'</th>  
                        <td style="text-align: left;color:#222222ff;">'.json_decode(json_encode($data[$i]['hDeptCompany'])).'</th>  
                        <td style="text-align: left;color:#222222ff;">'.json_decode(json_encode($data[$i]['hName'])).'</th>    
                        <td style="text-align: left;color:#222222ff;">'.json_decode(json_encode($data[$i]['hDrvTwo'])).'</th>  
                        <td style="text-align: center;color:#222222ff;">'.json_decode(json_encode($data[$i]['hHands'])).'</th>  
                        <td style="text-align: center;color:#222222ff;">'.json_decode(json_encode($data[$i]['hFingernails'])).'</th>  
                        <td style="text-align: center;color:#222222ff;">'.json_decode(json_encode($data[$i]['hJewelry'])).'</th>  
                        <td style="text-align: center;color:#222222ff;">'.json_decode(json_encode($data[$i]['hBeard'])).'</th>  
                        <td style="text-align: center;color:#222222ff;">'.json_decode(json_encode($data[$i]['hClothing'])).'</th>  
                        <td style="text-align: center;color:#222222ff;">'.json_decode(json_encode($data[$i]['hCap'])).'</th>  
                        <td style="text-align: center;color:#222222ff;">'.json_decode(json_encode($data[$i]['hShoes'])).'</th>  
                        <td style="text-align: center;color:#222222ff;">'.json_decode(json_encode($data[$i]['hHealth'])).'</th>
                        <td style="text-align: left;color:#222222ff;">' . json_decode(json_encode($data[$i]['hOthers'])) . '</th>   
                    </tr>';
    }
 
    echo  '<style>  
            table {
                width: fit-content;
                border-collapse: collapse;
                border: 1px solid #919191ff; 
            }
    
            th, td { 
                font-weight: 500;
                border: 1px solid #919191ff;
                padding: 8px;
                text-align: left;
            }
            
            th {
                text-align: center;
            }
        </style>
        <table border="1">
            <tr>
                <th colspan="15" rowspan="2" style="text-align: center; color:#222222ff; font-weight: 800;font-size: 20px;">แบบฟอร์ม  เรื่อง  รายงานการสุ่มตรวจสุขลักษณะส่วนบุคคล</th> 
            </tr>
            <tr></tr> 
            <tr>
                <th colspan="15" style="text-align: center; color:#222222ff; font-weight: 800;font-size: 16px">ผู้ขนส่ง </th> 
            </tr>
            <tr></tr> 
            <tr>
                <th colspan="15" style="text-align: left; color:#222222ff; font-weight: 800;font-size: .9rem;">Date : ' . ((new DateTime()))->format('d/m/Y') . '</th> 
            </tr>
            <tr>
                <th style="text-align: center;width:40px; color:#222222ff;font-weight: 700;font-size: 1rem;">ลำดับ</th> 
                <th style="text-align: center;width:95px; color:#222222ff;font-weight: 700;font-size: 1rem;">สายรถ</th>
                <th style="text-align: center;width:95px; color:#222222ff;font-weight: 700;font-size: 1rem;">ทะเบียนรถ</th> 
                <th style="text-align: center;width:170px; color:#222222ff;font-weight: 700;font-size: 1rem;">บริษัท</th>
                <th style="text-align: center;width:155px; color:#222222ff;font-weight: 700;font-size: 1rem;">รายชื่อ</th>
                <th style="text-align: center;width:155px; color:#222222ff;font-weight: 700;font-size: 1rem;">ผู้ช่วยคนขับ</th>
                <th style="text-align: center;width:155px; color:#222222ff;font-weight: 700;font-size: 1rem;">มือ</th>
                <th style="text-align: center;width:155px; color:#222222ff;font-weight: 700;font-size: 1rem;">เล็บมือ</th>
                <th style="text-align: center;width:155px; color:#222222ff;font-weight: 700;font-size: 1rem;">เครื่องประดับ</th>
                <th style="text-align: center;width:155px; color:#222222ff;font-weight: 700;font-size: 1rem;">หนวดเครา</th> 
                <th style="text-align: center;width:155px; color:#222222ff;font-weight: 700;font-size: 1rem;">เสื้อ - กางเกง</th>
                <th style="text-align: center;width:155px; color:#222222ff;font-weight: 700;font-size: 1rem;">หมวก</th>
                <th style="text-align: center;width:155px; color:#222222ff;font-weight: 700;font-size: 1rem;">รองเท้า บูท/Safety</th>
                <th style="text-align: center;width:155px; color:#222222ff;font-weight: 700;font-size: 1rem;">สุขภาพ</th>
                <th style="text-align: center;width:155px; color:#222222ff;font-weight: 700;font-size: 1rem;">อื่นๆ</th>
            </tr>' . $record . '
                
        </table>';
}
