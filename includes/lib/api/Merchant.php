<?php
namespace lib\api;

use Exception;

class Merchant
{
    public static function info(){
        global $conf, $DB, $userrow, $queryArr;

        $pid=intval($queryArr['pid']);
        $orders=$DB->getColumn("SELECT count(*) FROM pre_order WHERE uid=:uid", [':uid'=>$pid]);
        $lastday=date("Y-m-d",strtotime("-1 day"));
        $today=date("Y-m-d");
        $order_today=$DB->getColumn("SELECT count(*) FROM pre_order WHERE uid=:uid AND status=1 AND date=:today", [':uid'=>$pid, ':today'=>$today]);
        $order_lastday=$DB->getColumn("SELECT count(*) FROM pre_order WHERE uid=:uid AND status=1 AND date=:lastday", [':uid'=>$pid, ':lastday'=>$lastday]);
        $order_today_all = round($DB->getColumn("SELECT sum(money) FROM pre_order WHERE uid=:uid AND status=1 AND date=:today", [':uid'=>$pid, ':today'=>$today]),2);
	    $order_lastday_all = round($DB->getColumn("SELECT sum(money) FROM pre_order WHERE uid=:uid AND status=1 AND date=:lastday", [':uid'=>$pid, ':lastday'=>$lastday]),2);

        $result = ['code'=>0, 'pid'=>$pid, 'status'=>$userrow['status'], 'pay_status'=>$userrow['pay'], 'settle_status'=>$userrow['settle'], 'money'=>$userrow['money'], 'settle_type'=>$userrow['settle_id'], 'settle_account'=>$userrow['account'], 'settle_name'=>$userrow['username'], 'order_num'=>$orders, 'order_num_today'=>$order_today, 'order_num_lastday'=>$order_lastday, 'order_money_today'=>strval($order_today_all), 'order_money_lastday'=>strval($order_lastday_all)];
        $result = array_filter($result, function($a){return !isEmpty($a);});
        return $result;
    }

    public static function orders(){
        global $conf, $DB, $userrow, $queryArr;

        $pid=intval($queryArr['pid']);
        $limit=isset($queryArr['limit'])?intval($queryArr['limit']):10;
        $offset=isset($queryArr['offset'])?intval($queryArr['offset']):0;
        if($limit<1)$limit=10;
        if($limit>50)$limit=50;
        if($offset<0)$offset=0;

        $sql = " A.uid=:uid";
        $params = [':uid'=>$pid];
        if(isset($queryArr['status'])){
            $status = intval($queryArr['status']);
            $sql .= " AND A.status=:status";
            $params[':status'] = $status;
        }

        $data = [];
        $list=$DB->getAll("SELECT A.*,B.name typename FROM pre_order A LEFT JOIN pre_type B ON A.type=B.id WHERE{$sql} ORDER BY trade_no DESC LIMIT {$offset},{$limit}", $params);
        foreach($list as $order){
            $data[]=['trade_no'=>$order['trade_no'],'out_trade_no'=>$order['out_trade_no'],'api_trade_no'=>$order['api_trade_no'],'type'=>$order['typename'],'pid'=>$order['uid'],'addtime'=>$order['addtime'],'endtime'=>$order['endtime'],'name'=>$order['name'],'money'=>$order['money'],'param'=>$order['param'],'buyer'=>$order['buyer'],'clientip'=>$order['ip'],'status'=>$order['status'],'refundmoney'=>$order['refundmoney']];
        }

        $result['code'] = 0;
        $result['data'] = $data;
        return $result;
    }
}
