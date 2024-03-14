<?php

namespace EzziSystem\EzziPlugin\Services;

use Illuminate\Support\Arr;
use EzziSystem\EzziPlugin\Exceptions\PluginNotFoundException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Exception;

class PluginService
{
    const STATUS_INSTALL   = 'install';
    const STATUS_UNINSTALL = 'unInstall';
    const STATUS_ENABLE    = 'enable';
    const STATUS_DISABLE   = 'disable';
    const STATUS_BOOT      = 'boot';

    /**
     * @var string 插件目录
     */
    protected $pluginDir;

    protected $pluginCacheFile;

    /**
     * @param string $pluginDir 插件目录(目录后面不带/)
     */
    public function __construct(string $pluginDir, string $pluginCacheFile = null)
    {
        $this->pluginDir = $pluginDir . DIRECTORY_SEPARATOR;
        // 插件缓存文件 (默认为插件目录下的plugin.json)
        if ($pluginCacheFile == null) {
            $pluginCacheFile = $this->pluginDir . 'plugin.json';
        }
        $this->pluginCacheFile = $pluginCacheFile;
    }

    // 安装插件
    public function install(string $name)
    {
        // TODO 这里需要市场支持,先不做

    }

    /**
     * 启用插件
     * @param string $name
     * @return void
     * @throws Exception
     */
    public function enable(string $name)
    {
        $this->pluginExists($name);

        // 导入sql
        $this->importSql($name);

        $this->runPlugin($name, self::STATUS_ENABLE);

        // 注册当前插件的provider
        $this->registerPluginProvider($name);
        // 执行插件的发布
        $this->runPluginPublish($name);

        $this->updatePluginCacheFile($name, self::STATUS_ENABLE);
    }

    /**
     * 禁用插件
     * @param string $name
     * @return void
     * @throws Exception
     */
    public function disable(string $name)
    {
        $this->pluginExists($name);

        $this->runPlugin($name, self::STATUS_DISABLE);

        $this->updatePluginCacheFile($name, self::STATUS_DISABLE);
    }

    /**
     * 卸载插件
     * @param string $name
     * @return void
     * @throws Exception
     */
    public function unInstall(string $name)
    {
        $this->pluginExists($name);

        $this->runPlugin($name, self::STATUS_UNINSTALL);

        $this->updatePluginCacheFile($name, self::STATUS_UNINSTALL);

        // 删除插件目录
        self::rmDir($this->getPluginDir($name));
    }

    /**
     * 获取插件列表
     * @return array
     */
    public function getPluginList(): array
    {
        $files = scandir($this->pluginDir);
        $list  = [];
        foreach ($files as $name) {
            if ($name === '.' or $name === '..') {
                continue;
            }

            if (is_dir($this->pluginDir . $name)) {
                $info           = $this->getPluginInfo($name);
                $info['name']   = $name;
                $info['status'] = $this->getPluginStatus($name);
                $list[$name]    = $info;
            }
        }
        return $list;
    }

    /**
     * 运行已经启用的插件 (这里会包含注册插件的provider和执行插件中的boot方法)
     * @return void
     */
    public function runEnablePlugin()
    {
        $plugins = $this->getPluginList();
        foreach ($plugins as $plugin) {
            // 状态为已启用的
            if ($plugin['status'] == self::STATUS_ENABLE) {
                // 加载插件的autoload
                $this->loadPluginAutoload($plugin['name']);

                // 注册插件的 provider
                $this->registerPluginProvider($plugin['name']);

                // 执行插件的启动方法
                try {
                    $this->runPlugin($plugin['name'], self::STATUS_BOOT);
                } catch (Exception $e) {

                }

            }
        }
    }

    /**
     * 删除表
     * @param string $table 表名(不需要表前缀)
     * @return void
     */
    public static function dropTable(string $table)
    {
        Schema::dropIfExists($table);
    }

    /**
     * 执行插件中的发布操作
     * @param string $name
     * @return void
     */
    protected function runPluginPublish(string $name)
    {
        Artisan::call('vendor:publish', [
            '--provider' => $this->getProviderClass($name),
            '--force'    => true
        ]);
    }

    /**
     * 注册指定插件provider
     * @param string $name
     * @return void
     */
    protected function registerPluginProvider(string $name)
    {
        $provider = $this->getProviderClass($name);
        if ($provider) {
            App::register($provider);
        }
    }

