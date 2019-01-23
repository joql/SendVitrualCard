<?php
/*
 * 功能：会员中心－个人中心
 * Author:资料空白
 * Date:20180509
 */

class OrderController extends PcBasicController
{
	private $m_products;
	private $m_order;
	private $m_user;
	private $m_payment;
	private $m_products_pifa;
	private $m_products_card;
	private $m_substation;
    private $m_products_substation;
    private $m_products_wholesale_substation;

    public function init()
    {
        parent::init();
		$this->m_products = $this->load('products');
		$this->m_order = $this->load('order');
		$this->m_user = $this->load('user');
		$this->m_payment = $this->load('payment');
		$this->m_products_pifa = $this->load('products_pifa');
		$this->m_products_card = $this->load('products_card');
        $this->m_products_substation = $this->load('products_substation');
        $this->m_products_wholesale_substation = $this->load('products_wholesale_substation');
		$this->m_substation = $this->load('substation');



    }

    public function buyAction()
    {
		//下订单
		$pid = ceil($this->getPost('pid'));
		$number = ceil($this->getPost('number'));
		$chapwd = $this->getPost('chapwd');
		$addons = $this->getPost('addons');
		$csrf_token = $this->getPost('csrf_token', false);
        $paymethod = $this->getPost('paymethod');

		$chapwd_string = new \Safe\MyString($chapwd);
		$chapwd = $chapwd_string->trimall()->qufuhao2()->getValue();


		if(is_numeric($pid) AND $pid>0 AND is_numeric($number) AND $number>0  AND $chapwd AND $csrf_token AND $paymethod){
			if ($this->VerifyCsrfToken($csrf_token)) {
				if(isset($this->config['orderinputtype']) AND $this->config['orderinputtype']=='2'){
					if($this->login AND $this->userid){
						$email = $this->uinfo['email'];
						$qq = '';
					}else{
						$qq = $this->getPost('qq');
						if($qq AND is_numeric($qq)){
							$email = $qq.'@qq.com';
						}else{
							$data = array('code' => 1006, 'msg' => '请输入QQ号');
							Helper::response($data);
						}
					}
				}else{
					$email = $this->getPost('email',false);
					if($email AND isEmail($email)){
						$qq = '';
					}else{
						$data = array('code' => 1006, 'msg' => '请输入邮箱');
						Helper::response($data);
					}
				}
                $payments = $this->m_payment->getConfig();
                if(isset($payments[$paymethod]) AND !empty($payments[$paymethod])){
                    $payconfig = $payments[$paymethod];
                    if($payconfig['active']=0){
                        $data = array('code' => 1002, 'msg' => '支付渠道已关闭');
                        Helper::response($data);
                    }
                }else{
                    $data = array('code' => 1001, 'msg' => '支付渠道异常');
                    Helper::response($data);
                }

				$product = $this->m_products
                    ->Where(array('id'=>$pid,'active'=>1,'isdelete'=>0))
                    ->SelectOne();
                //单价设置 -- start
                if($this->substation_id != 'master'){
                    //获取分站售价如果存在替换默认售价
                    $exist = $this->m_products_substation
                        ->Where(array(
                            'substation_id' => $this->substation_id,
                            'product_id' => $product['id'],
                        ))->SelectOne();
                    !empty($exist['price']) && $product['price'] = $exist['price'];

                    //获取分站批发价如果存在替换当前售价
                    $wholesale = $this->m_products_wholesale_substation
                        ->Where(array(
                            'substation_id' => $this->substation_id,
                            'product_id' => $product['id'],
                        ))
                        ->Where('num <='.$number)
                        ->Order(array('price'=>'asc'))
                        ->SelectOne();
                    !empty($wholesale['price']) && ($wholesale['price'] < $product['price'] )
                    && $product['price'] = $wholesale['price'];
                }
                if($this->uinfo['super_type'] == 2){
                    //代理用户价格
                    !empty($product['price_agent']) && ($product['price_agent'] < $product['price'] )
                    && $product['price'] = $product['price_agent'];
                }
                //单价设置 -- end

				if(!empty($product)){
					$myip = getClientIP();

					//库存控制
					if($product['stockcontrol']==1 AND $product['qty']<1){
						$data = array('code' => 1003, 'msg' => '库存不足');
						Helper::response($data);
					}
					if($product['stockcontrol']==1 AND $number>$product['qty']){
						$data = array('code' => 1004, 'msg' => '库存不足');
						Helper::response($data);
					}
					if(isset($this->config['limitorderqty']) AND $this->config['limitorderqty']<$number){
						$data = array('code' => 1005, 'msg' => '下单数量超限');
						Helper::response($data);
					}


					$starttime = strtotime(date("Y-m-d"));
					$endtime = strtotime(date("Y-m-d 23:59:59"));
					//进行同一ip，下单未付款的处理判断
					if(isset($this->config['limitiporder']) AND $this->config['limitiporder']>0){
						$total = $this->m_order->Where(array('ip'=>$myip,'status'=>0,'isdelete'=>0))->Where("addtime>={$starttime} and addtime<={$endtime}")->Total();
						if($total>$this->config['limitiporder']){
							$data = array('code' => 1005, 'msg' => '处理失败,您有太多未付款订单了');
							Helper::response($data);
						}
					}

					//进行同一email，下单未付款的处理判断
					if(isset($this->config['limitemailorder']) AND $this->config['limitemailorder']>0){
						$total = $this->m_order->Where(array('email'=>$email,'status'=>0,'isdelete'=>0))->Where("addtime>={$starttime} and addtime<={$endtime}")->Total();
						if($total>$this->config['limitemailorder']){
							$data = array('code' => 1006, 'msg' => '处理失败,您有太多未付款订单了');
							Helper::response($data);
						}
					}

					//对附加输入进行判断
					if($product['addons']){
						$p_addons = explode(',',$product['addons']);
						if(count($p_addons)>count($addons)){
							$data = array('code' => 1006, 'msg' => '自定义内容不能为空!');
							Helper::response($data);
						}
						$o_addons = '';
						foreach($addons AS $k=>$addon){
							$o_addons .= $p_addons[$k].":".$addon.";";
						}
					}else{
						$o_addons = '';
					}


					//记录用户uid
					if($this->login AND $this->userid){
						$userid = $this->userid;
					}else{
						$uinfo = $this->m_user->Where(array('email'=>$email))->SelectOne();
						if(!empty($uinfo)){
							$userid = $uinfo['id'];
						}else{
							$userid = 0;
						}
					}

					//生成orderid
                    mt_srand();
					$postfix = mt_rand(1000, 9999).substr(md5(mt_rand(1000, 9999)),3,6);
					$prefix = isset($this->config['orderprefix'])?$this->config['orderprefix']:'zlkb';
					$orderid = $prefix. date('Y') . date('m') . date('d') . date('H') . date('i') . date('s') .$postfix ;

					//先拿折扣再算订单价格
					$money = $product['price']*$number;
					if($this->config['discountswitch']){
						$pifa = $this->m_products_pifa->getPifa($pid);
						if(!empty($pifa)){
							foreach($pifa AS $pf){
								if($number>=$pf['qty']){
									$money = $money*$pf['discount'];
								}
							}
						}
					}

//					//查询域名分站信息
//                    $substation_exist = $this->m_substation
//                        ->Field('id')
//                        ->Where(array('bind_url'=>$this->server_name))
//                        ->Select();
//                    if(empty($substation_exist[0]['id'])){
//                        $substation = 'master';
//                    }else{
//                        $substation = $substation_exist[0]['id'];
//                    }
					//开始下单，入库
					$m=array(
						'orderid'=>$orderid,
						'userid'=>$userid,
						'email'=>$email,
						'qq'=>$qq,
						'pid'=>$pid,
						'productname'=>$product['name'],
						'price'=>$product['price'],
						'number'=>$number,
						'money'=>$money,
						'chapwd'=>$chapwd,
						'ip'=>$myip,
						'status'=>0,
                        'paymethod'=>$paymethod,
						'addons'=>$o_addons,
						'addtime'=>time(),
                        'substation_id' => $this->substation_id,
					);
					$id=$this->m_order->Insert($m);
					if($id>0){
						$oid = base64_encode($id);
						//设置orderidSESSION
						$this->setSession('order_id',$id);
						$this->setSession('order_email',$email);
						$data = array('code' => 1, 'msg' => '下单成功','data'=>array(
						    'type'=>$paymethod,'oid'=>$oid,
                            'uid'=>$this->uinfo['id'])
                        );
					}else{
						$data = array('code' => 1003, 'msg' => '订单异常');
					}
				}else{
					$data = array('code' => 1002, 'msg' => '商品不存在');
				}
			} else {
                $data = array('code' => 1001, 'msg' => '页面超时，请刷新页面后重试!');
            }
		}else{
			$data = array('code' => 1000, 'msg' => '有内容没有输入完整，在确认一遍？');
		}
		Helper::response($data);
    }

