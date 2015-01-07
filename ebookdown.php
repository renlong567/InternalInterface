<html>
    <head>
        <title>批量下架页面</title>
    </head>
    <body>
        <div style="text-align: center;">
            <h1>批量下架功能页面</h1>
            <form action="" method="POST">
                <textarea name="file" cols="40" rows="10"></textarea>
                <input type="submit" value="提交" />
            </form>
        </div>
    </body>
</html>

<?php
/*
 * @author RenLong
 * @date 2013-11-15
 * @remark 批量下架功能页面(输入框样式)
 */
if (!empty($_POST['file']))
{
//    $db_add = '(DESCRIPTION =
//                            (ADDRESS_LIST =
//                              (ADDRESS = (PROTOCOL = TCP)(HOST = 192.168.51.7)(PORT = 1521))
//                            )
//                            (CONNECT_DATA =
//                              (SERVICE_NAME = orcl)
//                            )
//                          )';
    $db_add = '(DESCRIPTION =
                            (ADDRESS_LIST =
                              (ADDRESS = (PROTOCOL = TCP)(HOST = 192.168.51.21)(PORT = 1521))
                            )
                            (CONNECT_DATA =
                              (SERVICE_NAME = edidb)
                            )
                        )';

    try
    {
        $db = new PDO('oci:dbname=' . $db_add, 'testdb', 'testdb');
    }
    catch (Exception $e)
    {
        exit('<div style="text-align:center;">数据库连接失败</div>');
    }

    $file = $_POST['file'];
    $load_file = array();
    $load_file = explode("\n", $file);

    $errinfo = array(); //错误信息容器
    //设置计数器
    $ok_num = 0;
    $fail_num = 0;
    $ok_arr = array();
    foreach ($load_file as $key => $value)
    {
        $filter_val = trim($value);
        if ($filter_val != '')
        {
            $sql_select = "SELECT count(*) FROM oneshop_goods WHERE metaid = '" . $filter_val . "'";
            $temp = $db->query($sql_select);
            $fetch_rs = $temp->fetch();

            if ($fetch_rs['COUNT(*)'])
            {

                $ok_arr[] = $filter_val;
                $ok_num++;
            }
            else
            {
                $errinfo[] = '第' . ($key + 1) . '项数据库无记录' . '  ' . $filter_val;
                $fail_num++;
            }
        }
        else
        {
            $errinfo[] = '第' . ($key + 1) . '项metaid值为空';
            $fail_num++;
        }
    }

    if (!empty($ok_arr))
    {
        $sql_update = 'UPDATE oneshop_goods SET state = \'2\' , isindexed = \'0\' WHERE';
        foreach ($ok_arr as $value)
        {
            $sql_update .= ' metaid = \'' . $value . '\' OR';
        }
        $sql_update = rtrim($sql_update, 'OR');
        $db->exec($sql_update);
    }

    if ($errinfo)
    {
        echo '<div style="text-align:center;"><span style="color:red;">成功导入' . $ok_num . '条, ' . '失败' . $fail_num . '条。</span>' . '<br /><br />';
        foreach ($errinfo as $value)
        {
            echo $value . '<br />';
        }
        echo '</div>';
    }
    else
    {
        echo '<div style="text-align:center;">全部成功导入</div>';
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