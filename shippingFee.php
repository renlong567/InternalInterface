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

    echo '<meta charset="UTF-8">';

    $ordersn = explode("\n", $_POST['ordersn']);
    $username = trim($_POST['username']);
    $shippingfee = trim($_POST['shippingfee']);

    $sql = 'UPDATE oneshop_user SET balance=NVL(balance,0)+' . $shippingfee . ' WHERE name=\'' . $username . '\'';
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
        echo '<br /><span style="color:red;">退款失败用户：' . $username . ',金额：' . $shippingfee . ',订单号：' . implode(',', $ordersn) . '</span>';
    }
    echo '<br /><span style="color:green;">退款成功用户：' . $username . ',金额：' . $shippingfee . ',订单号：' . implode(',', $ordersn) . '</span>';

    echo '<br /><a href="./shippingFee.html">返回</a>';

    $time = time();
    $uid = $db->query('SELECT id FROM oneshop_user WHERE name=\'' . $username . '\'')->fetchColumn();
    $sql = "INSERT INTO oneshop_accountlog (userid,balance,changetype,changetime,type,changedesc) VALUES ('$uid',$shippingfee,'4',$time,'1','订单：";
    $sql .= implode(',', $ordersn);
    $sql .= ' 满100元退邮费\')';
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