	public function payAction()
	{
		$data = array();
		$oid = $this->get('oid',false);
		$ooid = $this->get('ooid',false);
		$id = 0;
		if($oid OR $ooid){
			if($oid){
				$oid = (int)base64_decode($oid);
				if(is_numeric($oid) AND $oid>0){
					$id = $oid;
				}
			}else{
				if(is_numeric($ooid) AND $ooid>0){
					$id = $ooid;
				}
			}
			$order_id = $this->getSession('order_id');
			if($order_id AND is_numeric($order_id) AND $order_id>0 AND $order_id ==$id ){
				if(is_numeric($id) AND $id>0){
					$order = $this->m_order->Where(array('id'=>$id,'isdelete'=>0))->SelectOne();
					if(!empty($order)){
						//获取支付方式
						$payments = $this->m_payment->getConfig();
						$data['order']=$order;
						$data['payments']=$payments;
						$data['code']=1;
                        try{
                            $orderid = $order['orderid'];
                            if($this->config['paysubjectswitch']>0){
                                $productname = $order['orderid'];
                            }else{
                                $productname = $order['productname'];
                            }
                            //余额支付直接返回
                            if($order['paymethod'] === 'balance'){
                                $data['title'] = "订单详情";
                                $data['result'] = $this->paylocationa($oid);
                                $this->getView()->assign($data);
                                return true;
                            }
                            $payclass = "\\Pay\\".$order['paymethod']."\\".$order['paymethod'];
                            $PAY = new $payclass();
                            $params =array('pid'=>$order['pid'],'orderid'=>$orderid,'money'=>$order['money'],'productname'=>$productname,'weburl'=>$this->config['weburl'],'qrserver'=>$this->config['qrserver']);
                            $pay_url = $PAY->pay($payments[$order['paymethod']],$params);
//                            if($pay_url=='' || $pay_url['code']!=1){
//                                $this->redirect("/product/?error=pay问题");
//                                return FALSE;
//                            }
                            //var_dump($pay_url); die;
                            $data['pay']=$pay_url['data'];
                        } catch (\Exception $e) {
                            $data = array('code' => 1005, 'msg' => $e->getMessage());
                        }
					}else{
                        $this->redirect("/product/?error=订单错误");
                        return FALSE;
					}
				}else{
                    $this->redirect("/product/?error=id错误");
                    return FALSE;
				}
			}else{
                $this->redirect("/product/?error=内容不完整");
                return FALSE;
			}
		}else{
            $this->redirect("/product/?error=id为空");
            return FALSE;
		}
		//echo json_encode($data);die;
		$data['title'] = "订单支付";
		$this->getView()->assign($data);
	}

