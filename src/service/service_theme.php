<?php
declare(strict_types=1);
namespace VioletBlue\service;

use InvalidArgumentException;
use VioletBlue\support\support_config;

/** 校验并保存 PC、手机配色及布局开关。 */
final class service_theme
{
    public const COLORS = [
        '--title' => '#000000', '--line-color' => '#8a2be2', '--all-text' => '#808080',
        '--scroll-bar' => 'rgba(138,43,226,0.2)', '--tag-text' => '#562385',
        '--tag-background' => 'rgba(138,43,226,0.2)', '--song-card' => 'rgba(249,216,255,0.25)',
        '--song-card-claim' => 'rgba(249,216,255,0.7)', '--type' => '#d8bfd8',
        '--type-selected' => '#9370db', '--button-color' => '#9370db',
        '--bio-card' => 'rgba(138,43,226,0.2)', '--nickname-color' => '#9370db',
        '--user-data-text' => '#594581', '--tool-box' => '#c9b3f4',
        '--page-background' => '#ffffff', '--surface-background' => 'rgba(255,255,255,0.9)',
        '--surface-shadow' => '#dcdcdc', '--profile-background' => 'rgba(255,255,255,0.9)',
        '--profile-stats-background' => '#ffffff', '--profile-divider' => '#e5e7eb',
        '--footer-background' => 'rgba(255,255,255,0.9)', '--footer-text' => '#6b7280',
        '--input-background' => '#ffffff', '--input-text' => '#000000',
        '--input-border' => '#ddd6fe', '--input-placeholder' => '#9ca3af',
        '--form-input-background' => '#b39fdb', '--form-input-text' => '#ffffff',
        '--form-help' => '#7b68ee', '--button-text' => '#ffffff',
        '--type-text' => '#000000', '--type-selected-text' => '#ffffff',
        '--tool-icon-color' => '#ffffff', '--scroll-track' => '#ededed',
        '--focus-ring' => '#a78bfa', '--profile-stats-shadow' => 'rgba(0,0,0,0.1)',
        '--tool-label-background' => 'rgba(0,0,0,0.3)', '--tool-label-text' => '#ffffff',
        '--tool-press-shadow' => 'rgba(0,0,0,0.15)', '--error-text' => '#dc2626',
        '--toast-background' => '#7c3aed', '--toast-error-background' => '#dc2626', '--toast-text' => '#ffffff',
        '--dialog-overlay' => 'rgba(0,0,0,0.4)', '--dialog-background' => '#ffffff',
        '--login-start' => '#fbc2eb', '--login-end' => '#a6c1ee', '--login-background' => '#ffffff',
        '--login-link' => '#9370db', '--upload-background' => '#e9967a',
        '--upload-button-background' => '#d3394c', '--upload-button-text' => '#f1e5e6',
        '--preview-background' => '#ffffff',
    ];

    public const DEFAULTS = [
        ...self::COLORS,
        'mobile_song_style' => 'card',
        'desktop_show_profile' => true, 'desktop_show_search' => true,
        'desktop_show_notice' => true, 'desktop_show_tags' => true,
        'desktop_background_image' => true, 'mobile_background_image' => true,
        'mobile_colors' => self::COLORS,
    ];

    public const DESKTOP_SWITCHES = [
        'desktop_show_profile', 'desktop_show_search', 'desktop_show_notice', 'desktop_show_tags',
    ];
    public const BOOLEAN_OPTIONS = [...self::DESKTOP_SWITCHES, 'desktop_background_image', 'mobile_background_image'];

    /**
     * 读取主题并补齐缺失颜色和开关。
     * @return array 包含独立手机配色的完整主题
     */
    public function info(): array
    {
        $path = support_config::path('theme');
        $colors = is_file($path) ? support_config::read('theme') : [];
        $result = self::DEFAULTS;
        foreach ($colors as $key => $color) {
            if ($key === 'mobile_song_style') {
                if (in_array($color, ['card', 'compact'], true)) {
                    $result[$key] = $color;
                }
            } elseif (in_array($key, self::BOOLEAN_OPTIONS, true)) {
                if (is_bool($color)) {
                    $result[$key] = $color;
                }
            } elseif (array_key_exists($key, self::COLORS) && self::valid($color)) {
                $result[$key] = $color;
            }
        }
        // 旧版共用配色首次读取时复制给手机，后续保存为独立的完整配色。
        $result['mobile_colors'] = array_intersect_key($result, self::COLORS);
        $mobile = $colors['mobile_colors'] ?? [];
        if (is_array($mobile)) {
            foreach ($mobile as $key => $color) {
                if (array_key_exists($key, self::COLORS) && self::valid($color)) {
                    $result['mobile_colors'][$key] = $color;
                }
            }
        }
        return $result;
    }

    /**
     * 校验 HEX、RGB 和 RGBA 颜色。
     * @param mixed $color 待验证的颜色值
     * @return bool 颜色是否有效
     */
    public static function valid(mixed $color): bool
    {
        if (!is_string($color) || strlen($color) > 64) {
            return false;
        }
        if (preg_match('/\A#(?:[a-f0-9]{3}|[a-f0-9]{4}|[a-f0-9]{6}|[a-f0-9]{8})\z/iD', $color)) {
            return true;
        }
        if (preg_match('/\Argb(a?)\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})(?:\s*,\s*(0(?:\.\d+)?|1(?:\.0+)?))?\s*\)\z/iD', $color, $parts)) {
            return (int) $parts[2] <= 255 && (int) $parts[3] <= 255 && (int) $parts[4] <= 255
                && ($parts[1] === '' ? !isset($parts[5]) : isset($parts[5]));
        }
        return false;
    }

    /**
     * 完整校验本次修改后保存主题。
     * @param array $colors 部分或全部颜色、手机配色和布局开关
     * @return array 合并后的完整主题
     */
    public function change(array $colors): array
    {
        if (array_is_list($colors) || count($colors) > count(self::DEFAULTS)) {
            throw new InvalidArgumentException('主题设置必须为选项名与值的映射');
        }
        $result = $this->info();
        // 所有修改先合并到内存，任一字段无效时不写配置文件。
        foreach ($colors as $key => $color) {
            if ($key === 'mobile_colors') {
                if (!is_array($color) || array_is_list($color) || count($color) > count(self::COLORS)) {
                    throw new InvalidArgumentException('手机配色必须为颜色变量与值的映射');
                }
                foreach ($color as $name => $value) {
                    if (!array_key_exists($name, self::COLORS) || !self::valid($value)) {
                        throw new InvalidArgumentException('手机配色包含无效颜色或未知变量');
                    }
                    $result['mobile_colors'][$name] = trim($value);
                }
                continue;
            }
            if ($key === 'mobile_song_style') {
                if (!in_array($color, ['card', 'compact'], true)) {
                    throw new InvalidArgumentException('手机歌曲样式只能为 card 或 compact');
                }
                $result[$key] = $color;
                continue;
            }
            if (in_array($key, self::BOOLEAN_OPTIONS, true)) {
                if (!is_bool($color)) {
                    throw new InvalidArgumentException('主题开关必须为布尔值');
                }
                $result[$key] = $color;
                continue;
            }
            if (!array_key_exists($key, self::COLORS) || !self::valid($color)) {
                throw new InvalidArgumentException('颜色无效，仅支持预定义变量及十六进制或 rgb/rgba 颜色');
            }
            $result[$key] = trim($color);
        }
        support_config::write('theme', $result);
        return $result;
    }
}
