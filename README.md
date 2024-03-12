<h1 align="center">laravel 插件扩展包</h1>

<p align="center">
<a href="https://packagist.org/packages/webguosai/idealright-plugin"><img src="https://poser.pugx.org/webguosai/idealright-plugin/v/stable" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/webguosai/idealright-plugin"><img src="https://poser.pugx.org/webguosai/idealright-plugin/downloads" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/webguosai/idealright-plugin"><img src="https://poser.pugx.org/webguosai/idealright-plugin/v/unstable" alt="Latest Unstable Version"></a>
<a href="https://packagist.org/packages/webguosai/idealright-plugin"><img src="https://poser.pugx.org/webguosai/idealright-plugin/license" alt="License"></a>
</p>


## 运行环境

- php >= 7.2
- laravel >= 7.24

## 安装

首先确保安装好了laravel，并且数据库连接设置正确

```Shell
composer require webguosai/idealright-plugin
```

然后运行下面的命令来发布资源：

```shell
php artisan vendor:publish --provider="Webguosai\IdealRight\Plugin\Providers\ServiceProvider"
```

## License

MIT

