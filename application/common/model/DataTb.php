<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/8/8
 * Time: 9:57
 */

namespace app\common\model;


class DataTb extends Common
{


    public function tableData($post){
        if(isset($post['limit'])){
            $limit = $post['limit'];
        }else{
            $limit = config('paginate.list_rows');
        }
        $tableWhere = $this->tableWhere($post);
        $list = $this->field($tableWhere['field'])->where($tableWhere['where'])->order($tableWhere['order'])->paginate($limit);
        $data = $this->tableFormat($list->getCollection());         //返回的数据格式化，并渲染成table所需要的最终的显示数据类型

        $re['code'] = 0;
        $re['msg'] = '';
        $re['count'] = $list->total();
        $re['data'] = $data;

        return $re;
    }

    /**
     * 根据输入的查询条件，返回所需要的where
     * @author sin
     * @param $post
     * @return mixed
     */
    protected function tableWhere($post)
    {
        if (!empty($post['goods_name']) && isset($post['goods_name'])){
            $where[] = ['goods_name','like','%'.$post['goods_name'].'%'];
        }
        if (!empty($post['goods_id']) && isset($post['goods_id'])){
            $where[] = ['goods_id','eq',$post['goods_id']];
        }
        $result['where'] = $where;
        $result['field'] = "order_id,pay_nickname,alipay_num,pay_order_num,pay_amount,real_pay_amount,"+
        "consignee,ship,id,mobile,create_order_time,pay_order_time,goods_name,"+
        "ship_num,logistics_company,confirm_time";
        $result['order'] = ['confirm_time'=>'asc','id'=>'asc'];
        return $result;
    }

    /**
     * 根据查询结果，格式化数据
     * @author sin
     * @param $list
     * @return mixed
     */
    protected function tableFormat($list)
    {
        return $list;
    }
}