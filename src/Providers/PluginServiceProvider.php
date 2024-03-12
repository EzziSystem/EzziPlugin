<?php

namespace EzziSystem\EzziPlugin\Providers;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use EzziSystem\EzziPlugin\Services\PluginService;

class PluginServiceProvider extends LaravelServiceProvider
{
    protected $root = __DIR__ . '/../../';

    public function boot()
    {
        // 配置文件
        $this->publishes([
            $this->root . 'config/plugin.php' => config_path('plugin.php'),
        ], 'config');

    }

    public function register()
    {
        // 合并配置文件
        $this->mergeConfigFrom($this->root . 'config/plugin.php', 'plugin');

        $config  = config('plugin');
        $service = new PluginService($config['pluginDir'], $config['pluginCacheFile']);
        // 运行已经启用的插件
        $service->runEnablePlugin();
        // 单例模式
        $this->app->instance('Plugin', $service);
    }
}