	public function payajaxAction()
	{
		$paymethod = $this->getPost('paymethod');
		$oid = $this->getPost('oid');
		$csrf_token = $this->getPost('csrf_token');
		if($paymethod AND $oid AND is_numeric($oid) AND $oid>0 AND $csrf_token){
			$payments = $this->m_payment->getConfig();
			if(isset($payments[$paymethod]) AND !empty($payments[$paymethod])){
				$payconfig = $payments[$paymethod];
				if($payconfig['active']>0){
					//获取订单信息
					$order = $this->m_order->Where(array('id'=>$oid,'isdelete'=>0))->SelectOne();
					if(is_array($order) AND !empty($order)){
						if($order['status']>0){
                            $this->redirect("/product/");
                            return FALSE;
						}else{
							try{
								$orderid = $order['orderid'];
								if($this->config['paysubjectswitch']>0){
									$productname = $order['orderid'];
								}else{
									$productname = $order['productname'];
								}
								$payclass = "\\Pay\\".$paymethod."\\".$paymethod;
								$PAY = new $payclass();
								$params =array('pid'=>$order['pid'],'orderid'=>$orderid,'money'=>$order['money'],'productname'=>$productname,'weburl'=>$this->config['weburl'],'qrserver'=>$this->config['qrserver']);
								$data = $PAY->pay($payconfig,$params);
							} catch (\Exception $e) {
								$data = array('code' => 1005, 'msg' => $e->getMessage());
							}
						}
					}else{
						$data = array('code' => 1003, 'msg' => '订单不存在');
					}
				}else{
					$data = array('code' => 1002, 'msg' => '支付渠道已关闭');
				}
			}else{
				$data = array('code' => 1001, 'msg' => '支付渠道异常');
			}
		}else{
			$data = array('code' => 1000, 'msg' => '丢失参数');
		}
		Helper::response($data);
	}

