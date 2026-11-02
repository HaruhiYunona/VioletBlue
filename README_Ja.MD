# VioletBlue 1.5

![Icon](./public/install/icon.webp)

![GitHub all releases](https://img.shields.io/github/downloads/HaruhiYunona/VioletBlue/total)![GitHub release (with filter)](https://img.shields.io/github/v/release/HaruhiYunona/VioletBlue)![GitHub (Pre-)Release Date](https://img.shields.io/github/release-date-pre/HaruhiYunona/VioletBlue)![GitHub](https://img.shields.io/github/license/HaruhiYunona/VioletBlue)![GitHub repo size](https://img.shields.io/github/repo-size/HaruhiYunona/VioletBlue)![Static Badge](https://img.shields.io/badge/PHP-8.4.1+-purple?logo=PHP)![Static Badge](https://img.shields.io/badge/Tailwindcss-4.3.3+-blue?logo=Tailwindcss)![Static Badge](https://img.shields.io/badge/NodeJS-22.20.0+-blue?logo=Node.js)![Static Badge](https://img.shields.io/badge/Vue-3.5.44+-green?logo=vue.js)![Static Badge](https://img.shields.io/badge/composer-2.8.6+-purple?logo=composer)![Static Badge](https://img.shields.io/badge/docker-29.9.2+-blue?logo=docker)

**♡ ReadMe Language ♡**

[<img alt="Static Badge" src="https://img.shields.io/badge/LANG-简体中文-red">](./README.MD) [<img alt="Static Badge" src="https://img.shields.io/badge/LANG-繁體中文-azure">](./README_Zh.MD) [<img alt="Static Badge" src="https://img.shields.io/badge/LANG-English-blue">](./README_En.MD) [<img alt="Static Badge" src="https://img.shields.io/badge/LANG-日本語-white?labelColor=red">](./README_Ja.MD)

# はじめに

3年ぶりに、ようやく重い腰を上げて更新しました。PHP の脆弱性がここまで深刻でなければ、たぶんそれほど気にしていなかったと思います。今回は使っている技術を全面的に入れ替えました。変更点も多いので、とりあえず 1.5.0 ということにします。

# 動作環境

- PHP：**8.4.1+**（8.4 未満には深刻な脆弱性があるため、サポート対象外）
  - PHP 拡張：pdo
  - PHP 拡張：pdo_mysql
  - PHP 拡張：mbstring
  - PHP 拡張：fileinfo
  - PHP 拡張：curl
  - PHP 拡張：yaml
- MySQL：**8.0.0+**
- Composer：**2.0.0+**
- Node.js：**22.12.0+**
- Yarn：**1.22.0+**

# インストール

## ♡ Docker を使う場合（おすすめ）♡

こちらは特に説明することもなさそうです。[Releases](https://github.com/HaruhiYunona/VioletBlue/releases) から `docker_バージョン番号.zip` をダウンロードして展開します。展開先の `config.yaml` にデータベースの接続情報を記入し、イメージを読み込んで起動すれば完了です。

このイメージには MySQL は含まれていません。ホスト側で用意するか、自分でイメージに追加してください。

`http://サーバーIP:8080` にアクセスしてください。初回起動時に自動でインストールされます。管理画面は `/admin`、初期パスワードは `123456` です。各種テストスクリプトも用意してありますが、環境に合わせてコードの調整が必要になる場合があります。

## ♡ サーバー管理パネル／インストールパッケージを使う場合 ♡

1. [Releases](https://github.com/HaruhiYunona/VioletBlue/releases) から `install_バージョン番号.zip` をサーバーの `wwwroot`（PHP サイトのディレクトリ）にダウンロードします。手元の PC にダウンロードしてからアップロードしても構いません。
2. サイトの設定を済ませたら、サイトのルートディレクトリでパッケージを展開します。このバージョンでは URL のリライトルールの手動設定や Composer の実行は不要です。
3. サイトにアクセスするとインストール画面が表示されます。案内に従ってデータベースの情報を入力してください。

## ♡ ソースコードから導入する場合 ♡

### 1. フロントエンドのカスタマイズ（任意）

見た目を変えたり機能を追加したりしたい場合は、`./frontend` にある Vue プロジェクトを編集してください。

### 2. シェルでビルド

以下のコマンドを実行します。

```shell
composer install --no-dev --prefer-dist
# フロントエンドのビルド #
yarn --cwd frontend install --frozen-lockfile
yarn --cwd frontend build
```

### 3. データベースへの接続

空のデータベースを作成し、Web サーバーのドキュメントルートを `public` に設定します。サイトにアクセスして、データベースの接続情報、テーブルのプレフィックス、管理者パスワードを入力してください。

# アップデート

このプロジェクトにはオンライン更新機能が組み込まれています。必要に応じてパッチも配布します。ファイル名は通常 `patch_バージョン番号.zip` です。新規インストール用の一式は含まれていないので、普段は別途ダウンロードする必要はありません。オンライン更新に問題があり、手動で上書きする場合は、パッチをサイトのルートディレクトリにアップロードして展開してください。どの方法で更新する場合も、先にサイトとデータをバックアップしておきましょう。

# 旧バージョンからの移行

1.1 以前のバージョンから更新する場合は、以下の手順で移行できます。

1. 新しいデータベースを作成するか、インストール時に別のテーブルプレフィックスを指定します。
2. インストールが完了したら、新しく作成されたテーブルを手動で削除します。
3. 古いテーブルを新しいテーブルと同じ名前に変更し、新しいテーブルがあったデータベースへ移動します。同じデータベース内であれば、移動は不要です。
4. [Releases](https://github.com/HaruhiYunona/VioletBlue/releases) から `script_migrate.php` をダウンロードし、サイトのルートディレクトリにアップロードします。
5. 以下のコマンドを実行します。

   ```shell
   php migrate.php
   ```

   エラーが出た場合は、3行目のパスを修正してください。どう直すかは実際の環境に合わせて判断してください。AI に聞いてみてもいいと思います。

6. エラーではなく、古いデータベースの情報を検出したという表示が出たら、あと少しです。以下のコマンドで移行を完了します。

   ```shell
   php migrate.php --apply
   ```

   これでデータの移行は完了です。

# 今回の主な変更点

1. 多くのバグを修正しました。旧画面の不具合はほぼ解消しています。
2. 曲の一括登録で、ファイルを直接アップロードできるようになりました。
3. 一括登録時のエラーチェックを追加しました。
4. スマートフォンと PC で、それぞれテーマをカスタマイズできるようになりました。
5. オンラインで更新を確認できるようになりました。
6. 曲リストを空にするボタンを追加しました。
7. より新しい技術へ移行しました。

# 連絡先

プロジェクトの QQ グループ：606917884

作者の GitHub：[HaruhiYunona](https://github.com/HaruhiYunona)

作者の X：[@Neko_Kuyo](https://x.com/Neko_Kuyo)
