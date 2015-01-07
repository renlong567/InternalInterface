<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
date_default_timezone_set('Asia/Shanghai');
ini_set("error_reporting", "E_ALL & ~E_NOTICE");

if (!empty($_POST['ordersn']))
{
    $dsn = 'oci:dbname=(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=192.168.51.21)(PORT=1521))(CONNECT_DATA=(SERVICE_NAME=edidb)));charset=AL32UTF8;';
    $username = 'testdb';
    $passwd = 'testdb';
    try
    {
        $db = new PDO($dsn, $username, $passwd);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
        exit();
    }

    $ordersn = explode("\n", $_POST['ordersn']);
    $ordersn = array_unique($ordersn);
    $ordersn_where = '';
    foreach ($ordersn as $value)
    {
        if (!empty($value))
        {
            $ordersn_where .= ' o.sn=\'' . trim($value) . '\' OR';
        }
    }
    $ordersn_where = rtrim($ordersn_where, 'OR');
    $selct = 'o.sn,i.goodsname,i.count,i.unitprice,i.discount,i.amount,o.shippingfee';
    $start_date = empty($_POST['start_date']) ? '' : ' AND addtime >=' . strtotime(trim($_POST['start_date']));
    $end_date = empty($_POST['end_date']) ? '' : ' AND addtime <=' . strtotime(trim($_POST['end_date']));
    $where = 'AND (' . $ordersn_where . ')' . $end_date . $start_date;
    $sql = 'SELECT ' . $selct . ' FROM oneshop_order o,oneshop_orderitem i WHERE o.id=i.orderid ' . $where;
    $data = $db->query($sql)->fetchAll();

    $filename = '新建文件';
    $date = date('Ymd');

    header("Content-type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename=$filename$date.xls");

    $output = "订单号\t商品名称\t销售数量\t码洋\t折扣\t实洋\t邮费\n";
    foreach ($data as $key => $value)
    {
        $output .= "$value[SN]\t";
        $output .= "$value[GOODSNAME]\t";
        $output .= "$value[COUNT]\t";
        $output .= "$value[UNITPRICE]\t";
        $output .= "$value[DISCOUNT]\t";
        $output .= "$value[AMOUNT]\t";
        if ($key == 0 || ($value['SN'] != $data[$key - 1]['SN']))
        {
            $output .= $value['SHIPPINGFEE'];
        }
        $output .= "\n";
    }

    echo iconv("UTF-8", "GB18030", $output);   //为兼容office
}
?>