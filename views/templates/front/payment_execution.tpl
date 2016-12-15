{*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author Cristian Rojas SA <cristian.rojas@nodriza.cl>
*  @copyright  2007-2015 Nodriza Spa
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}



{capture name=path}{l s='Pago a través de WebPay' mod='webpay'}{/capture}

{assign var='current_step' value='payment'}

<form method="post" action="{$url_token}" id="webpay_form" style="display: none;">
<input type="hidden" name="token_ws" value="{$token_webpay}" />

{if ({$token_webpay} == '0')}
    
    <p class="alert alert-danger">Ocurrio un error al intentar conectar con WebPay o los datos de conexion son incorrectos.</p>   
          
    <p class="cart_navigation clearfix" id="cart_navigation">
			<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button-exclusive btn btn-default"><i class="icon-chevron-left"></i>{l s='Other payment methods' mod='webpay'}</a>

    </p>
{else}
<div class="box cheque-box">
	  <h3 class="page-subheading">Pago por WebPay</h3>

		<p>
        Se realizara la compra a traves de WebPay por un total de ${$total}
		</p>
</div>

	<p class="cart_navigation clearfix" id="cart_navigation">
			<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" class="button-exclusive btn btn-default"><i class="icon-chevron-left"></i>{l s='Other payment methods' mod='webpay'}</a>

			<button type="submit" class="button btn btn-default button-medium">
				<span>Pagar<i class="icon-chevron-right right"></i></span>
			</button>
	</p>
	<script type="text/javascript">
		document.getElementById('webpay_form').submit();
	</script>
{/if}
</form>
      