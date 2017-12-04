<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RequestRecords extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('request_records', function (Blueprint $table) {

            // === 欄位 ===
            // [PK] 請求識別碼，資料意義為表頭名稱 X-Correlation-ID 的值
            $table->string('uuid', 36)->primary();
            // 接收到的 Header 參數
            $table->string('method', '8');
            // 請求所走的 route
            $table->string('route', 191);
            // 接收到的 Route 參數
            $table->text('route_params')->nullable();
            // 接收到的 Header
            $table->text('request_headers')->nullable();
            // 接收到的 Request 參數
            $table->text('request_params');
            // 對應請求所回應的 Header
            $table->text('response_headers')->nullable();
            // 對應請求所回應的內容
            $table->text('response_contents')->nullable();
            // 請求處理狀態：若為真表示請求已處理完成，不為真表示未完成
            // $table->boolean('job_status')->default(false);
            // 請求來源 ip
            $table->string('ip', 64);
            // 接收到 Request 的時間點
            $table->dateTime('created_at');
            // 主要用來識別寫入 Response 回應的時間
            $table->dateTime('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('request_records');
    }
}
