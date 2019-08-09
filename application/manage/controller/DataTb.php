<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/8/8
 * Time: 10:31
 */

namespace app\manage\controller;


use app\common\controller\Manage;
use app\common\model\DataTb as DataTbModel;
class DataTb extends Manage
{

    public function index(){
        $dataTbModel = new DataTbModel();

        if ($this->request->isAjax()){
            return $dataTbModel->tableData(input('param.'));
        }

        return $this->fetch();
    }

    public function import(){
        header("Content-type: text/html; charset=utf-8");
        $file = $_FILES['importFile'];
        $goods_id = input('goods_id','');
        if (empty($goods_id)){
            return $this->error('请填写商品ID');exit;
        }
        if($file['error'] == 0) {
            ini_set('max_execution_time', '10');
            include_once ROOT_PATH . 'vendor/PHPExcel/PHPExcel.php';
            $type = \PHPExcel_IOFactory::identify($file['tmp_name']);
            $path = $file['tmp_name'];
            if (!file_exists($path)) { die('no file!'); }//根据不同类型分别操作

            if( $type!='CSV'){
                $objPHPExcel = \PHPExcel_IOFactory::load($path);
            }else if( $type=='CSV' ){
                $objReader = \PHPExcel_IOFactory::createReader('CSV')
                    ->setDelimiter(',')
                    ->setInputEncoding('GBK') //不设置将导致中文列内容返回boolean(false)或乱码
                    ->setEnclosure('"')
//                    ->setLineEnding("\r\n")  //新版本可删除
                    ->setSheetIndex(0);
                $objPHPExcel = $objReader->load($path);
            }else{
                die('Not supported file types!');
            }

//            $fileType = \PHPExcel_IOFactory::identify($file['tmp_name']);
//            $objReader = \PHPExcel_IOFactory::createReader($fileType);
//            $encodestr = "utf-8";
//            if ($fileType == "CSV"){
//                $encodestr = "gbk";
//            }
//            $objPHPExcel = $objReader->load($file['tmp_name'],$encode=$encodestr);
            $sheet = $objPHPExcel->getSheet(0);
            $highestRow = $sheet->getHighestRow(); // 取得总行数
            $highestColumn = $sheet->getHighestColumn(); // 取得总列数
            $column_num = $this->tableRow($highestColumn);
            $dataTbModel = new DataTbModel();
            if ($highestRow > 0){
                $colarray = $this->tabalval();
                $alldata = [];
                for ($row = 2; $row <= $highestRow; $row++) {
                    $data = [];
                    for ($column = 0; $column < $column_num; $column++) {
                        if ($column >56){
                            break;
                        }
                        $val = $sheet->getCellByColumnAndRow($column, $row)->getValue();
                        if ($column == 18){
                            $val = str_replace("'","",$val);
                        }
                        if (!empty($colarray[$column])){
                            $data[$colarray[$column]] = $val;
                        }

                    }
                    $data['goods_id'] = $goods_id;
                    $alldata[] = $data;
                }
                $ids = $dataTbModel->saveAll($alldata);
                $idcount = $highestRow - count($ids) - 1;
                if ($idcount == 0){
                    return $this->success('导入成功');exit;
                }elseif ($idcount > 0){
                    return $this->success('成功导入'.count($ids).'条'.'失败'.$idcount."条"); exit;
                }else{
                    return $this->error('导入异常');exit;
                }
            }

        }
        return $this->error('导入失败');exit;
    }
    private function tableRow($col){
        $array = [
            'A'=>1,'B'=>2,'C'=>3,'D'=>4,'E'=>5,'F'=>6,'G'=>7,'H'=>8,
            'I'=>9,'J'=>10,'K'=>11,'L'=>12,'M'=>13,'N'=>14,
            'O'=>15,'P'=>16,'Q'=>17,'R'=>18,'S'=>19,'T'=>20,'U'=>21,'V'=>22,'W'=>23,'X'=>24,'Y'=>25,'Z'=>26,'AA'=>27,
            'AB'=>28,'AC'=>29,'AD'=>30,'AE'=>31,'AF'=>32,'AG'=>33,'AH'=>34,'AI'=>35,'AJ'=>36,'AK'=>37,'AL'=>38,'AM'=>39,'AN'=>40,'AO'=>41,
            'AP'=>42,'AQ'=>43,'AR'=>44,'AS'=>45,'AT'=>46,'AU'=>47,'AV'=>48,'AW'=>49,'AX'=>50,'AY'=>51,'AZ'=>52,'BA'=>53,'BB'=>54,'BC'=>55,
            'BD'=>56,'BE'=>57,'BF'=>58,'BG'=>59,'BH'=>60,'BI'=>61
        ];
        return $array[$col];
    }
    private function tabalval(){
       return ['order_id','pay_nickname','alipay_num','pay_order_num','pay_detail','pay_amount','pay_postage','pay_integral',
           'amount','fandian_integral','real_pay_amount','real_pay_integral','order_status','leave_message','consignee','ship',
           'freight_type','','mobile','create_order_time','pay_order_time','goods_name','','ship_num','logistics_company',
           '','goods_number','','','order_end_b','','','','','','','','','','','','','','','','','','','','','',
           'refund_amount','','','confirm_time',''];
    }
    public function export(){

    }
}