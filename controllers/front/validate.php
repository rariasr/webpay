<?php

require_once _PS_MODULE_DIR_.'webpay/webpay.php';
require_once _PS_MODULE_DIR_.'webpay/libwebpay/webpay-soap.php';


class WebPayValidateModuleFrontController extends ModuleFrontController
{

    public function initContent()
    {
        $this->display_column_left = false;
        $this->display_column_right = false;

        parent::initContent();
        
        if (Context::getContext()->cookie->pago_realizado == "SI") { 
            //echo "estoy en \$this->handleGET()";
            //die;

            //se resetea la variable para la proxima vez.
            Context::getContext()->cookie->__set('pago_realizado', 'NO');            
            $this->handleGET();
        }else{            
            //echo "estoy en \$this->confirm()";
            //die;
            
            $this->confirm();     
        }
    }

    
    public function confirm() {
        $privatekey = Configuration::get('WEBPAY_SECRETCODE');
        $comercio = Configuration::get('WEBPAY_STOREID');
    
        $errorResponse = array('status' => 'RECHAZADO', 'c' => $comercio);
        $acceptResponse = array('status' => 'ACEPTADO', 'c' => $comercio);

        Context::getContext()->cookie->__set('pago_realizado', 'SI');
        
        /*
        var_dump($_POST);
        echo "      isset(\$_POST[\"token_ws\"]) : ";
        echo isset($_POST["token_ws"]);*/
        //die;
        
        if (isset($_POST) && sizeof($_POST)==1) {
              
              //header( 'HTTP/1.1 200 OK' );
              $data = $this->process_response( $_POST );
           
            } else {
                //"Ocurrio un Error al procesar su Compra" o compra anulada. 
                Context::getContext()->cookie->__set('WEBPAY_TX_ANULADA', "SI");  
                //se resetea la variable para la proxima vez.
                Context::getContext()->cookie->__set('pago_realizado', 'NO');                 
                $this->handleGET();                                               
            }
        }           
            
    public function process_response($data) {

        if (isset($data["token_ws"])) {
            $token_ws = $data["token_ws"];
        } else {
            $token_ws = 0;
        }

        Context::getContext()->cookie->__set('WEBPAY_TX_ANULADA', "NO");

        $voucher = false;     
        $error_transbank = "NO";

        //config lo llenan con los datos almacenados en el e-commerce.
        $config = array(
            "MODO"            => Configuration::get('WEBPAY_AMBIENT'),             
            "PRIVATE_KEY"     => Configuration::get('WEBPAY_SECRETCODE'),
            "PUBLIC_CERT"     => Configuration::get('WEBPAY_CERTIFICATE'),
            "WEBPAY_CERT"     => Configuration::get('WEBPAY_CERTIFICATETRANSBANK'),            
            "CODIGO_COMERCIO" => Configuration::get('WEBPAY_STOREID'),
            "URL_FINAL"       => Context::getContext()->link->getModuleLink('webpay', 'validate', array(), true),//Configuration::get('WEBPAY_NOTIFYURL'),
            "URL_RETURN"      => Context::getContext()->link->getModuleLink('webpay', 'validate', array(), true)//Configuration::get('WEBPAY_POSTBACKURL')
        );   


        
        try{                        
            $webpay = new WebPaySOAP($config);
            $result = $webpay->webpayNormal->getTransactionResult($token_ws);
        }catch(Exception $e){
            $result["error"] = "Error conectando a Webpay";
            $result["detail"] = $e->getMessage();
            
            Context::getContext()->cookie->__set('WEBPAY_RESULT_CODE', $e->getCode()); 
            Context::getContext()->cookie->__set('WEBPAY_RESULT_DESC', $e->getMessage()); 
            Context::getContext()->cookie->__set('WEBPAY_TX_ANULADA', "NO");                
            $error_transbank = "SI";
            
            //echo " error de webpay ".$e->getCode(). " mensaje: ".$e->getMessage();
            //die;
            
        }         
        

        $order_id = $result->buyOrder;     


        if ($order_id && $error_transbank == "NO") {
            $this->completar_resp_operacion($result, $error_transbank);

            if ( ($result->VCI == "TSY" || $result->VCI == "")){
                // Transaccion autorizada 
                $voucher = true;

                //se va al voucher final de transbank.
                WebPaySOAP::redirect($result->urlRedirection, array("token_ws" => $token_ws));

            } else {
                $responseDescription = htmlentities($result->detailOutput->responseDescription);

            }

        }

        if (!$voucher){
            
            $cart = Context::getContext()->cart;            
            $customer = new Customer($cart->id_customer);

            if (!Validate::isLoadedObject($customer))
                Tools::redirect('index.php?controller=order&step=1');

            $currency = Context::getContext()->currency;
            $total = (float)$cart->getOrderTotal(true, Cart::BOTH);           
            
            $this->module->validateOrder((int)$cart->id, Configuration::get('PS_OS_ERROR'), $total, $this->module->displayName, NULL, NULL, (int)$currency->id, false, $customer->secure_key);
            Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
            
//$error_message = "Estimado Cliente, le informamos que su transacción terminó de forma inesperada. [".$responseDescription."] ";
            //$redirectOrderReceived = 'index.php?controller=order&step=1';    
            
            //WebPaySOAP::redirect(Context::getContext()->link->getModuleLink('webpay', 'validate', array(), true), array("token_ws" => $token_ws));

        }
    }
    
