<?php
declare(strict_types=1);
namespace VioletBlue\service;

use InvalidArgumentException;
use VioletBlue\support\support_config;

/** 校验图片和字体内容，并写入固定资源路径。 */
final class service_upload
{
    private const TARGETS = [
        'face' => '/static/img/face.png', 'background' => '/static/img/background.png',
        'favicon' => '/favicon.ico', 'ifont' => '/static/font/t_min.ttf',
    ];
    private const IMAGES = ['image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon'];

    /**
     * 校验 Base64 文件内容并替换对应静态资源。
     * @param string $type face、background、favicon 或 ifont
     * @param string $data 文件的 Base64 数据 URI
     * @return string 上传成功提示
     */
    public function save(string $type, string $data): string
    {
        $target = self::TARGETS[$type] ?? null;
        if ($target === null) {
            throw new InvalidArgumentException('不支持的上传类型');
        }
        $data = strtr($data, ['-' => '+', '_' => '/', '@' => '=']);
        if (strlen($data) > 2800000 || !preg_match('/\Adata:[^;,]+;base64,(.*)\z/sD', $data, $match)) {
            throw new InvalidArgumentException('文件必须为不超过2MB的 Base64 数据 URI');
        }
        $bytes = base64_decode($match[1], true);
        if ($bytes === false || strlen($bytes) < 1 || strlen($bytes) > 2 * 1024 * 1024) {
            throw new InvalidArgumentException('文件不能为空或超过2MB');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        // 按文件字节判断类型，图片再验证尺寸，字体检查 TTF 文件头。
        if ($type === 'ifont') {
            if (substr($bytes, 0, 4) !== "\x00\x01\x00\x00" || !in_array($mime, ['font/sfnt', 'font/ttf', 'application/x-font-ttf', 'application/font-sfnt', 'application/octet-stream'], true)) {
                throw new InvalidArgumentException('字体必须为有效 TTF');
            }
        } elseif (!in_array($mime, self::IMAGES, true)) {
            throw new InvalidArgumentException('只允许 PNG、JPEG、GIF、WebP 或 ICO 图片');
        } else {
            // getimagesize 可能对损坏的图片发出警告，统一返回参数错误。
            try {
                $size = getimagesizefromstring($bytes);
            } catch (\ErrorException) {
                $size = false;
            }
            if ($size === false || $size[0] > 16384 || $size[1] > 16384) {
                throw new InvalidArgumentException('图片无效或尺寸过大');
            }
        }
        support_config::atomic(support_config::publicRoot() . $target, $bytes);
        return '上传文件成功';
    }
}
