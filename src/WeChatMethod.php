<?php

namespace Azuriom\Plugin\VmqPayment;

use Azuriom\Plugin\Shop\Cart\Cart;
use Azuriom\Plugin\Shop\Models\Payment;
use Azuriom\Plugin\Shop\Payment\PaymentMethod;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WeChatMethod extends PaymentMethod
{
    /**
     * The payment method id name.
     *
     * @var string
     */
    protected $id = 'wechat';

    /**
     * The payment method display name.
     *
     * @var string
     */
    protected $name = '微信支付';

    public function startPayment(Cart $cart, float $amount, string $currency)
    {
        $payment = $this->createPayment($cart, $amount, $currency);

        $type = 1; //微信1，支付宝2
        $host = $this->gateway->data['host'];
        $param = urlencode(json_encode(array(
            "item_name" => $this->getPurchaseDescription($payment->id),
            "from" => 'Azuriom',
        )));

        $sign = md5($payment->id."Azuriom".$type.$amount.$this->gateway->data['secret']);
        
        $attributes = [
            "payId" => $payment->id,
            "type" => $type,
            "price" => $amount,
            "param" => "Azuriom",//$param,
            "sign" => $sign,
            "returnUrl" => route('shop.payments.success', $this->id),
            "notifyUrl" => route('shop.payments.notification', $this->id),
        ];

        $response = Http::asForm()->post($host."/createOrder", $attributes);
        if (! $response->successful() || $response['code'] != 1) {
            $this->logInvalid($response, 'Invalid init response');

            return $this->errorResponse();
        }
        $payment->update(['status' => 'pending', 'transaction_id' => $response['data']['orderId']]);
        
        return redirect()->away($host.'/payPage/pay.html?'.Arr::query(["orderId"=>$response['data']['orderId']]));
    }

    public function notification(Request $request, ?string $rawPaymentId)
    {
        $payId = $request->input('payId');
        $orderId = $request->input('orderId');
        $param = $request->input('param');
        $type = $request->input('type');
        $price = $request->input('price');
        $reallyPrice = $request->input('reallyPrice');
        $status = $request->input('status');
        $sign = $request->input('sign');

        
        if ($status === 'Expired') {
            $_sign = md5($orderId.$status.$this->gateway->data['secret']);
            if ($sign !== $_sign) {
                return response()->json('Invalid sign');
            }
            Payment::firstWhere('transaction_id',$orderId)->update(['status' => 'expired']);
            return response()->noContent();
        }
        
        $_sign = md5($payId.$param.$type.$price.$reallyPrice.$status.$this->gateway->data['secret']);
        if($sign !== $_sign){
            return response()->json('Invalid sign');
        }

        $payment = Payment::findOrFail($payId);

        if ($status !== 'Completed') {
            logger()->warning("[Shop] Invalid payment status for #{$payment->transaction_id}: {$status}");

            return $this->invalidPayment($payment, $payment->transaction_id, 'Invalid status');
        }

        $this->processPayment($payment);
        return response("success")->header('Content-type','text/plain');
    }

    public function view()
    {
        return 'shop::admin.gateways.methods.wechat';
    }

    public function rules()
    {
        return [
            'host' => ['required', 'string'],
            'secret' => ['required', 'string'],
        ];
    }

    public function image()
    {
        return asset('plugins/vmqpayment/img/wechat.svg');
    }

    private function logInvalid(Response $response, string $message)
    {
        Log::warning("[Shop] WeChat - {$message} {$response->effectiveUri()} ({$response->status()}): {$response->json('msg')}");
    }
}
