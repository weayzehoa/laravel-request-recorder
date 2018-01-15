<?php

namespace LittleBookBoy\Request\Recorder\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use LittleBookBoy\Request\Recorder\Models\RequestRecords;
use Ramsey\Uuid\Uuid;

class RequestRecorderMiddleware
{
    /* X_CORRELATION_ID string $requestId Http 表頭名稱，內容為請求識別 id 名稱 */
    const X_CORRELATION_ID = 'X-Correlation-ID';

    /* $uuid string 請求識別 id */
    protected $requestId;

    /**
     * RecorderMiddleware constructor
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        // 初始化 request id，傳入請求由自訂表頭 X-Correlation-ID 或由系統產生一組請求識別 id
        $this->requestId = $this->getRequestId($request);

        // 設定請求識別 id 於表頭（若是系統產生需設定）
        $request->headers->set(self::X_CORRELATION_ID, $this->requestId);
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // 檢查 request id 是否已存在
        if ($this->isRequestIdConflict()) {
            // 請求識別 id 若已存在，回應 409 Conflict 表示因為請求存在衝突無法處理該請求
            return Response()->make('', 409);
        }

        // 檢查是否要記錄此請求：方法 isExceptMethod 為真表示不記錄
        if ($this->isExceptMethod($request->getMethod())) {
            return $next($request);
        }

        // 加上回應表頭
        $response = $next($request);
        $response->headers->add([self::X_CORRELATION_ID => $this->requestId]);

        // 檢查是否要記錄此請求：路由、http code isExceptRoutes 為真表示不記錄
        if ($this->isExceptRoutes($request->route()->getName(), $response->status())) {
            return $next($request);
        }

        // 在收到請求時就建立記錄
        $this->storeApiResponseLog($request, $response);

        return $response;
    }

    /**
     * 創建一筆請求記錄並返回
     * @param $response
     * @param $request
     */
    protected function storeApiResponseLog($request, $response)
    {
        // 初始化
        $record = new RequestRecords();
        // 請求識別 id
        $record->uuid = $request->headers->get(self::X_CORRELATION_ID);
        // 請求方法
        $record->method = $request->getMethod();
        // 請求路由
        $record->route = $request->route()->uri();
        // 請求路由
        $record->route_params = collect($request->route()->parameters())->toJson();
        // 收到的請求的 Header
        $record->request_headers = collect($request->headers)->toJson();
        // 收到的請求原始內容
        $record->request_params = collect($request->toArray())->toJson();
        // 對應請求所回應的 Header
        $record->response_headers = collect($response->headers)->toJson();
        // 對應請求所回應的內容
        $record->response_code = $response->status();
        // 對應請求所回應的內容
        $record->response_content = $response->content();
        // 請求來源 ip
        $record->ip = $request->ip();
        // 找出對應的請求記錄，或創建一筆請求記錄
        $record->save();
    }

    /**
     * 判斷是否有自訂請求識別 id，若無則由系統產生一組 uuid 並回傳
     * @param $request
     * @return string
     */
    private function getRequestId($request)
    {
        // 取出 X-Correlation-ID 表頭請求識別 id
        $requestId = $request->headers->get(self::X_CORRELATION_ID);
        // 回傳請求識別 id 或由系統產生一組 uuid
        return ($requestId != '')
            ? $requestId
            : Uuid::uuid4()->toString();
    }

    /**
     * 檢查請求識別 id 是否已存在
     */
    protected function isRequestIdConflict()
    {
        return (RequestRecords::where('uuid', '=', $this->requestId)->get()->count() > 0);
    }

    /**
     * 檢查是否不要記錄此請求： http 方法名 'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'
     * @return bool false 表示記錄該請求方法，true 表示不記錄（排除掉該方法的所有請求）
     */
    protected function isExceptMethod($method)
    {
        // 使用者在 config 中沒有設定 except 則回應 false，記錄該請求資訊
        if (empty(config('request-recorder.recorder.except'))) {
            return false;
        }

        return in_array(strtoupper($method), config('request-recorder.recorder.except'));
    }

    /**
     * 檢查是否不要記錄此請求：路由名、http code 如 200、409
     * @return bool false 表示記錄該請求方法，true 表示不記錄（排除掉該方法的所有請求）
     */
    protected function isExceptRoutes($routeName, $responseCode)
    {
        $skipRoutes = collect(config('request-recorder.recorder.skip_routes'))->groupBy('route_name');
        if ($skipRoutes->isEmpty()) {
            return false;
        }

        // 使用者在 config 中有匹配到目前請求路由，進入檢查 http code 的設定
        if (array_key_exists($routeName, $skipRoutes->toArray())) {
            $dataSet = $skipRoutes->get($routeName)->first();

            // 當 config 中對應該路由的 http code 的設定為空或萬用，所有對應該請求的路由都記錄起來
            if (empty(array_get($dataSet, 'http_code')) || in_array('*', array_get($dataSet, 'http_code'))) {
                return true;
            }

            if (in_array($responseCode, array_get($dataSet, 'http_code'))) {
                return true;
            }
        }

        return false;
    }
}
