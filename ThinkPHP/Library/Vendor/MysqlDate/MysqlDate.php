<?php

namespace MysqlDate;

vendor("Carbon.Carbon");
use Carbon\Carbon;

class MysqlDate
{
    /**
     * [todadyPeriod 获取今天时间段]
     * @return [array] [返回mysql需要的数组]
     */
    public function todadyPeriod()
    {
        $s = Carbon::now()->startOfDay()->timestamp;
        $e = Carbon::now()->endOfDay()->timestamp;
        return [$s,$e];
    }
}
