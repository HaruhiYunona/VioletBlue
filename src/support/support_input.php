<?php
declare(strict_types=1);
namespace VioletBlue\support;

use InvalidArgumentException;
use JsonException;

/** 解析请求参数，校验类型、编码与数值范围。 */
final class support_input
{
    /**
     * 解析表单或 JSON，请求体字段优先于查询参数。
     * @return array 请求参数映射
     */
    public static function request(): array
    {
        $data = $_POST;
        if (str_contains(strtolower($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')) {
            $body = file_get_contents('php://input');
            $data = self::json($body === false ? '' : $body);
        }
        return $data + $_GET;
    }

    /**
     * 解析 JSON 并限制嵌套深度。
     * @param string $text JSON 文本
     * @return array 数组或对象数据
     */
    public static function json(string $text): array
    {
        try {
            $value = json_decode($text, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException('JSON 格式不正确');
        }
        if (!is_array($value)) {
            throw new InvalidArgumentException('参数必须为数组或对象');
        }
        return $value;
    }

    /**
     * 读取字符串字段并检查 UTF-8 编码。
     * @param array $data 参数映射
     * @param string $key 字段名
     * @param string $default 缺失字段的默认值
     * @return string 字符串值
     */
    public static function text(array $data, string $key, string $default = ''): string
    {
        $value = $data[$key] ?? $default;
        if (!is_string($value) && !is_int($value)) {
            throw new InvalidArgumentException($key . ' 必须为字符串');
        }
        $value = (string) $value;
        if (!mb_check_encoding($value, 'UTF-8')) {
            throw new InvalidArgumentException($key . ' 必须为 UTF-8');
        }
        return $value;
    }

    /**
     * 校验并读取指定范围的整数。
     * @param mixed $value 整数或整数字符串
     * @param int $min 最小允许值
     * @param int $max 最大允许值
     * @return int 范围内的整数
     */
    public static function integer(mixed $value, int $min = 1, int $max = 2147483647): int
    {
        if ((!is_int($value) && !is_string($value)) || filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException('参数必须为整数');
        }
        $number = (int) $value;
        if ($number < $min || $number > $max) {
            throw new InvalidArgumentException('整数参数超出范围');
        }
        return $number;
    }

    /**
     * 读取数组字段，字符串按 JSON 解析。
     * @param array $data 参数映射
     * @param string $key 字段名
     * @return array 解析后的数组
     */
    public static function array(array $data, string $key): array
    {
        $value = $data[$key] ?? null;
        if (is_string($value)) {
            return self::json($value);
        }
        if (!is_array($value)) {
            throw new InvalidArgumentException($key . ' 必须为数组或 JSON');
        }
        return $value;
    }

    /**
     * 转义 HTML 特殊字符。
     * @param string $value 原始文本
     * @return string 可插入 HTML 的文本
     */
    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
