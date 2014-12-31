<?php
/**
 * ECSHOP 购物流程
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: user.php 16643 2009-09-08 07:02:13Z liubo $
 */

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');
include(ROOT_PATH . 'includes/lib_order.php');
include(ROOT_PATH . 'includes/lib_transaction.php');

/* 载入语言文件 */
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/user.php');
require_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/shopping_flow.php');

if (empty($_REQUEST['step']))
{
    $_REQUEST['step'] = 'cart';
}


//添加物品的购物车
if ($_REQUEST['step'] == 'add_to_cart')
{
    $goods_id = intval($_POST['goods_id']);
    $goods_number = intval($_POST['goods_number']);
    $goods_list = array();
    if (isset($_SESSION['cart_list'])) {
        $goods_list = $_SESSION['cart_list'];
    }

    $has = false;
    foreach ($goods_list as $key => $goods) {
        if ($goods['id'] == $goods_id) {
            $has = true;
            $goods['number'] += $goods_number;
            break;
        }
    }

    if (!$has) {
        $goods_list[] = array(
            'id' => $goods_id,
            'number' => $goods_number,
        );
    }
    $_SESSION['cart_list'] = $goods_list;
}

elseif ($_REQUEST['step'] == 'drop_consignee')
{
    /*------------------------------------------------------ */
    //-- 删除收货人信息
    /*------------------------------------------------------ */

    $consignee_id = intval($_GET['id']);
    $res = drop_consignee($consignee_id);
    ecs_header("Location: flow.php?step=consignee\n");
    exit;
}

//删除商品
else if ($_REQUEST['step'] == 'drop_goods')
{
    $goods_id =  intval($_GET['id']);
    $goods_list = $_SESSION['cart_list'];

    foreach ($goods_list as $key => $goods) {
        if ($goods['id'] == $goods_id) {
            unset($goods_list[$key]);
            break;
        }
    }

    $_SESSION['cart_list'] = $goods_list;
    ecs_header("Location: flow.php\n");
    exit;
}

//改变商品数量
else if ($_REQUEST['step'] == 'ajax_update_cart')
{
    $goods_id = intval($_POST['goods_id']);
    $goods_number = intval($_POST['goods_number']);
    $goods_list = $_SESSION['cart_list'];

    foreach ($goods_list as $key => $goods) {
        if ($goods['id'] == $goods_id) {
            $goods_list[$key]['number'] = $goods_number;
            break;
        }
    }
    $_SESSION['cart_list'] = $goods_list;

    $stats = cart_stats();
    die(json_encode(array(
        'total_desc' => $stats['price'],
        'total_number' => $stats['count']
    )));
}

//显示收货人
else if($_REQUEST['step'] == 'consignee'){
    if ($_SESSION['user_id'] <= 0)
    {
        ecs_header('Location: user.php');
        exit();
    }


    /*------------------------------------------------------ */
    //-- 收货人信息
    /*------------------------------------------------------ */
    include_once('includes/lib_transaction.php');

    if ($_SERVER['REQUEST_METHOD'] == 'GET')
    {
        /* 取得购物类型 */
        $flow_type = isset($_SESSION['flow_type']) ? intval($_SESSION['flow_type']) : CART_GENERAL_GOODS;

        /*
         * 收货人信息填写界面
         */

        if (isset($_REQUEST['direct_shopping']))
        {
            $_SESSION['direct_shopping'] = 1;
        }

        /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
        $smarty->assign('country_list',       get_regions());
        $smarty->assign('shop_country',       $_CFG['shop_country']);
        $smarty->assign('shop_province_list', get_regions(1, $_CFG['shop_country']));

        /* 获得用户所有的收货人信息 */
        if ($_SESSION['user_id'] > 0)
        {
            $consignee_list = get_consignee_list($_SESSION['user_id']);

            if (count($consignee_list) < 5)
            {
                /* 如果用户收货人信息的总数小于 5 则增加一个新的收货人信息 */
                $consignee_list[] = array('country' => $_CFG['shop_country'], 'email' => isset($_SESSION['email']) ? $_SESSION['email'] : '');
            }
        }
        else
        {
            if (isset($_SESSION['flow_consignee'])){
                $consignee_list = array($_SESSION['flow_consignee']);
            }
            else
            {
                $consignee_list[] = array('country' => $_CFG['shop_country']);
            }
        }
        $smarty->assign('name_of_region',   array($_CFG['name_of_region_1'], $_CFG['name_of_region_2'], $_CFG['name_of_region_3'], $_CFG['name_of_region_4']));
        $smarty->assign('consignee_list', $consignee_list);
        
        /* 取得每个收货地址的省市区列表 */
        $province_list = array();
        $city_list = array();
        $district_list = array();
        foreach ($consignee_list as $region_id => $consignee)
        {
            $consignee['country']  = isset($consignee['country'])  ? intval($consignee['country'])  : 0;
            $consignee['province'] = isset($consignee['province']) ? intval($consignee['province']) : 0;
            $consignee['city']     = isset($consignee['city'])     ? intval($consignee['city'])     : 0;

            $province_list[$region_id] = get_regions(1, $consignee['country']);
            $city_list[$region_id]     = get_regions(2, $consignee['province']);
            $district_list[$region_id] = get_regions(3, $consignee['city']);
        }
        $smarty->assign('province_list', $province_list);
        $smarty->assign('city_list',     $city_list);
        $smarty->assign('district_list', $district_list);

        /* 返回收货人页面代码 */
        $smarty->assign('real_goods_count', exist_real_goods(0, $flow_type) ? 1 : 0);
    



        $smarty->display('flow_consignee.html');
    }
    else
    {
        /*
         * 保存收货人信息
         */
        $consignee = array(
            'address_id'    => empty($_POST['address_id']) ? 0  :   intval($_POST['address_id']),
            'consignee'     => empty($_POST['consignee'])  ? '' :   compile_str(trim($_POST['consignee'])),
            'country'       => empty($_POST['country'])    ? '' :   intval($_POST['country']),
            'province'      => empty($_POST['province'])   ? '' :   intval($_POST['province']),
            'city'          => empty($_POST['city'])       ? '' :   intval($_POST['city']),
            'district'      => empty($_POST['district'])   ? '' :   intval($_POST['district']),
            'email'         => empty($_POST['email'])      ? '' :   compile_str($_POST['email']),
            'address'       => empty($_POST['address'])    ? '' :   compile_str($_POST['address']),
            'zipcode'       => empty($_POST['zipcode'])    ? '' :   compile_str(make_semiangle(trim($_POST['zipcode']))),
            'tel'           => empty($_POST['tel'])        ? '' :   compile_str(make_semiangle(trim($_POST['tel']))),
            'mobile'        => empty($_POST['mobile'])     ? '' :   compile_str(make_semiangle(trim($_POST['mobile']))),
            'sign_building' => empty($_POST['sign_building']) ? '' :compile_str($_POST['sign_building']),
            'best_time'     => empty($_POST['best_time'])  ? '' :   compile_str($_POST['best_time']),
        );

        if ($_SESSION['user_id'] > 0)
        {
            /* 如果用户已经登录，则保存收货人信息 */
            $consignee['user_id'] = $_SESSION['user_id'];

            save_consignee($consignee, true);
        }

        /* 保存到session */
        $_SESSION['flow_consignee'] = stripslashes_deep($consignee);

        ecs_header("Location: flow.php?step=checkout\n");
        exit;
    }
}

