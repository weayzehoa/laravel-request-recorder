<?php

namespace LittleBookBoy\Request\Recorder;

use Illuminate\Support\ServiceProvider;
use LittleBookBoy\Request\Recorder\Middleware\RequestRecorderMiddleware;
use Illuminate\Routing\Router;

class RequestRecorderServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(Router $router)
    {
        // 設定發佈路徑遷移 migrations
        $this->publisher();

        // 註冊 api 中介層
        if (config('request-recorder.recorder.enabled')) {
            $router->prependMiddlewareToGroup('api', RequestRecorderMiddleware::class);
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * 設定發佈路徑
     */
    private function publisher()
    {
        // only accept publish request in command line
        if ($this->app->runningInConsole())
        {
            // 遷移 migrations
            $this->publishes([
                __DIR__ . '/Migrations/' => database_path('migrations')
            ], 'migrations');

            // 自訂記錄器配置檔
            $this->publishes([
                __DIR__ . '/resources/config/request-recorder.php' => config_path('request-recorder.php')
            ]);
        }
    }
}
