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

    /**
     * [yesterdayPeriod 获取昨天时间段]
     * @return [array] [返回mysql需要的数组]
     */
    public function yesterdayPeriod()
    {
        $s = Carbon::yesterday()->startOfDay()->timestamp;
        $e = Carbon::yesterday()->endOfDay()->timestamp;
        return [$s,$e];
    }

    /**
     * [weekPeriod 获取本周时间段]
     * @return [array] [返回mysql需要的数组]
     */
    public function weekPeriod()
    {
        $s = Carbon::now()->startOfWeek()->timestamp;
        $e = Carbon::now()->endOfWeek()->timestamp;
        return [$s,$e];
    }

    /**
     * [monthPeriod 获取本月时间段]
     * @return [array] [返回mysql需要的数组]
     */
    public function monthPeriod()
    {
        $s = Carbon::now()->startOfMonth()->timestamp;
        $e = Carbon::now()->endOfMonth()->timestamp;
        return [$s,$e];
    }

    /**
     * [dayPeriod 某日的时间段]
     * @param  [string] $date [字符串时间]
     * @return [array]       [返回mysql需要的数组]
     */
    public function dayPeriod($date)
    {
        $day = Carbon::createFromFormat('Y-m-d', $date);
        $s = $day->startOfDay()->timestamp;
        $e = $day->endOfDay()->timestamp;
        return [$s,$e];
    }
}