    /**
     * use for:余额支付
     * auth: Joql
     * date:2019-01-23 17:35
     */
    private function paylocationa($oid)
    {
        if ($this->login==FALSE AND !$this->userid) {
            $data = array('code' => 10010, 'msg' => '未登录，不能使用余额支付');
            return $data;
        }

        $paymethod = 'balance';
        if($paymethod AND $oid AND is_numeric($oid) AND $oid>0){
            $payments = $this->m_payment->getConfig();
            if(isset($payments[$paymethod]) AND !empty($payments[$paymethod])){
                $payconfig = $payments[$paymethod];
                if($payconfig['active']>0){
                    //获取订单信息
                    $order = $this->m_order->Where(array('id'=>$oid,'isdelete'=>0))->SelectOne();
                    if(is_array($order) AND !empty($order)){
                        if($order['status']>0){
                            $this->redirect("/product/");
                            return FALSE;
                        }else{
                            $orderid = $order['orderid'];
                            if($this->config['paysubjectswitch']>0){
                                $productname = $order['orderid'];
                            }else{
                                $productname = $order['productname'];
                            }
                            $user = $this->m_user->Field('money')->Where('id','=',$this->userid)->SelectOne();
                            if($user['money'] >= $order['money']){
                                $r = $this->m_user->UpdateById(array(
                                    'money' => '-'.$order['money'],
                                ),$this->userid, true);
                                if($r){
                                    $r = $this->payLocationNotify(array(
                                        'paymethod' => 'balance',
                                        'tradeid' => 'balance',
                                        'paymoney' => $order['money'],
                                        'orderid' => $orderid,
                                    ));
                                    $data = array('code' => 1, 'msg' => '支付成功');
                                }else{
                                    $data = array('code' => 1005, 'msg' => '余额扣款失败');
                                }
                            }else{
                                $data = array('code' => 1004, 'msg' => '余额不足，请充值');
                            }

                        }
                    }else{
                        $data = array('code' => 1003, 'msg' => '订单不存在');
                    }
                }else{
                    $data = array('code' => 1002, 'msg' => '支付渠道已关闭');
                }
            }else{
                $data = array('code' => 1001, 'msg' => '支付渠道异常');
            }
        }else{
            $data = array('code' => 1000, 'msg' => '丢失参数');
        }
        return $data;
    }

