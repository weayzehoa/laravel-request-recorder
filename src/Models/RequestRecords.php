<?php

namespace LittleBookBoy\Request\Recorder\Models;

use Illuminate\Database\Eloquent\Model;

class RequestRecords extends Model
{
    /**
     * 日期時間欄位格式
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * 讀出欄位轉 carbon 型態
     *
     * @var string
     */
    protected $dates = [
        'created_at',
        'updated_at',
    ];
}
