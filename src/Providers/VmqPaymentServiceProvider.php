<?php

namespace Azuriom\Plugin\VmqPayment\Providers;

use Azuriom\Extensions\Plugin\BasePluginServiceProvider;
use Azuriom\Plugin\VmqPayment\AliPayMethod;
use Azuriom\Plugin\VmqPayment\WeChatMethod;

class VmqPaymentServiceProvider extends BasePluginServiceProvider
{
    /**
     * Register any plugin services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any plugin services.
     *
     * @return void
     */
    public function boot()
    {
        if (! plugins()->isEnabled('shop')) {
            logger()->warning('This plugin need the shop plugin to work !');

            return;
        }

	$this->loadViews();

	$this->loadTranslations();

	payment_manager()->registerPaymentMethod('wechat', WeChatMethod::class);
	payment_manager()->registerPaymentMethod('alipay', AliPayMethod::class);
    }

}
