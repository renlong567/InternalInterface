<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
date_default_timezone_set('Asia/Shanghai');
ini_set("error_reporting", "E_ALL & ~E_NOTICE");

if (!empty($_POST['ordersn']) && !empty($_POST['username']) && !empty($_POST['shippingfee']))
{
    $_dsn = 'oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=192.168.51.21)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=edidb)));charset=AL32UTF8;';
    $_user = 'testdb';
    $_passwd = 'testdb';
//    $_dsn = 'oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=192.168.51.7)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=orcl)));charset=AL32UTF8;';
//    $_user = 'yanfatest';
//    $_passwd = 'yanfatest';

    try
    {
        $db = new PDO($_dsn, $_user, $_passwd);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
        exit();
    }

    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';

    $ordersn = explode("\n", $_POST['ordersn']);
    $username = explode("\n", $_POST['username']);
    $shippingfee = explode("\n", $_POST['shippingfee']);

    if (count($ordersn) != count($username) || count($ordersn) != count($shippingfee) || count($username) != count($shippingfee))
    {
        exit('输入信息数量不相等，请检查');
    }
    $ok = 0;
    $fail = 0;
    $none = 0;

    foreach ($username as $key => $v)
    {
        if ($v)
        {
            $sql = 'UPDATE oneshop_user SET balance=balance+' . $shippingfee[$key] . ' WHERE name=\'' . trim($v) . '\'';
            try
            {
                $db->beginTransaction();
                $db->exec($sql);
                $db->commit();
            }
            catch (Exception $ex)
            {
                $db->rollback();
                echo $ex->getMessage();
                echo '<br /><span style="color:red;">退款失败用户：' . $v . ',金额：' . $shippingfee[$key] . ',订单号：' . $ordersn[$key] . '</span>';
                ++$fail;
            }
            echo '<br /><span style="color:green;">退款成功用户：' . $v . ',金额：' . $shippingfee[$key] . ',订单号：' . $ordersn[$key] . '</span>';
            ++$ok;
        }
        else
        {
            echo '<br /><span style="color:red;">第' . $key . '行用户名为空,订单号：' . $ordersn[$key] . ',金额：' . $shippingfee[$key] . '</span>';
            ++$none;
        }
    }

    echo '<br />退款完成';
    $total = $ok + $fail + $none;
    echo "<br />总计 $total 项：成功 $ok 项，失败 $fail 项，缺少用户名 $none 项";

    $time = time();
    $sql = 'INSERT ALL ';
    foreach ($username as $key => $value)
    {
        $uid = $db->query('SELECT id FROM oneshop_user WHERE name=\'' . trim($value) . '\'')->fetchColumn();
        $sql .= "INTO oneshop_accountlog (userid,balance,changetype,changetime,type,changedesc) VALUES ('$uid',$shippingfee[$key],'4',$time,'1','订单：$ordersn[$key] 满100元退邮费') ";
    }
    $sql .= 'SELECT * FROM DUAL';
    try
    {
        $db->beginTransaction();
        $db->exec($sql);
        $db->commit();
    }
    catch (Exception $ex)
    {
        $db->rollBack();
        echo '日志添加失败<br />';
        echo $ex->getMessage();
    }
}
else
{
    echo '信息输入不全';
}
?>