    /**
     * use for:余额支付回调
     * auth: Joql
     * date:2019-01-23 15:10
     */
    private function payLocationNotify(array $params){
        //支付渠道
        $paymethod = $params['paymethod'];
        //订单号
        $tradeid = $params['tradeid'];
        //支付金额
        $paymoney = $params['paymoney'];
        //本站订单号
        $orderid = $params['orderid'];

        $m_order =  $this->m_order;
        $m_products_card = $this->m_products_card;
        $m_email_queue = \Helper::load('email_queue');
        $m_products = $this->m_products;
        $m_config = \Helper::load('config');
        $web_config = $m_config->getConfig();

        try{
            //1. 通过orderid,查询order订单
            $order = $m_order->Where(array('orderid'=>$orderid))->SelectOne();
            if(!empty($order)){
                if($order['status']>0){
                    $data =array('code'=>1,'msg'=>'订单已处理,请勿重复推送');
                    return $data;
                }else{
                    if($paymoney < $order['money']){
                        //原本检测支付金额是否与订单金额一致,但由于码支付这样的收款模式导致支付金额有时会与订单不一样,所以这里进行小于判断;
                        //所以,在这里如果存在类似码支付这样的第三方支付辅助工具时,有变动金额时,一定要做递增不能递减
                        $data =array('code'=>1005,'msg'=>'支付金额小于订单金额');
                        return $data;
                    }

                    //2.先更新支付总金额
                    $update = array('status'=>1,'paytime'=>time(),'tradeid'=>$tradeid,'paymethod'=>$paymethod,'paymoney'=>$paymoney);
                    $u = $m_order->Where(array('orderid'=>$orderid,'status'=>0))->Update($update);
                    if(!$u){
                        $data =array('code'=>1004,'msg'=>'更新失败');
                        return $data;
                    }else{
                        //3.开始进行订单处理
                        $product = $m_products->SelectByID('auto,stockcontrol,qty',$order['pid']);
                        if(!empty($product)){
                            if($product['auto']>0){
                                //3.自动处理
                                //查询通过订单中记录的pid，根据购买数量查询密码,修复
                                if($product['stockcontrol']>0){
                                    $Limit = $order['number'];
                                }else{
                                    $Limit = 1;
                                }
                                $cards = $m_products_card->Where(array('pid'=>$order['pid'],'active'=>0,'isdelete'=>0))->Limit($Limit)->Select();
                                if(is_array($cards) AND !empty($cards) AND count($cards)==$Limit){
                                    //3.1 库存充足,获取对应的卡id,密码
                                    $card_mi_array = array_column($cards, 'card');
                                    $card_mi_str = implode(',',$card_mi_array);
                                    $card_id_array = array_column($cards, 'id');
                                    $card_id_str = implode(',',$card_id_array);
                                    //3.1.2 进行密码处理,如果进行了库存控制，就开始处理
                                    if($product['stockcontrol']>0){
                                        //3.1.2.1 直接进行密码与订单的关联
                                        $m_products_card->Where("id in ({$card_id_str})")->Where(array('active'=>0))->Update(array('active'=>1));
                                        //3.1.2.2 然后进行库存清减
                                        $qty_m = array('qty' => 'qty-'.$order['number']);
                                        $m_products->Where(array('id'=>$order['pid'],'stockcontrol'=>1))->Update($qty_m,TRUE);
                                        $kucunNotic=";当前商品库存剩余:".($product['qty']-$order['number']);
                                    }else{
                                        //3.1.2.3不进行库存控制时,自动发货商品是不需要减库存，也不需要取消密码；因为这种情况下的密码是通用的；
                                        $kucunNotic="";
                                    }
                                    //3.1.3 更新订单状态,同时把密码写到订单中
                                    $m_order->Where(array('orderid'=>$orderid,'status'=>1))->Update(array('status'=>2,'kami'=>$card_mi_str));
                                    //3.1.4 把邮件通知写到消息队列中，然后用定时任务去执行即可
                                    $m = array();
                                    //3.1.4.1通知用户,定时任务去执行
                                    if(isEmail($order['email'])){
                                        $content = '用户:' . $order['email'] . ',购买的商品['.$order['productname'].'],密码是:'.$card_mi_str;
                                        $m[]=array('email'=>$order['email'],'subject'=>'商品购买成功','content'=>$content,'addtime'=>time(),'status'=>0);
                                    }
                                    //3.1.4.2通知管理员,定时任务去执行
                                    if(isEmail($web_config['adminemail'])){
                                        $content = '用户:' . $order['email'] . ',购买的商品['.$order['productname'].'],密码发送成功'.$kucunNotic;
                                        $m[]=array('email'=>$web_config['adminemail'],'subject'=>'用户购买商品','content'=>$content,'addtime'=>time(),'status'=>0);
                                    }

                                    if(!empty($m)){
                                        $m_email_queue->MultiInsert($m);
                                    }
                                    $data =array('code'=>1,'msg'=>'自动发卡');
                                }else{
                                    //3.2 这里说明库存不足了，干脆就什么都不处理，直接记录异常，同时更新订单状态
                                    $m_order->Where(array('orderid'=>$orderid,'status'=>1))->Update(array('status'=>3));
                                    file_put_contents(YEWU_FILE, CUR_DATETIME.'-'.'库存不足，无法处理'.PHP_EOL, FILE_APPEND);
                                    //3.2.3邮件通知写到消息队列中，然后用定时任务去执行即可
                                    $m = array();
                                    //3.2.3.1通知用户,定时任务去执行
                                    if(isEmail($order['email'])){
                                        $content = '用户:' . $order['email'] . ',购买的商品['.$order['productname'].'],由于库存不足暂时无法处理,管理员正在拼命处理中....请耐心等待!';
                                        $m[] = array('email'=>$order['email'],'subject'=>'商品购买成功','content'=>$content,'addtime'=>time(),'status'=>0);
                                    }
                                    //3.2.3.2通知管理员,定时任务去执行
                                    if(isEmail($web_config['adminemail'])){
                                        $content = '用户:' . $order['email'] . ',购买的商品['.$order['productname'].'],由于库存不足暂时无法处理,请尽快处理!';
                                        $m[] = array('email'=>$web_config['adminemail'],'subject'=>'用户购买商品','content'=>$content,'addtime'=>time(),'status'=>0);
                                    }

                                    if(!empty($m)){
                                        $m_email_queue->MultiInsert($m);
                                    }
                                    $data =array('code'=>1,'msg'=>'库存不足,无法处理');
                                }
                            }else{
                                //4.手工操作
                                //4.1如果商品有进行库存控制，就减库存
                                if($product['stockcontrol']>0){
                                    $qty_m = array('qty' => 'qty-'.$order['number']);
                                    $m_products->Where(array('id'=>$order['pid'],'stockcontrol'=>1))->Update($qty_m,TRUE);
                                }
                                //4.2邮件通知写到消息队列中，然后用定时任务去执行即可
                                $m = array();
                                //4.2.1通知用户,定时任务去执行
                                if(isEmail($order['email'])){
                                    $content = '用户:' . $order['email'] . ',购买的商品['.$order['productname'].'],属于手工发货类型，管理员即将联系您....请耐心等待!';
                                    $m[] = array('email'=>$order['email'],'subject'=>'商品购买成功','content'=>$content,'addtime'=>time(),'status'=>0);
                                }
                                //4.2.2通知管理员,定时任务去执行
                                if(isEmail($web_config['adminemail'])){
                                    $content = '用户:' . $order['email'] . ',购买的商品['.$order['productname'].'],属于手工发货类型，请尽快联系他!';
                                    if($order['addons']){
                                        $content .='订单附加信息：'.$order['addons'];
                                    }
                                    $m[] = array('email'=>$web_config['adminemail'],'subject'=>'用户购买商品','content'=>$content,'addtime'=>time(),'status'=>0);
                                }
                                if(!empty($m)){
                                    $m_email_queue->MultiInsert($m);
                                }
                                $data =array('code'=>1,'msg'=>'手工订单');
                            }
                        }else{
                            $data =array('code'=>1003,'msg'=>'订单对应商品不存在');
                        }
                    }
                }
            }else{
                $data =array('code'=>1003,'msg'=>'订单号不存在');
            }
        } catch(\Exception $e) {
            file_put_contents(YEWU_FILE, CUR_DATETIME.'-'.$e->getMessage().PHP_EOL, FILE_APPEND);
            $data =array('code'=>1001,'msg'=>$e->getMessage());
        }
        //file_put_contents(YEWU_FILE, CUR_DATETIME.'-'.'异步处理结果:'.json_encode($data).PHP_EOL, FILE_APPEND);
        return $data;
    }
	//支付宝当面付生成二维码
	public function showqrAction()
	{
		$url = $this->get('url',true);
		if($url){
			//增加安全判断
			if(isset($_SERVER['HTTP_REFERER'])){
				$referer_url = parse_url($_SERVER['HTTP_REFERER']);
				$web_url = parse_url($this->config['weburl']);
				if($referer_url['host']!=$web_url['host']){
					$img = APP_PATH.'/public/res/images/pay/weburl-error.png';
					@header("Content-Type:image/png");
					echo file_get_contents($img);
					exit();
				}
			}
			try{
				\PHPQRCode\QRcode::png($url);
				exit();
			} catch (\Exception $e) {
				echo $e->getMessage();
				exit();
			}
		}else{
			echo '参数丢失';
			exit();
		}
	}

