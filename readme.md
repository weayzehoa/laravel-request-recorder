## Recorder

說明

記錄器部分，每一次 Client 端發送請求，通過 api route 時，將內容資訊記錄到資料庫中。
允許 Client 自訂請求與回應的 X-Correlation-ID，若自訂的 X-Correlation-ID 與資料庫衝突，則回應會設置成 409 表示衝突。
回應部分，是系統完成請求處理後，產生回應，才將此內容寫入資料庫。

安裝
```
composer require littlebookboy/laravel-request-recorder
```

註冊服務提供者
```
LittleBookBoy\Request\Recorder\RequestRecorderServiceProvider::class,
```

發佈遷移
```
php artisan vendor:publish --provider="LittleBookBoy\Request\Recorder\RequestRecorderServiceProvider"
```

建立資料表
```
php artisan migrate
```

## Usage

設置 api 路由，例如請求搜尋指定 id 用戶
```
Route::get('user/{id}', 'UserController@show');
```

新增控制器
```
php artisan make:controller UserController
```

對應控制器 UserController
```
public function show()
{
    return collect([
        'name' => 'littlebookboy',
        'saying' => 'Hello Recorder',
    ])->toJson();
}
```

記錄開關```/path/to/project/config/request-recorder.php```，```enabled false```表示關閉記錄器
```
'enabled' => false,
```
若要變更記錄群組可設定```group```，設為```api```表示請求從 api route 進來的請求都會被記錄
```
'group' => 'api'
```

## Except

實務上，有時需要讓系統排除某些 request 不要寫入，本套件提供了兩種排除記錄的方式

1. 排除指定 http method，您可以在 config 中設置 ```except```，表示要記錄除了這些方法之外的請求, 可設置的方法有 ```'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'```，例如設置了 ```GET```，系統將忽略記錄所有 get 請求
2. 排除指定 route name with http code，您可以在 config 中設置 ```skip_routes```，將經過命名的路由設置到 ```route_name```，並選擇要過濾的狀態碼，若要全部過濾，可選填 ```*``` 或留空

### Config Example
```
/**
 * - enabled : true or false
 * - group : route middleware group name
 * - except : 僅記錄除了這些方法之外的請求, 'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'
 * - skip_routes : 僅記錄除了這些路由之外的請求, 也可限定只排除該路由的某些 rsponse http code
 */
'recorder' => [
    'enabled' => true,
    'group' => 'api',
    'except' => ['GET'],
    'skip_routes' => [
        [
            'route_name' => 'route.name.1',
            'http_code' => ['201']
        ],
        [
            'route_name' => 'route.name.2',
            'http_code' => ['409', '422']
        ],
        [
            'route_name' => 'route.name.3',
            'http_code' => ['*']
        ]
    ]
]
```


## Table

記錄資料

|  Column Name      |         Description        |
|-------------------|----------------------------|
|  uuid             |   請求識別 id (Primary Key) |
|  method           |   請求 http 方法            |
|  route            |   請求對應路由               |
|  route_params     |   路由參數 (json)           |
|  request_headers  |   請求表頭 (json)           |
|  request_params   |   請求參數 (json)           |
|  response_headers |   回應表頭 (json)           |
|  response_contents|   回應內容 (json)           |
|  ip               |   Client 來源 ip           |