    public function completar_resp_operacion($result, $error_transbank) {

        $paymentTypeCodearray = array(
            "VD" => "Venta Debito",
            "VN" => "Venta Normal", 
            "VC" => "Venta en cuotas", 
            "SI" => "3 cuotas sin interés", 
            "S2" => "2 cuotas sin interés", 
            "NC" => "N cuotas sin interés", 
        );

        if ($result->detailOutput->responseCode == 0){
            $transactionResponse = "Aceptado";
        } else {
            $transactionResponse = $result->detailOutput->responseDescription; //." (".$result->detailOutput->responseCode.")";
        }            

        if($error_transbank == "NO"){
            
            Context::getContext()->cookie->__set('WEBPAY_RESULT_CODE', $result->detailOutput->responseCode);               
            Context::getContext()->cookie->__set('WEBPAY_RESULT_DESC', $transactionResponse);               
        }

        $date_tmp = strtotime($result->transactionDate);
        $date_tx_hora = date('H:i:s',$date_tmp);
        $date_tx_fecha = date('d-m-Y',$date_tmp);
        
        
        //tipo de cuotas
        if($result->detailOutput->paymentTypeCode == "SI" || $result->detailOutput->paymentTypeCode == "S2" || 
           $result->detailOutput->paymentTypeCode == "NC" || $result->detailOutput->paymentTypeCode == "VC" )
        {
            $tipo_cuotas = $paymentTypeCodearray[$result->detailOutput->paymentTypeCode];
          }else{
              $tipo_cuotas = "Sin cuotas";
          }       

      
        Context::getContext()->cookie->__set('WEBPAY_TX_ANULADA', "NO");  

        Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TXRESPTEXTO', $transactionResponse);  
        Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TOTALPAGO', $result->detailOutput->amount);  
        Context::getContext()->cookie->__set('WEBPAY_VOUCHER_ACCDATE', $result->accountingDate);  
        Context::getContext()->cookie->__set('WEBPAY_VOUCHER_ORDENCOMPRA', $result->buyOrder);  
        Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TXDATE_HORA', $date_tx_hora);  
        Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TXDATE_FECHA', $date_tx_fecha);  
        Context::getContext()->cookie->__set('WEBPAY_VOUCHER_NROTARJETA', $result->cardDetail->cardNumber);  
        Context::getContext()->cookie->__set('WEBPAY_VOUCHER_AUTCODE', $result->detailOutput->authorizationCode);  
        Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TIPOPAGO', $paymentTypeCodearray[$result->detailOutput->paymentTypeCode]); 
        Context::getContext()->cookie->__set('WEBPAY_VOUCHER_TIPOCUOTAS', $tipo_cuotas); 
        Context::getContext()->cookie->__set('WEBPAY_VOUCHER_RESPCODE', $result->detailOutput->responseCode);  
        Context::getContext()->cookie->__set('WEBPAY_VOUCHER_NROCUOTAS', $result->detailOutput->sharesNumber);  
        
        
    }
    
    private function handleGET()
    {
        

        $cart = Context::getContext()->cart;

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active)
                Tools::redirect('index.php?controller=order&step=1');

        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module)
                if ($module['name'] == 'webpay')
                {
                        $authorized = true;
                        break;
                }

        if (!$authorized)
                die($this->module->l('This payment method is not available.', 'validation'));

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer))
                Tools::redirect('index.php?controller=order&step=1');

        $currency = Context::getContext()->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
        
        

        
        //La transaccion fue anulada
        if(Context::getContext()->cookie->WEBPAY_TX_ANULADA == "SI"){
                                    
            $this->module->validateOrder((int)$cart->id, Configuration::get('PS_OS_CANCELED'), $total, $this->module->displayName, NULL, NULL, (int)$currency->id, false, $customer->secure_key);
            Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
        }   
        
        //La transaccion se ejecuto
        if (Context::getContext()->cookie->WEBPAY_RESULT_CODE == 0){
            
            
            $this->module->validateOrder((int)$cart->id, Configuration::get('PS_OS_PREPARATION'), $total, $this->module->displayName, NULL, NULL, (int)$currency->id, false, $customer->secure_key);           
            Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
            
        }else{
            
            $this->module->validateOrder((int)$cart->id, Configuration::get('PS_OS_ERROR'), $total, $this->module->displayName, NULL, NULL, (int)$currency->id, false, $customer->secure_key);
            Tools::redirect('index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&id_module='.(int)$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
        }
    }
    
}
