<?php
if (!defined('ABSPATH') ) exit;

function BankMaskan_Gateway_Load() {
	
	if ( class_exists( 'WC_Payment_Gateway' ) && !class_exists( 'WC_BankMaskan' ) && !function_exists('Woocommerce_Add_BankMaskan_Gateway') ) {
		
		add_filter('woocommerce_payment_gateways', 'Woocommerce_Add_BankMaskan_Gateway' );
		function Woocommerce_Add_BankMaskan_Gateway($methods) {
			$methods[] = 'WC_BankMaskan';
			return $methods;
		}

		class WC_BankMaskan extends WC_Payment_Gateway {
			
			public function __construct(){
				
				$this->author = 'mohsenabedy.ir';

				$this->id = 'WC_BankMaskan';
				$this->method_title = 'بانک مسکن';
				$this->method_description = 'تنظیمات درگاه پرداخت بانک مسکن برای افزونه فروشگاه ساز ووکامرس';
				$this->icon = apply_filters('WC_BankMaskan_logo', WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/assets/images/logo.png');
				$this->has_fields = false;

				$this->init_form_fields();
				$this->init_settings();
				
				$this->title = $this->settings['title'];
				$this->description = $this->settings['description'];
				
				$this->terminal = $this->settings['terminal'];	//set
				$this->username = $this->settings['username'];	//set
				$this->password = $this->settings['password'];	//set

				$this->success_massage = $this->settings['success_massage'];
				$this->failed_massage = $this->settings['failed_massage'];
				$this->cancelled_massage = $this->settings['cancelled_massage'];
				
				if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) )
					add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
				else
					add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );	
				add_action('woocommerce_receipt_'.$this->id.'', array($this, 'Send_to_BankMaskan_Gateway'));
				add_action('woocommerce_api_'.strtolower(get_class($this)).'', array($this, 'Return_from_BankMaskan_Gateway') );
			}

			public function admin_options(){
				$action = $this->author;
				do_action( 'WC_Gateway_Payment_Actions', $action );
				parent::admin_options();
			}
		
			public function init_form_fields(){
				$this->form_fields = apply_filters('WC_BankMaskan_Config', 
					array(
						'base_confing' => array(
							'title'       => 'تنظیمات پایه ای',
							'type'        => 'title',
							'description' => '',
						),
						'enabled' => array(
							'title'   => 'فعالسازی/غیرفعالسازی',
							'type'    => 'checkbox',
							'label'   => 'فعالسازی درگاه بانک مسکن',
							'description' => 'برای فعالسازی درگاه پرداخت بانک مسکن باید چک باکس را تیک بزنید',
							'default' => 'yes',
							'desc_tip'    => true,
						),
						'title' => array(
							'title'       => 'عنوان درگاه',
							'type'        => 'text',
							'description' => 'عنوان درگاه که در طی خرید به مشتری نمایش داده میشود',
							'default'     => 'بانک مسکن',
							'desc_tip'    => true,
						),
						'description' => array(
							'title'       => 'توضیحات درگاه',
							'type'        => 'text',
							'desc_tip'    => true,
							'description' => 'توضیحاتی که در طی عملیات پرداخت برای درگاه نمایش داده خواهد شد',
							'default'     => 'پرداخت امن به وسیله کلیه کارت های عضو شتاب از طریق درگاه بانک مسکن'
						),
						'account_confing' => array(
							'title'       => 'تنظیمات حساب بانک مسکن',
							'type'        => 'title',
							'description' => '',
						),
						'terminal' => array(
							'title'       => 'ترمینال آیدی',
							'type'        => 'text',
							'description' => 'شماره ترمینال درگاه بانک مسکن',
							'default'     => '',
							'desc_tip'    => true
						),
						'username' => array(
							'title'       => 'نام کاربری',
							'type'        => 'text',
							'description' => 'نام کاربری درگاه بانک مسکن',
							'default'     => '',
							'desc_tip'    => true
						),
						'password' => array(
							'title'       => 'کلمه عبور',
							'type'        => 'text',
							'description' => 'کلمه عبور درگاه بانک مسکن',
							'default'     => '',
							'desc_tip'    => true
						),
						'payment_confing' => array(
							'title'       => 'تنظیمات عملیات پرداخت',
							'type'        => 'title',
							'description' => '',
						),
						'success_massage' => array(
							'title'       => 'پیام پرداخت موفق',
							'type'        => 'textarea',
							'description' => 'متن پیامی که میخواهید بعد از پرداخت موفق به کاربر نمایش دهید را وارد نمایید . همچنین می توانید از شورت کد {transaction_id} برای نمایش کد رهگیری ( کد مرجع تراکنش ) و از شرت کد {SaleOrderId} برای شماره درخواست تراکنش بانک مسکن استفاده نمایید .',
							'default'     => 'با تشکر از شما . سفارش شما با موفقیت پرداخت شد .',
						),
						'failed_massage' => array(
							'title'       => 'پیام پرداخت ناموفق',
							'type'        => 'textarea',
							'description' => 'متن پیامی که میخواهید بعد از پرداخت ناموفق به کاربر نمایش دهید را وارد نمایید . همچنین می توانید از شورت کد {fault} برای نمایش دلیل خطای رخ داده استفاده نمایید . این دلیل خطا از سایت بانک مسکن ارسال میگردد .',
							'default'     => 'پرداخت شما ناموفق بوده است . لطفا مجددا تلاش نمایید یا در صورت بروز اشکال با مدیر سایت تماس بگیرید .',
						),
						'cancelled_massage' => array(
							'title'       => 'پیام انصراف از پرداخت',
							'type'        => 'textarea',
							'description' => 'متن پیامی که میخواهید بعد از انصراف کاربر از پرداخت نمایش دهید را وارد نمایید . این پیام بعد از بازگشت از بانک نمایش داده خواهد شد .',
							'default'     => 'پرداخت به دلیل انصراف شما ناتمام باقی ماند .',
						),
					)
				);
			}

			public function process_payment( $order_id ) {
				$order = new WC_Order( $order_id );	
				return array(
					'result'   => 'success',
					'redirect' => $order->get_checkout_payment_url(true)
				);
			}

			public function Send_to_BankMaskan_Gateway($order_id){

				global $woocommerce;
				$woocommerce->session->order_id_bankMaskan = $order_id;
				$order = new WC_Order( $order_id );

				$currency = $order->get_currency();
				$currency = apply_filters( 'WC_BankMaskan_Currency', $currency, $order_id );

				$action = $this->author;
				do_action( 'WC_Gateway_Payment_Actions', $action );
				$form = '<form action="" method="POST" class="bankMaskan-checkout-form" id="bankMaskan-checkout-form">
						<input type="submit" name="bankMaskan_submit" class="button alt" id="bankMaskan-payment-button" value="'.'پرداخت'.'"/>
						<a class="button cancel" href="' . $woocommerce->cart->get_checkout_url() . '">' . 'بازگشت' . '</a>
					 </form><br/>';
				$form = apply_filters( 'WC_BankMaskan_Form', $form, $order_id, $woocommerce );

				do_action( 'WC_BankMaskan_Gateway_Before_Form', $order_id, $woocommerce );
				echo $form;
				do_action( 'WC_BankMaskan_Gateway_After_Form', $order_id, $woocommerce );

				if (! isset($_POST["bankMaskan_submit"]) ) {
					return;
				}

				$Amount = intval($order->get_total());
				$Amount = apply_filters( 'woocommerce_order_amount_total_IRANIAN_gateways_before_check_currency', $Amount, $currency );

				if ( strtolower($currency) == strtolower('IRT') || strtolower($currency) == strtolower('TOMAN')
				     || strtolower($currency) == strtolower('Iran TOMAN') || strtolower($currency) == strtolower('Iranian TOMAN')
				     || strtolower($currency) == strtolower('Iran-TOMAN') || strtolower($currency) == strtolower('Iranian-TOMAN')
				     || strtolower($currency) == strtolower('Iran_TOMAN') || strtolower($currency) == strtolower('Iranian_TOMAN')
				     || strtolower($currency) == strtolower('تومان') || strtolower($currency) == strtolower('تومان ایران')
				) {
					$Amount = $Amount * 10;
				} else if ( strtolower($currency) == strtolower('IRHT') ) {
					$Amount = $Amount * 1000 * 10;
				} else if ( strtolower($currency) == strtolower('IRHR') ) {
					$Amount = $Amount * 1000;
				}

				$Amount = apply_filters( 'woocommerce_order_amount_total_IRANIAN_gateways_after_check_currency', $Amount, $currency );
				$Amount = apply_filters( 'woocommerce_order_amount_total_IRANIAN_gateways_irr', $Amount, $currency );
				$Amount = apply_filters( 'woocommerce_order_amount_total_Maskan_gateway', $Amount, $currency );

				do_action( 'WC_BankMaskan_Gateway_Payment', $order_id );

				$CARDACCEPTORCODE = $this->terminal;
				$USERNAME = $this->username;
				$USERPASSWORD = $this->password;
				$PAYMENTID = date('ymdHis');
				$CALLBACKURL = add_query_arg( 'wc_order', $order_id , WC()->api_request_url('WC_BankMaskan') );

				$is_error = 'no';
				$error_code = 0;

				$parameters =  array(
					"PAYMENTID" => $PAYMENTID,
					"CALLBACKURL" => $CALLBACKURL,
					"AMOUNT" => $Amount,
					"USERNAME" => $USERNAME,
					"USERPASSWORD" => $USERPASSWORD,
					"CARDACCEPTORCODE" => $CARDACCEPTORCODE
				);

				$curl = curl_init();
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($parameters));
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_URL, "https://fcp.shaparak.ir/NvcService/Api/v2/PayRequest");
// 				curl_setopt($curl, CURLOPT_URL, "http://79.174.161.132:8181/NvcService/Api/v2/PayRequest");
				curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Accept: application/json"));
				$result = curl_exec($curl);
				curl_close($curl);

				if ($result == null || $result == "" || !isset($result)) {
					$is_error = 'yes';
					$error_code = 505;
				} else {
					$confirm = json_decode($result, false);
					$ActionCode = strval($confirm->ActionCode);
					$RedirectUrl = strval($confirm->RedirectUrl);

					if ($ActionCode != "0")	{
						$is_error = 'yes';
						$error_code = $ActionCode;
					} else {
						do_action( 'WC_BankMaskan_Before_Send_to_Gateway', $order_id );
						header("Location: ".$RedirectUrl);
					}
				}

				if ($is_error == 'yes') {

					$Note = sprintf('خطا در هنگام ارسال به بانک : %s', $this->Fault_BankMaskan($error_code) );
					$Note = apply_filters( 'WC_BankMaskan_Send_to_Gateway_Failed_Note', $Note, $order_id, $error_code );
					$order->add_order_note( $Note );

					$Notice = sprintf('در هنگام اتصال به بانک خطای زیر رخ داده است : <br/>%s', $this->Fault_BankMaskan($error_code) );
					$Notice = apply_filters( 'WC_BankMaskan_Send_to_Gateway_Failed_Notice', $Notice, $order_id, $error_code );

					if ( $Notice ) {
						wc_add_notice( $Notice, 'error' );
					}

					do_action( 'WC_BankMaskan_Send_to_Gateway_Failed', $order_id, $error_code );
				}
			}

			public function Return_from_BankMaskan_Gateway(){

				global $woocommerce;
				$action = $this->author;
				do_action( 'WC_Gateway_Payment_Actions', $action );

				if ( isset($_GET['wc_order'] ) ) {
					$order_id = $_GET['wc_order'];
				} else {
					$order_id = $woocommerce->session->order_id_bankMaskan;
				}
				$transaction_id = null;

				if ( $order_id ) {

					$order = new WC_Order($order_id);

					if( $order->status != 'completed' ) {

						$CARDACCEPTORCODE = $this->terminal;
						$USERNAME = $this->username;
						$USERPASSWORD = $this->password;

						$json = stripslashes($_POST['Data']);
						$Res = json_decode($json);

						$transaction_id = strval($Res->RRN);
						$orderId = strval($Res->PaymentID);
						$fault = strval($Res->ActionCode);

						update_post_meta( $order_id, 'WC_BankMaskan_settleSaleOrderId', $transaction_id );

						if ($fault == "511" || $fault == "519") {
							$status = 'cancelled';
						} else if ($fault != "0") {
							$status = 'failed';
						} else {
							$parameters =  array(
								"PAYMENTID" => $orderId,
								"CARDACCEPTORCODE" => $CARDACCEPTORCODE,
								"RRN" => $transaction_id,
								"USERNAME" => $USERNAME,
								"USERPASSWORD" => $USERPASSWORD
							);

							$curl = curl_init();
							curl_setopt($curl, CURLOPT_POST, 1);
							curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($parameters));
							curl_setopt($curl, CURLOPT_URL, "https://fcp.shaparak.ir/NvcService/Api/v2/Confirm");
// 							curl_setopt($curl, CURLOPT_URL, "http://79.174.161.132:8181/NvcService/Api/v2/Confirm");
							curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
							curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Accept: application/json"));
							$result = curl_exec($curl);
							curl_close($curl);

							$result = iconv('UTF-8', 'UTF-8//IGNORE', utf8_encode($result));

							$confirm = json_decode($result, false);
							$ActionCode = strval($confirm->ActionCode);

							if (json_last_error() != 0 || $confirm == null || $ActionCode == null) {
								$status = 'failed';
								$fault = -1;
							} else {
								//$order->add_order_note("ActionCode=".$ActionCode, 1);
								if ($ActionCode == "511" || $ActionCode == "519") {
									$status = 'cancelled';
									$fault = 0;
								} else if ($ActionCode != "0") {
									$status = 'failed';
								} else {
									$status = 'completed';
								}
							}
						}

						$SaleOrderId = isset($orderId) ? $orderId : 0;

						if ( $status == 'completed') {
							$action = $this->author;
							do_action( 'WC_Gateway_Payment_Actions', $action );

							if ( $transaction_id && ( $transaction_id !=0 ) ) {
								update_post_meta( $order_id, '_transaction_id', $transaction_id );
							}

							$order->payment_complete( $transaction_id );
							$woocommerce->cart->empty_cart();

							$Note = sprintf( 'پرداخت موفقیت آمیز بود .<br/> کد رهگیری (کد مرجع تراکنش) : %s <br/> شماره درخواست تراکنش : %s', $transaction_id, $SaleOrderId );
							$Note = apply_filters( 'WC_BankMaskan_Return_from_Gateway_Success_Note', $Note, $order_id, $transaction_id, $SaleOrderId );
							if ( $Note ) {
								$order->add_order_note( $Note, 1 );
							}

							$Notice = wpautop( wptexturize($this->success_massage));
							$Notice = str_replace("{transaction_id}",$transaction_id,$Notice);
							$Notice = str_replace("{SaleOrderId}",$SaleOrderId,$Notice);
							$Notice = apply_filters('WC_BankMaskan_Return_from_Gateway_Success_Notice', $Notice, $order_id, $transaction_id, $SaleOrderId );
							if ( $Notice ) {
								wc_add_notice( $Notice, 'success' );
							}

							do_action( 'WC_BankMaskan_Return_from_Gateway_Success', $order_id, $transaction_id);
							WC()->session->get( 'wc_notices' );
							wp_redirect( add_query_arg( 'wc_status', 'success', $this->get_return_url( $order ) ) );
							exit;
						}
						elseif ( $status == 'cancelled' ) {
							$action = $this->author;
							do_action( 'WC_Gateway_Payment_Actions', $action );

							$sale_order_id = ( $SaleOrderId && $SaleOrderId != 0 ) ? ('<br/>شماره درخواست تراکنش : '.$SaleOrderId) : '';

							$Note = 'کاربر در حین تراکنش از پرداخت انصراف داد .' . $sale_order_id;
							$Note = apply_filters( 'WC_BankMaskan_Return_from_Gateway_Cancelled_Note', $Note, $order_id, $SaleOrderId, $sale_order_id);
							if ( $Note ) {
								$order->add_order_note( $Note, 1 );
							}

							$Notice =  wpautop( wptexturize($this->cancelled_massage));
							$Notice = apply_filters( 'WC_BankMaskan_Return_from_Gateway_Cancelled_Notice', $Notice, $order_id, $SaleOrderId, $sale_order_id);
							if ( $Notice ) {
								wc_add_notice( $Notice, 'error' );
							}

							do_action( 'WC_BankMaskan_Return_from_Gateway_Cancelled', $order_id, $SaleOrderId, $sale_order_id);

							wp_redirect( $woocommerce->cart->get_checkout_url() );
							exit;
						}
						else {
							$action = $this->author;
							do_action( 'WC_Gateway_Payment_Actions', $action );

							$tr_id = ( $transaction_id && $transaction_id != 0 ) ? ('<br/>کد رهگیری (کد مرجع تراکنش) : '.$transaction_id) : '';
							$sale_order_id = ( $SaleOrderId && $SaleOrderId != 0 ) ? ('<br/>شماره درخواست تراکنش : '.$SaleOrderId) : '';

							$Note = sprintf( 'خطا در هنگام بازگشت از بانک : %s %s %s', $this->Fault_BankMaskan($fault), $tr_id , $sale_order_id );
							$Note = apply_filters( 'WC_BankMaskan_Return_from_Gateway_Failed_Note', $Note, $order_id, $transaction_id, $SaleOrderId, $fault );
							if ( $Note ) {
								$order->add_order_note( $Note, 1 );
							}

							$Notice = wpautop( wptexturize($this->failed_massage));
							$Notice = str_replace("{fault}",$this->Fault_BankMaskan($fault),$Notice);
							$Notice = apply_filters( 'WC_BankMaskan_Return_from_Gateway_Failed_Notice', $Notice, $order_id, $transaction_id, $SaleOrderId, $fault );
							if ( $Notice ) {
								wc_add_notice( $Notice, 'error' );
							}

							do_action( 'WC_BankMaskan_Return_from_Gateway_Failed', $order_id, $transaction_id, $SaleOrderId, $fault );

							wp_redirect(  $woocommerce->cart->get_checkout_url()  );
							exit;
						}
					} else {

						$action = $this->author;
						do_action( 'WC_Gateway_Payment_Actions', $action );

						$transaction_id = get_post_meta( $order_id, '_transaction_id', true );
						$SaleOrderId = get_post_meta( $order_id, 'WC_BankMaskan_settleSaleOrderId', true );

						$Notice = wpautop( wptexturize($this->success_massage));
						$Notice = str_replace("{transaction_id}",$transaction_id,$Notice);
						$Notice = str_replace("{SaleOrderId}",$SaleOrderId,$Notice);
						$Notice = apply_filters( 'WC_BankMaskan_Return_from_Gateway_ReSuccess_Notice', $Notice, $order_id, $transaction_id, $SaleOrderId );
						if ($Notice) {
							wc_add_notice( $Notice, 'success' );
						}

						do_action( 'WC_BankMaskan_Return_from_Gateway_ReSuccess', $order_id, $transaction_id, $SaleOrderId );

						wp_redirect( add_query_arg( 'wc_status', 'success', $this->get_return_url( $order ) ) );
						exit;
					}
				}
				else {
					$action = $this->author;
					do_action( 'WC_Gateway_Payment_Actions', $action );

					$fault = 'شماره سفارش وجود ندارد .';
					$Notice = wpautop( wptexturize($this->failed_massage));
					$Notice = str_replace("{fault}",$fault, $Notice);
					$Notice = apply_filters( 'WC_BankMaskan_Return_from_Gateway_No_Order_ID_Notice', $Notice, $order_id, $fault );
					if ($Notice) {
						wc_add_notice( $Notice, 'error' );
					}
					do_action( 'WC_BankMaskan_Return_from_Gateway_No_Order_ID', $order_id, $transaction_id, $fault );

					wp_redirect( $woocommerce->cart->get_checkout_url() );
					exit;
				}
			}

			private static function Fault_BankMaskan($err_code){

				if ($err_code == "settle") {
					return "عملیات Settel دستی با موفقیت انجام شد .";
				}
				switch(intval($err_code)){

					case -2:
						return  "شکست در ارتباط با بانک .";
					case -1:
						return  "شکست در ارتباط با بانک .";
					case 0:
						return  "تراکنش با موفقیت انجام شد .";
					case 1:
						return  "عملیات ناموفق.";
					case 2:
						return  "خطا در برقراری ارتباط.";
					case 3:
						return  "تراکنش نامعتبر است.";
					case 5:
						return  "تراکنش نامعتبر است.";
					case 6:
						return  "خطا در برقراری ارتباط.";
					case 7:
						return  "کارت نامعتبر است";
					case 8:
						return  "باتشخیص هویت دارنده ی کارت، موفق می باشد.";
					case 9:
						return  "متاسفانه خطایی در سرور رخ داده است";
					case 12:
						return  "تراکنش نامعتبر است";
					case 13:
						return  "خطا در برقراری ارتباط.";
					case 14:
						return  "شماره کارت ارسالی نامعتبر است. (وجود ندارد).";
					case 15:
						return  "صادرکننده ی کارت نامعتبراست. (وجود ندارد)";
					case 16:
						return  "تراکنش مورد تایید است و اطلاعات شیار سوم کارت به روز رسانی شود.";
					case 19:
						return  "عملیات ناموفق";
					case 20:
						return  "خطا در برقراری ارتباط";
					case 23:
						return  "تراکنش نامعتبر است.";
					case 25:
						return  "خطا در برقراری ارتباط.";
					case 30:
						return  "تراکنش نامعتبر است.";
					case 31:
						return  "پذیرنده توسط سوئیچ پشتیبانی نمی شود.";
					case 33:
						return  "تاریخ انقضای کارت سپری شده است.";
					case 34:
						return  "عملیات ناموفق.";
					case 36:
						return  "کارت نامعتبر است";
					case 38:
						return  "تعداد دفعات ورود رمز غلط بیش از حد مجاز است.";
					case 39:
						return  "کارت حساب اعتباری ندارد.";
					case 40:
						return  "عملیات ناموفق.";
					case 41:
						return  "کارت مفقودی میباشد.";
					case 42:
						return  "کارت حساب عمومی ندارد.";
					case 43:
						return  "عملیات ناموفق.";
					case 44:
						return  "کارت حساب سرمایه گذاری ندارد.";
					case 48:
						return  "خطا در برقراری ارتباط.";
					case 51:
						return  "موجودی کافی نیست.";
					case 52:
						return  "کارت حساب جاری ندارد.";
					case 53:
						return  "کارت حساب قرض الحسنه ندارد.";
					case 54:
						return  "تاریخ انقضای کارت سپری شده است.";
					case 55:
						return  "رمز کارت نامعتبر است.";
					case 56:
						return  "کارت نا معتبر است.";
					case 57:
						return  "تراکنش نامعتبر است.";
					case 58:
						return  "تراکنش نامعتبر است.";
					case 59:
						return  "کارت نامعتبر است";
					case 61:
						return  "مبلغ تراکنش بیش از حد مجاز است.";
					case 62:
						return  "کارت محدود شده است.";
					case 63:
						return  "عملیات ناموفق.";
					case 64:
						return  "تراکنش نا معتبر است";
					case 65:
						return  "تعداد درخواست تراکنش بیش از حد مجاز است.";
					case 67:
						return  "کارت توسط دستگاه ضبط شود.";
					case 75:
						return  "تعداد دفعات ورود رمزغلط بیش از حد مجاز است.";
					case 77:
						return  "خطا در برقراری ارتباط.";
					case 78:
						return  "کارت فعال نیست.";
					case 79:
						return  "حساب متصل به کارت نامعتبر است یا دارای اشکال است.";
					case 80:
						return  "تراکنش موفق عمل نکرده است.";
					case 81:
						return  "خطا در برقراری ارتباط.";
					case 83:
						return  "خطا در برقراری ارتباط.";
					case 84:
						return  "وضعیت سامانه یا بانک مقصد تراکنش غیرفعال می باشد. (Host Down)";
					case 86:
						return  "خطا در برقراری ارتباط.";
					case 90:
						return  "عملیات ناموفق.";
					case 91:
						return  "صادر کننده یا سوییچ مقصد فعال نمی باشد.";
					case 92:
						return  "خطا در برقراری ارتباط.";
					case 93:
						return  "خطا در برقراری ارتباط.";
					case 94:
						return  "ارسال تراکنش تکراری";
					case 96:
						return  "بروز خطای سیستمی در انجام تراکنش";
					case 97:
						return  "فرآیند تغییر کلید برای صادر کننده یا پذیرنده در حال انجام است.";
					case 98:
						return  ".شارژ مورد نظر موجود نیست";
					case 99:
						return  "بروزرسانی کلیدهای پایانه";
					case 100:
						return  "خطا در برقراری ارتباط";
					case 500:
						return  "کدپذیرندگی معتبر نمی باشد";
					case 501:
						return  "مبلغ بیشتر از حد مجاز است";
					case 502:
						return  "نام کاربری و یا رمز ورود اشتباه است";
					case 503:
						return  "آی پی دامنه کار بر نا معتبر است";
					case 504:
						return  "آدرس صفحه برگشت نا معتبر است";
					case 505:
						return  "خطای نا معلوم";
					case 506:
						return  "شماره سفارش تکراری است -  و یا مشکلی دیگر در درج اطلاعات";
					case 507:
						return  "اعتبار ستجی مقادیر با خطا مواجه شده";
					case 508:
						return  "فرمت درخواست ارسالی نا معتبر است";
					case 509:
						return  "از سرویس سوئیچ پاسخی بازنگشت";
					case 510:
						return  "مشتری منصرف شده است";
					case 511:
						return  "زمان انجام تراکنش به پایان رسیده";
					case 512:
						return  "نامعتبر است Cvv2";
					case 513:
						return  "تاریخ انقضاء کارت نامعتبر است";
					case 514:
						return  "پست الکترونیک نا معتبر است";
					case 515:
						return  "حروف امنیتی اشتباه وارد شده است";
					case 516:
						return  "اطلاعات درخواست نامعتبر میباشد";
					case 517:
						return  "شماره کارت وارد شده صحیح نمیباشد";
					case 518:
						return  "تراکنش یافت نشد";
					case 519:
						return  "مشتری از پرداخت منصرف شده است";
					case 520:
						return  "مشتری در زمان مقرر پرداخت را انجام نداده است";
					case 521:
						return  ".قبلا درخواست تائید با موفقیت ثبت شده است";
					case 522:
						return  ".قبلا درخواست اصلاح تراکنش با موفقیت ثبت شده است";
					case 600:
						return  "لغو تراکنش";
				}
				return "در حین پرداخت خطای سیستمی رخ داده است .";
			}
		}
	}
}

add_action('plugins_loaded', 'BankMaskan_Gateway_Load', 0);
