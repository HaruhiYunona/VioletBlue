# VioletBlue 1.5

![Icon](./public/install/icon.webp)

![GitHub all releases](https://img.shields.io/github/downloads/HaruhiYunona/VioletBlue/total)
![GitHub release (with filter)](https://img.shields.io/github/v/release/HaruhiYunona/VioletBlue)
![GitHub (Pre-)Release Date](https://img.shields.io/github/release-date-pre/HaruhiYunona/VioletBlue)
![GitHub](https://img.shields.io/github/license/HaruhiYunona/VioletBlue)
![GitHub repo size](https://img.shields.io/github/repo-size/HaruhiYunona/VioletBlue)
![Static Badge](https://img.shields.io/badge/PHP-8.4.1+-purple?logo=PHP)
![Static Badge](https://img.shields.io/badge/Tailwindcss-4.3.3+-blue?logo=Tailwindcss)
![Static Badge](https://img.shields.io/badge/NodeJS-22.20.0+-blue?logo=Node.js)
![Static Badge](https://img.shields.io/badge/Vue-3.5.44+-green?logo=vue.js)
![Static Badge](https://img.shields.io/badge/composer-2.8.6+-purple?logo=composer)
![Static Badge](https://img.shields.io/badge/docker-29.9.2+-blue?logo=docker)

**♡ ReadMe Language ♡**

[<img alt="Static Badge" src="https://img.shields.io/badge/LANG-简体中文-red">](./README.MD) [<img alt="Static Badge" src="https://img.shields.io/badge/LANG-繁體中文-azure">](./README_Zh.MD) [<img alt="Static Badge" src="https://img.shields.io/badge/LANG-English-blue">](./README_En.MD) [<img alt="Static Badge" src="https://img.shields.io/badge/LANG-日本語-white?labelColor=red">](./README_Ja.MD)

# A few words

Three years later, I've finally got around to an update. If the PHP vulnerability hadn't been so serious, I probably wouldn't have paid much attention. This release overhauls the entire stack. There's enough here that I'm calling it 1.5.0.

# Requirements

- PHP: **8.4.1+** (versions below 8.4 have high-severity vulnerabilities and are no longer supported)
  - PHP extension: pdo
  - PHP extension: pdo_mysql
  - PHP extension: mbstring
  - PHP extension: fileinfo
  - PHP extension: curl
  - PHP extension: yaml
- MySQL: **8.0.0+**
- Composer: **2.0.0+**
- Node.js: **22.12.0+**
- Yarn: **1.22.0+**

# Installation

## ♡ Docker (recommended) ♡

There's not much to it. Download `docker_<version>.zip` from [Releases](https://github.com/HaruhiYunona/VioletBlue/releases) and extract it. Fill in the database settings in `config.yaml`, load the image, and start it.

The image doesn't include MySQL. You'll need to set it up on the host or add it to the image yourself.

Open `http://<server-ip>:8080`. Installation runs automatically on the first start. The admin panel is at `/admin`, and the default password is `123456`. Test scripts are also ready to use, though you may need to tweak them for your setup.

## ♡ Hosting control panel / installation package ♡

1. Download `install_<version>.zip` from [Releases](https://github.com/HaruhiYunona/VioletBlue/releases) to your server's `wwwroot` directory (the PHP website directory), or download it locally and upload it to the server.
2. Once the site is configured, extract the package into its root directory. This version no longer requires manual URL rewrite rules or running Composer.
3. Open your website to reach the installer, then fill in the database details as prompted.

## ♡ From source ♡

### 1. Customize the frontend (optional)

If you'd like to change the interface or add features, edit the Vue project in `./frontend`.

### 2. Build from the command line

Run:

```shell
composer install --no-dev --prefer-dist
# Build the frontend #
yarn --cwd frontend install --frozen-lockfile
yarn --cwd frontend build
```

### 3. Connect the database

Create an empty database and point your web server's document root to `public`. Open the site and enter the database connection details, table prefix, and admin password.

# Updates

The project has a built-in online updater. Patch packages may also be released when needed, usually named `patch_<version>.zip`. These are not complete installation packages, so you usually won't need to download them separately. If the online update fails and you need to update manually, upload the patch to your website's root directory and extract it there. Back up your website and data before any update.

# Migrating from an older version

If you're upgrading from version 1.1 or earlier, follow these steps:

1. Create a new database, or use a different table prefix during installation.
2. Once installation is complete, manually delete the tables it just created.
3. Rename the old tables to match the new ones and move them into the database where the new tables were. If they're already in the same database, there's no need to move them.
4. Download `script_migrate.php` from [Releases](https://github.com/HaruhiYunona/VioletBlue/releases) and upload it to the website's root directory.
5. Run:

   ```shell
   php migrate.php
   ```

   If you get an error, adjust the path on line 3 to match your server setup. You'll need to work out the right path yourself, or ask an AI.

6. If the script reports that it found the old database rather than showing an error, you're nearly there. Run the following command to finish the migration:

   ```shell
   php migrate.php --apply
   ```

   That's it—your data has been migrated.

# What's new

1. Fixed lots of bugs. Nearly all of the old interface issues are gone.
2. Added file uploads for bulk song imports.
3. Added error checking for bulk imports.
4. Added separate theme customization for mobile and desktop.
5. Added online update checks.
6. Added a button to clear the song list.
7. Moved to a more modern stack.

# Contact

Project discussion group (QQ): 606917884

Author on GitHub: [HaruhiYunona](https://github.com/HaruhiYunona)

Author on X: [@Neko_Kuyo](https://x.com/Neko_Kuyo)
