<?php
declare(strict_types=1);
namespace VioletBlue\controller;

use VioletBlue\service\{service_auth, service_playlist, service_options, service_upload, service_remote, service_theme, service_import, service_release, service_update, service_migration};
use VioletBlue\support\support_input;

/** 将路由操作映射到歌曲、设置、认证和上传服务。 */
final class controller_api
{
    /**
     * 调用操作对应的业务服务。
     * @param string $action 路由已验证的操作名
     * @param array $input 请求参数映射
     * @return mixed 业务结果
     */
    public function dispatch(string $action, array $input): mixed
    {
        $playlist = new service_playlist();
        $options = new service_options();
        return match ($action) {
            'getTagList' => $playlist->tags(),
            'getSongList' => $playlist->list(support_input::text($input, 'tag', '全部'), support_input::integer($input['page'] ?? 1, 1, 10000000)),
            'getNotice' => $options->notice(),
            'search' => $playlist->search(support_input::text($input, 'search')),
            'random' => $playlist->random(),
            'detail' => $playlist->detail(support_input::integer($input['uid'] ?? null)),
            'vUpInfo' => (new service_remote())->profile(),
            'siteInfo' => $options->info(),
            'themeInfo' => (new service_theme())->info(),
            'changeTheme' => (new service_theme())->change(support_input::array($input, 'colors')),
            'sessionInfo' => ['authenticated' => service_auth::loggedIn(), 'csrf' => $_SESSION['csrf'] ?? ''],
            'logout' => service_auth::logout(),
            'passport' => service_auth::login(support_input::text($input, 'passWd')),
            'submitSong' => $playlist->add($input),
            'submitSongs' => $playlist->batch(support_input::array($input, 'list')),
            'delete' => $playlist->delete(support_input::integer($input['uid'] ?? null)),
            'clearSongList' => $playlist->clear(support_input::text($input, 'confirmation')),
            'startImport' => (new service_import())->start(support_input::array($input, 'list')),
            'importStatus' => (new service_import())->status(support_input::text($input, 'id')),
            'cancelImport' => (new service_import())->cancel(support_input::text($input, 'id')),
            'edit' => $playlist->edit(support_input::integer($input['uid'] ?? null), $input),
            'changeOption' => $options->change(support_input::array($input, 'config')),
            'changeSetting' => $options->setting(support_input::array($input, 'config')),
            'uploadFile' => (new service_upload())->save(support_input::text($input, 'type'), support_input::text($input, 'file')),
            'getVersion' => \VioletBlue\support\support_version::info(),
            'getUpdate' => (new service_release())->check(($input['force'] ?? false) === true),
            'startUpdate' => (new service_update())->start(support_input::text($input, 'version')),
            'getUpdateStatus' => (new service_update())->status(support_input::text($input, 'id')),
            'getMigrationDatabases' => (new service_migration())->databases(),
            'previewMigration' => (new service_migration())->preview(support_input::text($input, 'database'), support_input::text($input, 'prefix')),
            'applyMigration' => (new service_migration())->apply(support_input::text($input, 'database'), support_input::text($input, 'prefix'), support_input::text($input, 'mode'), support_input::text($input, 'fingerprint')),
        };
    }
}
