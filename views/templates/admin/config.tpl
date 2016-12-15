
<body onload="">
<img src="{$img_icono|escape:'htmlall':'UTF-8'}" width="200" height="183"/>


<h2>{l s='Pago electronico con Tarjetas de Credito o Redcompra a traves de Webpay Plus' mod='webpay'}</h2>


<form action="{$post_url|escape:'htmlall':'UTF-8'}" method="post" style="clear: both; margin-top: 10px;">
    <fieldset>
        <legend><img src="../img/admin/contact.gif"/>{l s='Configuracion' mod='webpay'}</legend>
        {if isset($errors.merchantERR)}
            <div class="error">
                <p>{$errors.merchantERR|escape:'htmlall':'UTF-8'}</p>
            </div>
        {/if}
        

        <label for="storeID">{l s='Codigo de Comercio' mod='webpay'}</label>

        <div class="margin-form"><input type="text" size="90" id="storeID" name="storeID" value="{$data_storeid|escape:'htmlall':'UTF-8'}"/></div>
        <label for="secretCode">{l s='Llave privada' mod='webpay'}</label>
        <div class="margin-form"><textarea cols="90" rows="6" wrap="soft" placeholder="" name="secretCode" id="secretCode" value="{$data_secretcode|escape:'htmlall':'UTF-8'}">{$data_secretcode|escape:'htmlall':'UTF-8'}</textarea></div>
        <label for="certificate">{l s='Certificado' mod='webpay'}</label>
        <div class="margin-form"><textarea cols="90" rows="6" wrap="soft" id="certificate" name="certificate" value="{$data_certificate|escape:'htmlall':'UTF-8'}"/>{$data_certificate|escape:'htmlall':'UTF-8'}</textarea></div>
                                        
        <label for="certificateTransbank">{l s='Certificado Transbank' mod='webpay'}</label>
        <div class="margin-form"><textarea cols="90" rows="6" wrap="soft" id="certificateTransbank" name="certificateTransbank" value="{$data_certificatetransbank|escape:'htmlall':'UTF-8'}"/>{$data_certificatetransbank|escape:'htmlall':'UTF-8'}</textarea></div>                                        
                                        

        <label for="ambient">{l s='Ambiente' mod='webpay'}</label>
   
        <div class="margin-form">
            <select name="ambient" onChange="
                if(this.options[0].selected){ 
                    carga_datos_integracion();                                                                         
                }else if(this.options[1].selected){
                    carga_datos_certificacion();
                }else if(this.options[2].selected){ 
                    carga_datos_produccion();
                }" default="INTEGRACION">
                <option value="INTEGRACION" {if $data_ambient eq "INTEGRACION"}selected{/if}>Integracion</option>
                <option value="CERTIFICACION" {if $data_ambient eq "CERTIFICACION"}selected{/if}>Certificacion</option>
                <option value="PRODUCCION" {if $data_ambient eq "PRODUCCION"}selected{/if}>Produccion</option>
            </select>
        </div>

<div class="panel-footer">
    <div align="right"><button type="submit" value="1" id="webpay_updateSettings" name="webpay_updateSettings" class="btn btn-default pull-right">
            <i class="process-icon-save" value="{l s='Save Settings' mod='webpay'}"></i> Guardar
    </button>
    </div>
</div>
    </fieldset>
</form>

    
<script type="text/javascript">
   function carga_datos_integracion(){
       
        var private_key_js = "{$data_secretcode_init}".replace(/<br\s*\/?>/mg,"\n");
        var public_cert_js = "{$data_certificate_init}".replace(/<br\s*\/?>/mg,"\n");
        var webpay_cert_js = "{$data_certificatetransbank_init}".replace(/<br\s*\/?>/mg,"\n");
       
        document.getElementById('storeID').value = "{$data_storeid_init|escape:'htmlall':'UTF-8'}";  
        document.getElementById('secretCode').value = private_key_js;
        document.getElementById('certificate').value = public_cert_js;    
        document.getElementById('certificateTransbank').value = webpay_cert_js;      
   }

   function carga_datos_certificacion(){        
        document.getElementById('secretCode').value = ''; 
        document.getElementById('certificate').value = ''; 
        document.getElementById('certificateTransbank').value = '';
        document.getElementById('storeID').value = '';  
   }

    function carga_datos_produccion(){        
        document.getElementById('secretCode').value = ''; 
        document.getElementById('certificate').value = ''; 
        document.getElementById('certificateTransbank').value = '';
        document.getElementById('storeID').value = '';  
   }   
   
</script>

</body>