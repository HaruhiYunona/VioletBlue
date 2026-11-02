# VioletBlue 1.5

![Icon](./public/install/icon.webp)

![GitHub all releases](https://img.shields.io/github/downloads/HaruhiYunona/VioletBlue/total)![GitHub release (with filter)](https://img.shields.io/github/v/release/HaruhiYunona/VioletBlue)![GitHub (Pre-)Release Date](https://img.shields.io/github/release-date-pre/HaruhiYunona/VioletBlue)![GitHub](https://img.shields.io/github/license/HaruhiYunona/VioletBlue)![GitHub repo size](https://img.shields.io/github/repo-size/HaruhiYunona/VioletBlue)![Static Badge](https://img.shields.io/badge/PHP-8.4.1+-purple?logo=PHP)![Static Badge](https://img.shields.io/badge/Tailwindcss-4.3.3+-blue?logo=Tailwindcss)![Static Badge](https://img.shields.io/badge/NodeJS-22.20.0+-blue?logo=Node.js)![Static Badge](https://img.shields.io/badge/Vue-3.5.44+-green?logo=vue.js)![Static Badge](https://img.shields.io/badge/composer-2.8.6+-purple?logo=composer)![Static Badge](https://img.shields.io/badge/docker-29.9.2+-blue?logo=docker)



**♡ ReadMe Language ♡**

[<img alt="Static Badge" src="https://img.shields.io/badge/LANG-简体中文-red">](./README.MD) [<img alt="Static Badge" src="https://img.shields.io/badge/LANG-繁體中文-azure">](./README_Zh.MD) [<img alt="Static Badge" src="https://img.shields.io/badge/LANG-English-blue">](./README_En.MD) [<img alt="Static Badge" src="https://img.shields.io/badge/LANG-日本語-white?labelColor=red">](./README_Ja.MD)



# 前言

时隔三年终于舍得更新了。这次要不是PHP漏洞太大我也不会这么关注这件事。技术栈进行了一次完全的升级，因为升级内容较多我姑且叫其1.5.0吧。



# 环境需求

- PHP版本： **8.4.1+**（低于8.4的包含高危漏洞，不再支持）
  - PHP 扩展：pdo
  - PHP扩展：pdo_mysql
  - PHP扩展：mbstring
  - PHP扩展：fileinfo
  - PHP扩展：curl
  - PHP扩展：yaml
- MySQL版本： **8.0.0+**
- Composer版本： **2.0.0+**
- Node.js版本： **22.12.0+**
- Yarn版本： **1.22.0+**



# 安装

## ♡ 对于Docker (推荐)♡

​	这个似乎没什么好讲的，在 [Releases](https://github.com/HaruhiYunona/VioletBlue/releases) 下载并解压 `docker_你所要的版本.zip`。编辑解压目录下  `config.yaml` 数据库配置，导入镜像，直接启动即可。

​	此镜像中不包含Mysql环境，需要手动在宿主机配置或在镜像内构建Mysql环境。

​	访问 `http://服务器IP:8080`，首次启动自动安装。后台地址为 `/admin`，默认密码 `123456`。各类测试脚本也已就绪（可能需要手动微调代码）



## ♡ 对于面板/安装包♡

1. 在 [Releases](https://github.com/HaruhiYunona/VioletBlue/releases) 下载或下载后上传 `install_你所需要的版本.zip` 到你的服务器`wwwroot`(PHP网站目录)
2. 设置好网站后,在你的对应网站根目录下解压安装包。此版本已经不再需要手动设置伪静态和composer。
3. 直接访问你的网站，将看到安装页面。按要求填写数据库信息。



## ♡ 对于源码♡

### 1.修改定制前端（非必要）

如果你想个性化加功能可以自行修改Vue项目，位于 `./frontend`。



### 2.Shell指令构建

代码如下

```shell
composer install --no-dev --prefer-dist
# 下面是前端构建 #
yarn --cwd frontend install --frozen-lockfile
yarn --cwd frontend build
```



### 3.链接数据库

创建或一个空数据库，将 Web 服务器根目录指向 `public`，访问站点并填写数据库连接、表前缀和管理员密码。



# 更新

该项目已经内置联网更新功能。不过特别情况会发布补丁包更新。补丁包格式一般是patch_对应版本.zip，其中不包含完整程序，所以一般不要下载。除非联网更新遇到问题，需要手动覆盖，则将其上传到网站根目录，解压即可。任何更新都应做好数据和网站备份。



# 旧程序迁移

若你是从1.1版本甚至更早版本更新，你可以按以下办法更新：

1. 创建一个新的数据库，或修改安装时要填写的数据表前缀。

2. 程序安装完成后，手动将数据库中新生成的数据表删除。

3. 将旧表改名与新表一致，并放到新表位置（若位于同一个库就不用挪表了）

4. 下载 [Releases](https://github.com/HaruhiYunona/VioletBlue/releases) 中的 `script_migrate.php`，并上传至网站根目录。

5. 按以下代码执行

   ```shell
   php migrate.php
   ```

   如果报错还请修改第3行路径，具体怎么修改自行判断（也可以问AI）

6. 如果没有报错，而是提示检测到老数据库信息，恭喜，按以下代码执行就完成迁移了：

   ``` shell
   php migrate.php --apply
   ```

   至此数据迁移结束。



# 新版本特性

1. 修复了诸多BUG. 旧界面BUG几乎消失

2. 新增批量上传直接使用文件的功能

3. 新增批量上传排错

4. 新增手机端PC端主题自定义

5. 新增联网检查更新

6. 新增清空歌单数据按钮

7. 改用更先进的技术

# 联系方式
本项目讨论群（QQ）：606917884
作者的Github: [https://github.com/HaruhiYunona](https://github.com/HaruhiYunona)
作者的X: [https://x.com/@Neko_Kuyo](https://x.com/@Neko_Kuyo)
   
