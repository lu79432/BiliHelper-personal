<?php

/**
 *  Website: https://mudew.com/
 *  Author: Lkeme
 *  License: The MIT License
 *  Email: Useri@live.cn
 *  Updated: 2021 ~ 2022
 */

namespace BiliHelper\Plugin;

use BiliHelper\Core\Log;
use BiliHelper\Util\TimeLock;


class Schedule
{
    use TimeLock;

    // TODO 黑白名单|考虑添加到每个插件内部自动添加|优化RUN逻辑代码
    private static $unlock_hour = 24;
    private static $unlock_timers = [];
    private static $sleep_section = [];
    // 日常类
    private static $fillable = ['Login', 'Schedule', 'Daily', 'Judge', 'MainSite', 'GiftSend', 'DailyTask', 'Silver2Coin', 'ManGa', 'GameMatch', 'GroupSignIn', 'AwardRecord', 'Statistics'];
    // 任务类
    private static $guarded_first = ['Barrage', 'GiftHeart', 'Silver', 'MaterialObject'];
    // 监控类
    private static $guarded_second = ['AloneTcpClient', 'ZoneTcpClient',];
    // 抽奖类
    private static $guarded_third = ['StormRaffle', 'GuardRaffle', 'PkRaffle', 'GiftRaffle', 'AnchorRaffle', 'Forward'];
    // 特殊 老爷处理
    private static $guarded_fourth = ['Heart'];
    // 暂定不做处理，后期看情况再定
    private static $release = ['ActivityLottery', 'SmallHeart', 'Competition', 'SmallHeart', 'Forward', 'CapsuleLottery'];

    public static function run()
    {
        if (self::getLock() > time()) {
            return;
        }
        self::isSleep();
        self::isSpecialPause();
        self::setLock(1 * 60);
    }

    /**
     * @use 检查休眠
     */
    private static function isSleep()
    {
        if (getenv('USE_SLEEP') != 'false' && self::$unlock_hour != date('H')) {
            self::$sleep_section = empty(self::$sleep_section) ? explode(',', getenv('SLEEP_SECTION')) : self::$sleep_section;
            if (!in_array(date('H'), self::$sleep_section)) {
                return false;
            };
            self::handleBan('sleep');
        };
        return true;
    }


    /**
     * @use 特殊暂停
     */
    private static function isSpecialPause()
    {
        foreach (self::$guarded_second as $classname) {
            $status = call_user_func(array(__NAMESPACE__ . '\\' . $classname, 'getPauseStatus'));
            if ($status) {
                return true;
            }
        }
        foreach (self::$guarded_third as $classname) {
            $status = call_user_func(array(__NAMESPACE__ . '\\' . $classname, 'getPauseStatus'));
            if (!$status) {
                return false;
            }
        }
        self::handleBan('special');
        return true;
    }


    /**
     * @use 处理禁令
     * @param $action
     * @param string $classname
     */
    private static function handleBan($action, $classname = '')
    {
        switch ($action) {
            // 休眠
            case 'sleep':
                foreach (self::$fillable as $classname) {
                    Log::info("插件 {$classname} 白名单，保持当前状态继续");
                }
                $unlock_time = 60 * 60;
                self::$unlock_hour = date('H');
                $classname_list = array_merge(self::$guarded_first, self::$guarded_second, self::$guarded_third);
                if (!User::isMaster()) {
                    $classname_list = array_merge($classname_list, self::$guarded_fourth);
                }
                self::stopProc($classname_list, $unlock_time, true);
                Log::warning('进入自定义休眠时间范围，暂停非必要任务，自动开启！');
                break;
            // 暂停访问
            case 'pause':
                // 访问拒绝 统一时间 第二天0点
                $unlock_time = strtotime(date("Y-m-d", strtotime("+1 day", time()))) - time();
                self::stopProc([$classname], $unlock_time);
                Log::warning("{$classname} 任务拒绝访问，暂停任务，自动开启！");
                // 推送被ban信息
                $time = floor($unlock_time / 60 / 60);
                Notice::push('banned', "任务 {$classname} 暂停，{$time} 小时后自动恢复！");
                break;
            // 特殊类
            case 'special':
                // 访问拒绝 统一时间 第二天0点
                $unlock_time = strtotime(date("Y-m-d", strtotime("+1 day", time()))) - time();
                self::stopProc(self::$guarded_second, $unlock_time);
                Log::warning("所有抽奖任务拒绝访问，暂停监控任务，自动开启！");
                break;
            default:
                break;
        }
    }


    /**
     * @use 停止运行
     * @param array $classname_list
     * @param int $unlock_time
     * @param bool $force
     */
    private static function stopProc(array $classname_list, int $unlock_time, bool $force = false)
    {
        foreach ($classname_list as $classname) {
            Log::info("插件 {$classname} 黑名单，锁定状态将于" . date("Y-m-d H:i", time() + $unlock_time) . "解除");
            // 强制 无视小黑屋设定
            if ($force) {
                call_user_func(array(__NAMESPACE__ . '\\' . $classname, 'setPauseStatus'), false);
            }
            call_user_func(array(__NAMESPACE__ . '\\' . $classname, 'setLock'), $unlock_time + 3 * 60);
            call_user_func(array(__NAMESPACE__ . '\\' . $classname, 'setPauseStatus'), true);
        }
    }

    /**
     * @use 触发封禁
     * @param string $classname
     */
    public static function triggerRefused(string $classname)
    {
        self::handleBan('pause', $classname);
    }
}