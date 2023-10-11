<?php

namespace CloverLite;



use PDO;
use PDOException;



/**
 * 数据库类
 */
class Db
{



    /**
     * 链接数据库
     * @return PdoObject
     */
    private static function connect()
    {
        try {
            return new PDO("mysql:host=" . DB_SERVER_NAME . ";dbname=" . DB_NAME . ";port=" . DB_PORT . ";charset=utf8;", DB_USER_NAME, DB_PASSWORD);
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }



    /**
     * 数据库查询语句
     * @param string $sql SQL语句
     * @param boolean $fetch 是否只输出第一行,默认全部输出
     * @return array/false
     */
    public static function query($sql, $fetch = false)
    {
        $result = self::connect()->query($sql);
        if ($result == false || $result == null) {
            return false;
        }
        return ($fetch == true) ? $result->fetch()[0] : $result->fetchAll();
    }



    /**
     * 数据库操作语句
     * @param string $sql SQL语句
     * @return int
     */
    public static function exec($sql)
    {
        return self::connect()->exec($sql);
    }



    /**
     * 参数校验
     * @param array $param 参数
     * @param array $verifier 校验条件
     * @return array 
     */
    public static function validator($param, $verifier)
    {
        $result = true;
        foreach ($verifier as $key => $rules) {
            $rules = $verifier[$key];
            $rules = explode('|', $rules);
            isset($param[$key]) || $param[$key] = NULL;
            foreach ($rules as $rule) {
                $rule = strtolower(trim($rule));
                switch ($rule) {
                    case 'array':
                        $Regx = (is_array($param[$key])) ? 'ok' : '/^\D\W\S$/';
                        break;

                    case (preg_match('/between\s*\d*,\d*/', $rule) == 1):
                        preg_match('/(?<=between{1}\s)\d*(?=,)/', $rule, $min);
                        preg_match('/(?<=,)\d*/', $rule, $max);
                        $Regx = '/^.{' . $min[0] . ',' . $max[0] . '}$/';
                        break;

                    default:
                        $Regx = '//';
                        break;
                }

                $result = ($param[$key] === null) ? false : (($Regx != 'ok') ? (preg_match($Regx, $param[$key]) ? true : false) : true);
                if ($result == false) {
                    return $result;
                }
            }
        }
        return $result;
    }
}