	//专门针对第三种支付接口,获取
	public function payjumpAction()
	{
		$paymethod = $this->get('paymethod');
		$orderid = $this->get('orderid');
		if($paymethod AND $orderid AND strlen($orderid)>0){
			$payments = $this->m_payment->getConfig();
			if(isset($payments[$paymethod]) AND !empty($payments[$paymethod])){
				$payconfig = $payments[$paymethod];
				if($payconfig['active']>0){
					//获取订单信息
					$order = $this->m_order->Where(array('orderid'=>$orderid,'isdelete'=>0))->SelectOne();
					if(is_array($order) AND !empty($order)){
						if($order['status']>0){
							$msg = '订单已支付成功';
						}else{
							try{
								$payclass = "\\Pay\\".$paymethod."\\".$paymethod;
								$PAY = new $payclass();
								$params =array('orderid'=>$orderid,'money'=>$order['money'],'productname'=>$order['productname'],'weburl'=>$this->config['weburl']);
								$msg = $PAY->jump($payconfig,$params);
							} catch (\Exception $e) {
								$msg = $e->getMessage();
							}
						}
					}else{
						$msg = '订单不存在';
					}
				}else{
					$msg = '支付渠道已关闭';
				}
			}else{
				$msg = '支付渠道异常';
			}
		}else{
			$msg = '丢失参数';
		}
		echo $msg;
		exit();
	}
}