    /**
     * 删除目录文件(含子目录)
     * @param string $dir
     * @return bool
     */
    protected static function rmDir(string $dir): bool
    {
        //不是目录
        if (!is_dir($dir)) {
            return true;
        }

        //目录不可写
        if (!is_writable($dir)) {
            return false;
        }

        $dirHandle = opendir($dir);
        while ($file = readdir($dirHandle)) {
            if ($file != '.' && $file != '..') {
                $childrenPath = $dir . '/' . $file;
                if (!is_dir($childrenPath)) {
                    @unlink($childrenPath);
                } else {
                    self::rmDir($childrenPath);
                    @rmdir($childrenPath);
                }
            }
        }

        //关闭目录句柄
        closedir($dirHandle);

        // 删除指定的目录
        @rmdir($dir);

        return true;
    }

    /**
     * 获取插件的 provider class
     * @param string $name
     * @return string|null
     */
    protected function getProviderClass(string $name): ?string
    {
        $class = 'EzziSystem\EzziPlugin\Plugins\\' . $name . '\Providers\\' . $name . 'ServiceProvider';
        if (!class_exists($class)) {
            $class = null;
        }
        return $class;
    }

    /**
     * 导入sql
     * @param string $name
     * @return void
     */
    protected function importSql(string $name)
    {
        $file = $this->getPluginDir($name) . 'install.sql';

        // 存在install.sql文件就导入
        if (file_exists($file)) {
            $sql = file_get_contents($file);
            $sql = str_replace('__PREFIX__', DB::getTablePrefix(), $sql);

            DB::unprepared($sql);
        }
    }

    /**
     * 获取插件信息
     * @param string $name
     * @return array
     */
    protected function getPluginInfo(string $name): array
    {
        $file = $this->getPluginDir($name) . 'info.ini';
        $info = parse_ini_file($file, true);
        return Arr::only($info, [
            'title', 'desc', 'author', 'website', 'version', 'license'
        ]);
    }

    /**
     * 获取插件缓存文件的数据
     * @return array|mixed
     */
    protected function getCacheFileData()
    {
        $data = [];
        if (file_exists($this->pluginCacheFile)) {
            $data = json_decode(file_get_contents($this->pluginCacheFile), true);
        } else {
            // 创建文件
            $this->writeCacheFileData();
        }

        return $data;
    }

    /**
     * 写入到插件缓存文件
     * @param mixed $data
     * @return void
     */
    protected function writeCacheFileData($data = '')
    {
        if (empty($data)) {
            $data = '{}';
        } elseif (is_array($data)) {
            $data = json_encode($data);
        }

        file_put_contents($this->pluginCacheFile, $data);
    }

    /**
     * 获取插件状态
     * @param string $name
     * @return mixed|null
     */
    protected function getPluginStatus(string $name)
    {
        $data = $this->getCacheFileData();

        $status = null;
        if (isset($data[$name])) {
            $status = $data[$name];
        }

        return $status;
    }

    /**
     * 更新到插件缓存文件
     * @param string $name
     * @param string $status
     * @return void
     */
    protected function updatePluginCacheFile(string $name, string $status)
    {
        $data = $this->getCacheFileData();

        if (self::STATUS_UNINSTALL === $status) {
            // 如果是卸载，则从缓存文件中移除
            unset($data[$name]);
        } else {
            $data[$name] = $status;
        }

        $this->writeCacheFileData($data);
    }

    /**
     * 获取某个插件的目录
     * @param string $name
     * @return string 返回的目录后面会带/
     */
    protected function getPluginDir(string $name): string
    {
        return $this->pluginDir . $name . DIRECTORY_SEPARATOR;
    }

    /**
     * 加载插件的autoload
     * @param string $name
     * @return void
     */
    protected function loadPluginAutoload(string $name)
    {
        $path = $this->getPluginDir($name) . 'vendor/autoload.php';
        if (file_exists($path)) {
            require_once $path;
        }
    }

    /**
     * 插件是否存在
     * @param string $name
     * @return bool
     * @throws Exception
     */
    protected function pluginExists(string $name): bool
    {
        if (is_dir($this->getPluginDir($name))) {
            return true;
        } else {
            throw new PluginNotFoundException("【{$name}】不存在！");
        }
    }

    /**
     * 运行插件中的方法
     * @param string $name 插件名
     * @param string $method 插件内的方法名
     * @return bool
     * @throws Exception
     */
    protected function runPlugin(string $name, string $method): bool
    {
        $className = 'EzziSystem\EzziPlugin\Plugins\\' . $name . '\\' . $name;
        if (class_exists($className)) {
            $class = new $className();
            if (method_exists($class, $method)) {
                try {
                    call_user_func([$class, $method]);
                    return true;
                } catch (Exception $e) {
                    throw new Exception($e->getMessage());
                }
            }
        }

        throw new PluginNotFoundException('找不到插件');
    }

}
