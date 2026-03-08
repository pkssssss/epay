<?php

class paypal_plugin
{
	static public $info = [
		'name'        => 'paypal', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => 'PayPal', //支付插件显示名称
		'author'      => 'PayPal', //支付插件作者
		'link'        => 'https://www.paypal.com/', //支付插件作者链接
		'types'       => ['paypal'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => 'ClientId',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => 'ClientSecret',
				'type' => 'input',
				'note' => '',
			],
			'appswitch' => [
				'name' => '模式选择',
				'type' => 'select',
				'options' => [0=>'线上模式',1=>'沙盒模式'],
			],
		],
		'select' => null,
		'note' => '', //支付密钥填写说明
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];

	static public function submit(){
		global $siteurl, $channel, $order, $ordername, $sitename, $conf, $DB;

		require_once(PAY_ROOT."inc/PayPalClient.php");

		$parameter = [
            'intent'            => 'CAPTURE',
            'purchase_units'    => [
                [
                    'amount'        => [
                        'currency_code' => 'USD',
                        'value'         => $order['realmoney'],
                    ],
                    'description'   => $order['name'],
					'custom_id'     => TRADE_NO,
                    'invoice_id'    => TRADE_NO,
                ],
            ],
            'application_context'=> [
                'cancel_url'    => $siteurl.'pay/cancel/'.TRADE_NO.'/',
                'return_url'    => $siteurl.'pay/return/'.TRADE_NO.'/',
            ],
        ];

		try {
			$approvalUrl = \lib\Payment::lockPayData(TRADE_NO, function() use($channel, $parameter) {
				$client = new PayPalClient($channel['appid'], $channel['appkey'], $channel['appswitch']);
				$result = $client->createOrder($parameter);

				$approvalUrl = null;
				foreach($result['links'] as $link){
					if($link['rel'] == 'approve'){
						$approvalUrl = $link['href'];
					}
				}
				if(empty($approvalUrl)){
					throw new Exception('获取支付链接失败');
				}
				return $approvalUrl;
			});

			return ['type'=>'jump','url'=>$approvalUrl];
		}
		catch (Exception $ex) {
			sysmsg('PayPal下单失败：'.$ex->getMessage());
		}
	}

	//同步回调
	static public function return(){
		global $channel, $order;

		require_once(PAY_ROOT."inc/PayPalClient.php");
		
		if (isset($_GET['token']) && isset($_GET['PayerID'])) {
		
			$token = $_GET['token'];
			try {
				$client = new PayPalClient($channel['appid'], $channel['appkey'], $channel['appswitch']);
				$result = $client->captureOrder($token);
			} catch (Exception $ex) {
				return ['type'=>'error','msg'=>'支付订单失败 '.$ex->getMessage()];
			}

			$captures = $result['purchase_units'][0]['payments']['captures'][0];
				$amount = $captures['seller_receivable_breakdown']['gross_amount']['value'];
				$trade_no = $captures['id'];
				$out_trade_no = $captures['invoice_id'];
				$buyer = $result['payer']['email_address'];

				if($out_trade_no == TRADE_NO && round($amount,2)==round($order['realmoney'],2)){
					processReturn($order, $trade_no, $buyer);
				}else{
					return ['type'=>'error','msg'=>'订单信息校验失败'];
				}
		} else {
			return ['type'=>'error','msg'=>'PayPal返回参数错误'];
		}
	}

	static public function cancel(){
		return ['type'=>'page','page'=>'error'];
	}

		static public function webhook(){
			global $channel, $DB;
			$json = file_get_contents('php://input');
			$arr = json_decode($json, true);
			if(!$arr || empty($arr['event_type'])){
	            return ['type'=>'html','data'=>'事件类型为空'];
	        }
			if(!in_array($arr['event_type'], ['PAYMENT.CAPTURE.COMPLETED'])){
	            return ['type'=>'html','data'=>'其他事件('.$arr['event_type'].':'.$arr['summary'].')'];
	        }
			if(empty($channel['appsecret'])){
				return ['type'=>'html','data'=>'未配置webhookid'];
			}

			$crc32 = crc32($json);
			$cert_url = isset($_SERVER['HTTP_PAYPAL_CERT_URL']) ? trim($_SERVER['HTTP_PAYPAL_CERT_URL']) : '';
	        if (empty($_SERVER['HTTP_PAYPAL_TRANSMISSION_ID']) || empty($_SERVER['HTTP_PAYPAL_TRANSMISSION_TIME']) || empty($_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG']) || empty($crc32) || empty($cert_url)) {
				return ['type'=>'html','data'=>'签名数据为空'];
	        }
	        $sign_string = $_SERVER['HTTP_PAYPAL_TRANSMISSION_ID'].'|'.$_SERVER['HTTP_PAYPAL_TRANSMISSION_TIME'].'|'.$channel['appsecret'].'|'.$crc32;

	        // 仅允许使用 PayPal 官方证书地址，避免回调证书 URL 被伪造触发 SSRF
			if(!self::isTrustedCertUrl($cert_url)){
				return ['type'=>'html','data'=>'证书地址不合法'];
			}
	        $public_key = openssl_pkey_get_public(get_curl($cert_url));
			if(!$public_key){
				return ['type'=>'html','data'=>'证书解析失败'];
			}
	        $verify = openssl_verify($sign_string, base64_decode($_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG']), $public_key, 'SHA256');
	        if($verify != 1)
	        {
				return ['type'=>'html','data'=>'签名验证失败'];
	        }

			$resource = $arr['resource'];
			$amount = isset($resource['amount']['value']) ? $resource['amount']['value'] : null;
			$trade_no = isset($resource['id']) ? trim((string)$resource['id']) : '';
			$out_trade_no = isset($resource['invoice_id']) ? trim((string)$resource['invoice_id']) : '';
			if($amount === null || empty($trade_no) || !preg_match('/^[0-9]{6,64}$/', $out_trade_no)){
				return ['type'=>'html','data'=>'订单参数错误'];
			}
			$order = $DB->getRow("SELECT * FROM pre_order WHERE trade_no=:trade_no limit 1", [':trade_no'=>$out_trade_no]);
			if(!$order || ($order['channel']!=$channel['id'] && $order['subchannel']!=$channel['id'])){
				return ['type'=>'html','data'=>'no order'];
			}
			if(round($amount,2)!=round($order['realmoney'],2)){
				return ['type'=>'html','data'=>'金额不一致'];
			}
			processNotify($order, $trade_no);
			return ['type'=>'html','data'=>'success'];
		}

		static private function isTrustedCertUrl($url){
			$parts = parse_url($url);
			if(!$parts || empty($parts['scheme']) || empty($parts['host'])) return false;
			if(strtolower($parts['scheme']) !== 'https') return false;
			$host = strtolower($parts['host']);
			if(preg_match('/(^|\\.)paypal\\.com$/', $host)) return true;
			if(preg_match('/(^|\\.)paypalobjects\\.com$/', $host)) return true;
			return false;
		}

	//退款
	static public function refund($order){
		global $channel;
		if(empty($order))exit();

		require_once(PAY_ROOT."inc/PayPalClient.php");

		$parameter = [
            'amount'    => [
                'currency_code'  => 'USD',
                'value'     => $order['refundmoney'],
            ],
        ];

		try{
			$client = new PayPalClient($channel['appid'], $channel['appkey'], $channel['appswitch']);
			$res = $client->refundPayment($order['api_trade_no'], $parameter);
			$result = ['code'=>0, 'trade_no'=>$res['id'], 'refund_fee'=>$res['amount']['value']];
		}catch(Exception $e){
			$result = ['code'=>-1, 'msg'=>$e->getMessage()];
		}
		return $result;
	}

}
