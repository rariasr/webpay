<?php

require_once _PS_MODULE_DIR_.'webpay/libwebpay/webpay-soap.php';


class WebPayPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    public $display_column_left = false;


    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        $WebPayPayment = new WebPay();
        
        $cart = Context::getContext()->cart;
        $cartId = self::$cart->id;
        

        $order = new Order(Order::getOrderByCartId($cartId));
        //$order_invoice->id_order|string_format:"%06d";
        //var_dump($order);
        
        //$order_invoice = $order->id_order|string_format:"%06d";
        //die;
        
        
        //echo "\$cartId: ".$cartId;


        Context::getContext()->smarty->assign(array(
                'nbProducts' => $cart->nbProducts(),
                'cust_currency' => $cart->id_currency,
                'currencies' => $this->module->getCurrency((int)$cart->id_currency),
                'total' => $cart->getOrderTotal(true, Cart::BOTH),
                'this_path' => $this->module->getPathUri(),
                'this_path_bw' => $this->module->getPathUri(),
                'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
        ));
        
        

        
        /* preparar pagina de exito o fracaso */
        $url_base = Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . "index.php?fc=module&module={$WebPayPayment->name}&controller=validate&cartId=" . $cartId;
        $url_exito   = $url_base."&return=ok";
        $url_fracaso = $url_base."&return=error";
        $url_confirmacion = Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . "modules/{$WebPayPayment->name}/validate.php";
        
        
        Configuration::updateValue('WEBPAY_URL_FRACASO', $url_fracaso);
        Configuration::updateValue('WEBPAY_URL_EXITO', $url_exito);
        Configuration::updateValue('WEBPAY_URL_CONFIRMACION', $url_confirmacion);
        

        //config lo llenan con los datos almacenados en el e-commerce.
        $config = array(
            "MODO"            => Configuration::get('WEBPAY_AMBIENT'),             
            "PRIVATE_KEY"     => Configuration::get('WEBPAY_SECRETCODE'),
            "PUBLIC_CERT"     => Configuration::get('WEBPAY_CERTIFICATE'),
            "WEBPAY_CERT"     => Configuration::get('WEBPAY_CERTIFICATETRANSBANK'),            
            "CODIGO_COMERCIO" => Configuration::get('WEBPAY_STOREID'),
            "URL_FINAL"       => Configuration::get('WEBPAY_NOTIFYURL'),
            "URL_RETURN"      => Configuration::get('WEBPAY_POSTBACKURL')
        );    
                

        
        try{           
            $webpay = new WebPaySOAP($config);
            $result = $webpay->webpayNormal->initTransaction($cart->getOrderTotal(true, Cart::BOTH), $sessionId="123abc", $ordenCompra=$cartId);
        }catch(Exception $e){
            $result["error"] = "Error conectando a Webpay";
            $result["detail"] = $e->getMessage();  
            //echo "$e";            
	}
        $url_token = '0';
        $token_webpay = '0';
        


        
        if (isset($result["token_ws"])){

               $url_token = $result["url"];
               $token_webpay = $result["token_ws"];

           } else {
                
               //echo "<br/>Ocurrio un error al intentar conectar con WebPay. Por favor intenta mas tarde.<br/>";
               //echo $this->data["error_detail"] = $result["detail"];

           }     
           
          
        Context::getContext()->smarty->assign(array(
                'url_token' => $url_token,
                'token_webpay' => $token_webpay
        ));
        
        //return Context::getContext()->smarty->fetch('module:webpay/views/templates/front/payment_execution.tpl');
        $this->setTemplate('module:webpay/views/templates/front/payment_execution.tpl');
    }
}

