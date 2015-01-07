<html>
    <head>
        <meta charset="UTF-8" />
        <title>读书卡批量绑定用户</title>
    </head>
    <body>
        <div style="text-align: center;">
            <h1>批量绑定读书卡页面</h1>
            <form action="" method="GET">
                <div style="margin: 30px;"><textarea name="file" cols="40" rows="10"></textarea>输入卡ID范围，以英文逗号','区别</div>
                <div style="margin: 30px;"><input type="text" name="user" value="" />输入绑定用户账号</div>
                <input type="submit" value="提交" />
            </form>
        </div>
    </body>
</html>
<?php
/*
 * @author RenLong
 * @date 2014-11-4
 * @remark 批量绑定读书卡页面(输入框样式)
 */
if (!empty($_GET['file']))
{
//    $db_add = '(DESCRIPTION =
//                            (ADDRESS_LIST =
//                              (ADDRESS = (PROTOCOL = TCP)(HOST = 192.168.51.7)(PORT = 1521))
//                            )
//                            (CONNECT_DATA =
//                              (SERVICE_NAME = orcl)
//                            )
//                          )';
//    $user = 'yanfatest';
//    $passwd = 'yanfatest';
    $db_add = 'oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=192.168.51.21)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=edidb)));charset=AL32UTF8;';
    $user = 'testdb';
    $passwd = 'testdb';

    try
    {
        $db = new PDO($db_add, $user, $passwd);
    }
    catch (Exception $e)
    {
        exit('<div style="text-align:center;">数据库连接失败</div>');
    }
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $file = trim($_GET['file']);
    $load_file = explode(',', $file);

    foreach ($load_file as $value)
    {
        if (stristr($value, '-'))
        {
            $temp = explode('-', $value);
            if (is_numeric($temp[0]) && is_numeric($temp[1]))
            {
                $num0 = intval(trim($temp[0]));
                $num1 = intval(trim($temp[1]));
                if ($num0 > $num1)
                {
                    echo '开始ID大于结束ID，请检查：' . $num0 . ',' . $num1;
                    exit();
                }
            }
            else
            {
                echo '输入的读书卡ID含有非法字符，请检查：' . $num0 . ',' . $num1;
                exit();
            }
            $temp_arr = range($num0, $num1);

            $id_str[] = implode(',', $temp_arr);
        }
        else
        {
            if (!is_numeric($value))
            {
                echo '输入的读书卡ID含有非法字符，请检查：' . $value;
                exit();
            }
            $id_str[] = $value;
        }
    }
    //数据整理
    $convid_str = implode(',', $id_str);    //转为字符串
    $convid_arr = explode(',', $convid_str);     //转为数组
    $unique_data = array_unique($convid_arr);   //过滤重复ID

    if ($unique_data)
    {
        $sel_sql = 'SELECT id FROM oneshop_user WHERE email=\'' . $_GET['user'] . '\'';
        $userid = $db->query($sel_sql)->fetchColumn();
        $sql_where = '';
        foreach ($unique_data as $value)
        {
            $sql_where .= ' id = \'' . $value . '\' OR';
        }
        $sql_where = rtrim($sql_where, 'OR');
        $check_sql = 'SELECT id FROM oneshop_giftcards WHERE (' . $sql_where . ') AND (invokestate!=1 OR payvalue!=balance)';
        $check_rs = $db->query($check_sql)->fetchAll(PDO::FETCH_COLUMN);
        if ($check_rs)
        {
            echo '以下卡状态错误，无法绑定：<br />';
            echo implode(',', $check_rs);
            echo '<br />';
            $ok_arr = array_diff($unique_data, $check_rs);
            $sql_where = '';
            foreach ($ok_arr as $val)
            {
                $sql_where .= ' id = \'' . $val . '\' OR';
            }
            $sql_where = rtrim($sql_where, 'OR');
        }
        else
        {
            $ok_arr = $unique_data;
        }

        if (!$sql_where)
        {
            exit('ID全部非法');
        }
        $time = time();
        $expiretime = strtotime('+3 year');
        $sql_update = "UPDATE oneshop_giftcards SET invokestate='2',expiredtime=$expiretime,userinvoketime=$time,userid=$userid WHERE (" . $sql_where . ')';

        try
        {
            $db->beginTransaction();
            $db->exec($sql_update);
            $db->commit();
        }
        catch (Exception $ex)
        {
            $db->rollBack();
            echo $ex->getMessage();
            exit('绑定失败');
        }

        echo '绑定成功卡号:<br />';
        echo '总计:' . count($ok_arr) . '张<br />';
        echo implode(',', $ok_arr);
    }
}

//                               _oo0oo_
//                              o8888888o
//                              88" . "88
//                              (| -_- |)
//                              0\  =  /0
//                            ___/`---'\___
//                          .' \\|     |// '.
//                         / \\|||  :  |||// \
//                        / _||||| -:- |||||- \
//                       |   | \\\  -  /// |   |
//                       | \_|  ''\---/''  |_/ |
//                       \  .-\___ '-' ___/-.  /
//                   ____`. .'   /--.--\  `. .'____
//                   ."" '< `.___\_<|>_/___.' >' "".
//                  | | : `- \`.; \ _ /`;.`/ - ` : | |
//                  \ \`_.   \_ ___\ / ___ _/  .-` / /
//             =====`-.____`.____\_____/____.-`____.-`=====
//                               '=---='
//                    客户虐我千百遍，我待客户如初恋
?>