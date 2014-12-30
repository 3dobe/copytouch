<?php

/**
 * ECSHOP 商品分类页
 * ============================================================================
 * * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: testyang $
 * $Id: category.php 15013 2008-10-23 09:31:42Z testyang $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

$c_id = !empty($_GET['c_id']) ? intval($_GET['c_id']) : 0;
if ($c_id <= 0)
{
    ecs_header("Location: cat_all.php\n");
    exit();
    /*$pcat_array = get_categories_tree();
    foreach ($pcat_array as $key => $pcat_data)
    {
        $pcat_array[$key]['name'] = encode_output($pcat_data['name']);
        if ($pcat_data['cat_id'])
        {
            foreach ($pcat_data['cat_id'] as $k => $v)
            {
                $pcat_array[$key]['cat_id'][$k]['name'] = encode_output($v['name']);
            }
        }
    }
    $smarty->assign('cat_array' , $pcat_array);
    $smarty->assign('all_cat' , 1);*/
}
else
{
    $cat_array = get_categories_tree($c_id);
    $smarty->assign('c_id', $c_id);
    $cat_name = $db->getOne('SELECT cat_name FROM ' . $ecs->table('category') . ' WHERE cat_id=' . $c_id);
    $smarty->assign('cat_name', encode_output($cat_name));
    $smarty->assign('heading', encode_output($cat_name));

    if (!empty($cat_array[$c_id]['cat_id']))
    {
        foreach ($cat_array[$c_id]['cat_id'] as $key => $child_data)
        {
            $cat_array[$c_id]['cat_id'][$key]['name'] = encode_output($child_data['name']);
        }
        $smarty->assign('cat_children', $cat_array[$c_id]['cat_id']);
    }


    $sort = empty($_GET['sort']) ? '' : $_GET['sort'];
    if ($sort == 'click') {
      $sort = 'click';
    } else if ($sort == 'price') {
      $sort = 'price';
    }
    $smarty->assign('sort', $sort);

    $order_rule = 'ORDER BY ';
    if ($sort == '') {
      $order_rule .= 'g.sort_order, g.goods_id DESC';
    } else if ($sort == 'click') {
      $order_rule .= 'g.click_count DESC, g.sort_order';
    } else if ($sort == 'price') {
      $order_rule .= 'g.shop_price, g.sort_order';
    }
    $smarty->assign('url_prefix', 'category.php?c_id='. $c_id);


    $cat_goods = assign_cat_goods($c_id, 0, 'wap', $order_rule);
    $num = count($cat_goods['goods']);
    if ($num > 0)
    {
        $page_num = '10';
        $page = !empty($_GET['page']) ? intval($_GET['page']) : 1;
        $pages = ceil($num / $page_num);
        if ($page <= 0)
        {
            $page = 1;
        }
        if ($pages == 0)
        {
            $pages = 1;
        }
        if ($page > $pages)
        {
            $page = $pages;
        }
        
        $goods_list = array();
        foreach ($cat_goods['goods'] as $goods)
        {
            //if (($i > ($page_num * ($page - 1 ))) && ($i <= ($page_num * $page)))
            //{
                //$price = empty($goods['promote_price_org']) ? $goods['shop_price'] : $goods['promote_price'];
                $price_formated = $goods['shop_price'];

                $goods_list[] = array(
                    'thumb' => $goods['thumb'],
                    'price_formated' => $price_formated,
                    'id' => $goods['id'] ,
                    'name' => encode_output($goods['name'])
                );
            //}
        }
        $smarty->assign('goods_list', $goods_list);
        $pagebar = get_wap_pager($num, $page_num, $page, 'category.php?c_id='.$c_id.'&order_price='.(empty($order_price)?0:$order_price), 'page');
        $smarty->assign('pagebar', $pagebar);
    }

    $pcat_array = get_parent_cats($c_id);
    if (!empty($pcat_array[1]['cat_name']))
    {
        $pcat_array[1]['cat_name'] = encode_output($pcat_array[1]['cat_name']);
        $smarty->assign('pcat_array', $pcat_array[1]);
    }

    $smarty->assign('cat_array', $cat_array);
}

$smarty->assign('footer', get_footer());
$smarty->display('goods_list.html');

?>