else if ($_REQUEST['step'] == 'checkout')
{
    if ($_SESSION['user_id'] <= 0)
    {
        ecs_header('Location: user.php');
        exit();
    }

    $order = flow_order_info();

    //收货人信息
    $consignee = get_consignee($_SESSION['user_id']);

    //取得配送方式
    $region            = array($consignee['country'], $consignee['province'], $consignee['city'], $consignee['district']);
    $shipping_list     = available_shipping_list($region);

    $cart_weight_price = cart_weight_price(0);
    $insure_disabled   = true;
    $cod_disabled      = true;

    // 查看购物车中是否全为免运费商品，若是则把运费赋为零
    $sql = 'SELECT count(*) FROM ' . $ecs->table('cart') . " WHERE `session_id` = '" . SESS_ID. "' AND `extension_code` != 'package_buy' AND `is_shipping` = 0";
    $shipping_count = $db->getOne($sql);

    foreach ($shipping_list AS $key => $val)
    {
        $shipping_cfg = unserialize_config($val['configure']);
        $shipping_fee = ($shipping_count == 0 AND $cart_weight_price['free_shipping'] == 1) ? 0 : shipping_fee($val['shipping_code'], unserialize($val['configure']),
            $cart_weight_price['weight'], $cart_weight_price['amount'], $cart_weight_price['number']);

        $shipping_list[$key]['format_shipping_fee'] = price_format($shipping_fee, false);
        $shipping_list[$key]['shipping_fee']        = $shipping_fee;
        $shipping_list[$key]['free_money']          = price_format($shipping_cfg['free_money'], false);
        $shipping_list[$key]['insure_formated']     = strpos($val['insure'], '%') === false ?
            price_format($val['insure'], false) : $val['insure'];

        /* 当前的配送方式是否支持保价 */
//        if ($val['shipping_id'] == $order['shipping_id'])
//        {
//            $insure_disabled = ($val['insure'] == 0);
//            $cod_disabled    = ($val['support_cod'] == 0);
//        }
    }

    $smarty->assign('shipping_list',$shipping_list);


    $cod_fee = 0;
    //取得支付方式  0 表示不支持货到付款 反之
    $payment_list = available_payment_list(0, $cod_fee);

    $smarty->assign('payment_list',$payment_list);

    //取得购物车信息
    if(isset($_SESSION['cart_list'])){
        $goods_list =  $_SESSION['cart_list'];
    }

    $smarty->assign('goods_list',$goods_list);

    $smarty->display('flow_checkout.html');

}

//显示购物车
else 
{
    $stats = cart_stats();
    $goods_list = $stats['goods_list'];
    $total_price = $stats['price'];
    $total_price_formated = price_format($total_price);

    $smarty->assign('goods_list', $goods_list);
    $smarty->assign('total_price_formated', $total_price_formated);
    $smarty->display('flow_cart.html');
}
