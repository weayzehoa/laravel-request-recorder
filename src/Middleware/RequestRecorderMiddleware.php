<?php

namespace LittleBookBoy\Request\Recorder\Middleware;

use Closure;
use Illuminate\Http\Request;
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

        // 檢查是否要記錄此請求
        if ($this->isExceptMethod($request->getMethod())) {
            return $next($request);
        }

        // 加上回應表頭
        $response = $next($request);
        $response->headers->add([self::X_CORRELATION_ID => $this->requestId]);

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
        $record->request_header = collect($request->headers)->toJson();
        // 收到的請求原始內容
        $record->request_params = collect($request->toArray())->toJson();
        // 對應請求所回應的 Header
        $record->response_headers = collect($response->headers)->toJson();
        // 對應請求所回應的內容
        $record->response_contents = $response->getContent();
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
     * 檢查是否不要記錄此請求
     */
    protected function isExceptMethod($method)
    {
        return in_array(strtoupper($method), config('request-recorder.recorder.except'));
    }
}
