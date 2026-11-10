<?php

namespace App\Contracts;

use App\Models\Recharge;
use App\Models\VirtualCard;

interface PaymentGateway
{
    /**
     * Cria uma cobrança PIX com txid único e BR Code válido (CRC16-CCITT).
     */
    public function createPixCharge(VirtualCard $card, float $amount): Recharge;

    /**
     * Processa confirmação de pagamento PIX de forma idempotente pelo txid.
     */
    public function confirmPixWebhook(string $txid, ?int $triggeredByUserId = null): Recharge;
}
