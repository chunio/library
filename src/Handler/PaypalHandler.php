<?php

declare(strict_types=1);

namespace Component;

use App\Constant\Ryenovel\FinanceEnum;
use App\Model\Content\Entity\Novel;
use App\Module\Ryenovel\Logic\FinanceLogic;
use App\Module\Ryenovel\Logic\SystemLogic;
use Hyperf\Database\Model\Model;
use PayPal\Api\Payee;
use PayPal\Api\Payer;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Details;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Payment;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Common\PayPalModel;
use PayPal\Rest\ApiContext;
use PayPal\Api\PaymentExecution;

class PaypalHandler
{

    const CURRENCY = 'USD';//貨幣單位
    protected $Paypal;

    public function __construct(int $white/*白名單*/ = 0)
    {
        TraceHandler::sendAlarm2DingTalk("white:{$white}");
        $paypalSandboxButton = SystemLogic::cacheDetail('whitelist', 'paypal_sandbox_button');
        $clientId = ($white || $paypalSandboxButton) ? config('system.payment.paypal.sandbox.client_id') : config('system.payment.paypal.official.client_id');
        $secret = ($white || $paypalSandboxButton) ? config('system.payment.paypal.sandbox.secret') : config('system.payment.paypal.official.secret');
        $this->Paypal = new ApiContext(
            new OAuthTokenCredential(
                $clientId,
                $secret
            )
        );
        //真實支付
        if($white){
        }else{
            if(!$paypalSandboxButton){
                $this->Paypal->setConfig(
                    array(
                        'mode' => 'live',
                    )
                );
            }
        }
    }

    /**
     * 拉取支付鏈接
     * @param
     * $product 商品
     * $price 價格
     * $shipping 運費
     * $description 描述
     */
    public function pullPaymentLink(
        array $payload,
        int $accountId,
        string $product,
        float $price,
        array $description,
        array $callbackParameter,
        string $frontendCallback,
        int $shipping,
        string $topupLevelListBackup,
        int $white = 0
    ): array
    {
        try {
            $orderId = md5("{$payload['client_id']}_{$accountId}_{$price}_" . microtime(true)) ;
            $backendCallback = config('system.payment.paypal.backend_callback');
            $symbol = strpos($frontendCallback,'?') === false ? '?' : '&';
            $cancelUrl = "{$frontendCallback}{$symbol}order_id={$orderId}&action=cancel&order_status=2";
            $paypal = $this->Paypal;
            $total = $price + $shipping;//總價
            $payer = new Payer();
            $payer->setPaymentMethod('paypal');
            $item = new Item();
            $item->setName($product)->setCurrency(self::CURRENCY)->setQuantity(1)->setPrice($price);
            $itemList = new ItemList();
            $itemList->setItems([$item]);
            $details = new Details();
            $details->setShipping($shipping)->setSubtotal($price);
            $amount = new Amount();
            $amount->setCurrency(self::CURRENCY)->setTotal($total)->setDetails($details);
            $payee = new Payee();
            $email = ($white || SystemLogic::cacheDetail('whitelist', 'paypal_sandbox_button')) ? config('system.payment.paypal.sandbox.payee') : config('system.payment.paypal.official.payee');
            $payee->setEmail($email);
            $transaction = new Transaction();
            $transaction->setAmount($amount)->setItemList($itemList)->setDescription(json_encode($description))->setPayee($payee)->setInvoiceNumber(uniqid());
            $redirectUrls = new RedirectUrls();
            $redirectUrls->setReturnUrl( "{$backendCallback}?action=true&device_id={$payload['device_id']}&account_id={$accountId}&order_id={$orderId}&goods_id={$callbackParameter['goods_id']}&topup_once_id={$callbackParameter['topup_once_id']}&referer_novel_id={$callbackParameter['referer_novel_id']}&referer_chapter_id={$callbackParameter['referer_chapter_id']}&referer_chapter_index={$callbackParameter['referer_chapter_index']}")->setCancelUrl($cancelUrl);
            $payment = new Payment();
            $payment->setIntent('sale')->setPayer($payer)->setRedirectUrls($redirectUrls)->setTransactions([$transaction]);
            $payment->create($paypal);
            $link = $payment->getApprovalLink();
            //topup stat/PREPARE[START]
            if($callbackParameter['referer_novel_id']) $novelDetail = (new Novel())->one(['id' => $callbackParameter['referer_novel_id']], ['id', 'novel_title']);
            [$unitTopupLevelDetail,,] = (new FinanceLogic())->unitTopupLevelDetail($payload, $accountId, $callbackParameter['goods_id'], $topupLevelListBackup);
            $property = [//兼容埋點字段
                'operation_id' => FinanceEnum::TOPUP_STAT_OPERATION_ID['TAP_LEVEL'],
                'trace_id' => $orderId,
                'top_up_amount' => $price,
                'top_up_coins' => floatval($unitTopupLevelDetail['total'] ?? 0),
                'novel_name' => $novelDetail['novel_title'] ?? '',
                'novel_id' => intval($callbackParameter['referer_novel_id'] ?? 0),
                'novel_chapter_id' => intval($callbackParameter['referer_chapter_id'] ?? 0),
                'novel_chapter_number' => intval($callbackParameter['referer_chapter_index'] ?? 0),
                'frontend_callback' => $frontendCallback,
                'topup_level_list_backup' => $topupLevelListBackup,
            ];
            make(FinanceLogic::class)->topupStatHandler($payload, $accountId, $property);
            //topup stat/PREPARE[END]
            return [
                'link' => $link,
                'order_id' => $orderId,
            ];
        } catch (\Throwable $e) {
            xdebug($e);
            TraceHandler::sendAlarm2DingTalk($e);
            throw $e;
        }
    }

    public function checkout(string $paymentId): array
    {
        //手動扣費[START]
        try{
            $payment = Payment::get($paymentId, $this->Paypal);
            $execution = new PaymentExecution();
            $execution->setPayerId($payment->getPayer()->getPayerInfo()->getPayerId());
            $payment->execute($execution, $this->Paypal);
        }catch (\Throwable $e){
            TraceHandler::sendAlarm2DingTalk($e);
            throw $e;
        }
        //手動扣費[END]
        $return = Payment::get($paymentId, $this->Paypal)->toArray();
        TraceHandler::sendAlarm2DingTalk(json_encode($return));
        return $return;
    }

    public function pullPaymentDetail(string $paymentId): array
    {
        $return = Payment::get($paymentId, $this->Paypal)->toArray();
        //TraceHandler::sendAlarm2DingTalk(json_encode($return));
        return $return;
    }

}
