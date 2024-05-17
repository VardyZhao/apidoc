<?php

/*
可解析参数格式：
---------------
a=1&b=oqwep123&c=1.9288
---------------
a:1
b:oqwep123
c:1.9288
---------------
{
    "a":1,
    "b":"oqwep123",
    "c":1.9288,
    "d": [1,2,3],
    "e": ["1","2","3"],
    "f": {
        "f_1": 1,
        "f_2": "asdfas"
    }
}
---------------
*/

/*
$jsonString = '{
    "a": 1,
    "b": "oqwep123",
    "c": 1.9288,
    "d": [1, 2, 3],
    "e": ["1", "2", "3"],
    "f": {
        "f_1": 1,
        "f_2": "asdfas",
        "f_3": [1, 2, 3, 4],
        "f_4": {
            "pppp": "11111",
            "qqqq": "aaaaa"
        },
        "f_5": [
            {
                "key1": {
                    "f_key1": [1,2,3],
                    "f_key2": 1231
                }
            }
        ]
    },
    "g": [
        {
            "g_1": 1,
            "g_2": "aaaa"
        },
        {
            "g_1": 2,
            "g_2": "bbbb"
        }
    ]
}';

// 解析JSON字符串
$data = json_decode($jsonString); // 使用true参数将JSON对象转换为关联数组

//|a|number||1|
//|b|string||oqwep123|
//|c|number||1.9288|
//|d|array(number)||[1,2,3]|
//|e|array(string)||["1","2","3"]|
//|f|object||object|
//|- f_1|number||1|
//|- f_2|string||asdfas|
//|- f_3|array(number)||[1,2,3,4]|
//|- f_4|object||object|
//|-- pppp|string||11111|
//|-- qqqq|string||aaaaa|
//|- f_5|array(object)||array(object)|
//|-- key1|number||1|

// 调用函数并输出结果
$output = '';
foreach ($data as $key => $value) {
    $output .= generateJsonParamsMd($key, $value);
}
echo $output;
exit();
*/


$md = '
### %s
- 接口：%s
- method: %s
%s
- 请求参数
  |参数名|类型|是否必填|说明|示例|
  |--|--|--|--|--|
  %s
- 请求参数示例
  ```%s
  %s
  ```
- 返回参数
  |参数名|类型|是否必填|说明|示例|
  |--|--|--|--|--|
  %s
- 返回示例
  ```json
  %s
  ```
';

$apiName           = $_POST['api_name'] ?? '';
$apiUrl            = $_POST['api_url'] ?? '';
$requestMethod     = $_POST['request_method'] ?? '';
$requestHeader     = $_POST['request_header'] ?? '';
$requestParamsType = $_POST['request_params_type'] ?? 0;
$inputParams       = $_POST['request_params'] ?? '';
$responseData      = $_POST['response_data'] ?? '';

$requestParams   = [];
$requestDemoType = 'txt';
$requestParamsMd = '';
$responseDataMd  = '';

$requestHeaderMd = '
- 请求头
  |参数名|是否必填|值|说明|
  |--|--|--|--|
';
if (!empty($requestHeader)) {
    foreach (explode("\n", $requestHeader) as $headerItem) {
        $tmpItem         = explode(':', $headerItem);
        $requestHeaderMd .= generateRequestHeaderMarkdown($tmpItem[0] ?? '', $tmpItem[1] ?? '');
    }
}

switch ($requestParamsType) {
    case 1:
        foreach (explode('&', $inputParams) as $item) {
            $tmpItem         = explode('=', $item);
            $requestParamsMd .= generateRequestParamsMarkdown($tmpItem[0] ?? '', $tmpItem[1] ?? '');
        }
        break;
    case 2:
        foreach (explode("\n", $inputParams) as $item) {
            $tmpItem         = explode(':', $item);
            $requestParamsMd .= generateRequestParamsMarkdown($tmpItem[0] ?? '', $tmpItem[1] ?? '');
        }
        break;
    case 3:
        $requestDemoType = 'json';
        $data            = json_decode($inputParams);
        if (!$data) {
            $requestParamsMd = generateJsonParamsMd($data);
        }
        break;
    default:
        exit('error request params type');
}

$jsonResponseData = json_decode($responseData);
if ($jsonResponseData) {
    $responseDataMd = generateJsonParamsMd($jsonResponseData);
}

echo sprintf(
    $md,
    $apiName,
    $apiUrl,
    $requestMethod,
    $requestHeaderMd,
    $requestParamsMd,
    $requestDemoType,
    $inputParams,
    $responseDataMd,
    $responseData
);

function generateRequestHeaderMarkdown($key, $value): string
{
    return "|$key|Y|$value|||\n";
}

function generateRequestParamsMarkdown($key, $value): string
{
    $type = gettype($value);
    return "|$key|$type|Y||$value|\n";
}

function generateJsonParamsMd($value, $key = '', $prefix = ''): string
{
    $output     = '';
    $paramsName = substr($prefix, 1) . $key;
    if (is_object($value)) {
        if (!empty($paramsName)) {
            $output .= "|$paramsName|object|Y|||\n";
        }
        foreach ($value as $subKey => $subItem) {
            $output .= generateJsonParamsMd($subItem, $subKey, trim($prefix) . '- ');
        }
    } elseif (is_array($value)) {
        $firstItem = current($value);
        if (is_object($firstItem)) {
            $output .= "|$paramsName|array(object)|Y|||\n";
            foreach ($firstItem as $subKey => $subItem) {
                $output .= generateJsonParamsMd($subItem, $subKey, trim($prefix) . '- ');
            }
        } elseif (is_array($firstItem)) {
            $output .= "|$paramsName|array(array)|Y|||\n";
            $output .= generateJsonParamsMd($firstItem, $key, trim($prefix) . '- ');
        } elseif (is_string($firstItem)) {
            $output .= "|$paramsName|array(string)|Y||$firstItem|\n";
        } elseif (is_numeric($firstItem)) {
            $output .= "|$paramsName|array(number)|Y||$firstItem|\n";
        } else {
            $output .= "|$paramsName|array()|Y||[]|\n";
        }
    } elseif (is_string($value)) {
        $output .= "|$paramsName|string|Y||$value|\n";
    } elseif (is_numeric($value)) {
        $output .= "|$paramsName|number|Y||$value|\n";
    }
    return $output;
}
