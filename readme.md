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

## Example

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

## Table

記錄資料

|  Column Name      |         Description        |
|-------------------|----------------------------|
|  uuid             |   請求識別 id (Primary Key) |
|  route            |   請求對應路由              |
|  route_params     |   路由參數 (json)           |
|  request_params   |   請求參數 (json)           |
|  response_contents|   回應內容 (json)           |
|  job_status       |   請求處理狀態              |
|  ip               |   Client 來源 ip           |
