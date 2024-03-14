<?php

namespace EzziSystem\EzziPlugin\Services;

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
}
