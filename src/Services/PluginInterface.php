<?php

namespace Webguosai\IdealRight\Plugin\Services;

interface PluginInterface
{
    /**
     * 插件安装
     */
    public function install();

    /**
     * 插件卸载
     */
    public function unInstall();

    /**
     * 插件启用
     */
    public function enable();

    /**
     * 插件禁用
     */
    public function disable();

    /**
     * 插件启用后始终会执行的方法
     */
    public function boot();
}
