<?php

namespace EzziSystem\EzziPlugin\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \EzziSystem\EzziPlugin\Services\PluginService
 *
 * @method static void install(string $name) 安装插件
 * @method static void enable(string $name) 启用插件
 * @method static void disable(string $name) 禁用插件
 * @method static void unInstall(string $name) 卸载插件
 * @method static array getPluginList() 获取插件列表
 * @method static array runEnablePlugin() 运行已经启用的插件
 * @method static void dropTable(string $table) 删除表
 */
class Plugin extends Facade
{
    /**
     * Get the registered name of the component.
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'Plugin';
    }
}
