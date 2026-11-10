<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Models\AuditLog;
use App\Models\CardTransaction;
use App\Models\Recharge;
use App\Models\VirtualCard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SimulatedPixGateway implements PaymentGateway
{
    public function createPixCharge(VirtualCard $card, float $amount): Recharge
    {
        $normalizedAmount = round($amount, 2);
        $txid = 'DCPX'.strtoupper(Str::random(21)); // 25 caracteres alfanuméricos padrão BACEN
        $payload = self::generateBrCode(
            pixKey: (string) config('digitalcard.pix.key', 'simulacao-pix@digitalcard.edu.br'),
            merchantName: (string) config('digitalcard.pix.merchant_name', 'DIGITALCARD ACADEMICO'),
            merchantCity: (string) config('digitalcard.pix.merchant_city', 'MACEIO'),
            amount: $normalizedAmount,
            txid: $txid,
        );

        $recharge = $card->recharges()->create([
            'payment_method' => 'pix_dynamic',
            'txid' => $txid,
            'pix_payload' => $payload,
            'expires_at' => now()->addMinutes((int) config('digitalcard.pix.expiration_minutes', 30)),
            'amount' => $normalizedAmount,
            'receipt_path' => null,
            'receipt_hash' => null,
            'status' => Recharge::STATUS_PENDING,
        ]);

        AuditLog::record(
            'pix.charge_created',
            $recharge,
            null,
            ['txid' => $txid, 'amount' => $normalizedAmount],
            $card->user_id
        );

        return $recharge;
    }

    /**
     * Processa o webhook PIX de maneira estritamente idempotente (F1).
     */
    public function confirmPixWebhook(string $txid, ?int $triggeredByUserId = null): Recharge
    {
        return DB::transaction(function () use ($txid, $triggeredByUserId) {
            $recharge = Recharge::where('txid', $txid)->lockForUpdate()->firstOrFail();

            // Idempotência: se já foi aprovado anteriormente, retorna sem creditar em duplicidade
            if ($recharge->status === Recharge::STATUS_APPROVED) {
                return $recharge;
            }

            if ($recharge->status !== Recharge::STATUS_PENDING) {
                abort(400, 'Esta cobrança PIX não está pendente.');
            }

            if ($recharge->expires_at && now()->gt($recharge->expires_at)) {
                abort(400, 'Esta cobrança PIX expirou.');
            }

            $card = VirtualCard::where('id', $recharge->virtual_card_id)->lockForUpdate()->firstOrFail();
            $creditAmount = round((float) $recharge->amount, 2);
            $oldBalance = round((float) $card->balance, 2);
            $newBalance = round($oldBalance + $creditAmount, 2);

            $card->update(['balance' => $newBalance]);

            CardTransaction::create([
                'virtual_card_id' => $card->id,
                'type' => CardTransaction::TYPE_RECHARGE,
                'amount' => $creditAmount,
                'balance_after' => $newBalance,
                'description' => "Recarga PIX Simulada ({$txid})",
            ]);

            $recharge->update([
                'status' => Recharge::STATUS_APPROVED,
                'approved_amount' => $creditAmount,
                'admin_notes' => 'Liquidação automática via Webhook PIX Simulado (Ambiente Acadêmico)',
                'reviewed_at' => now(),
            ]);

            AuditLog::record(
                'pix.webhook_confirmed',
                $recharge,
                ['status' => Recharge::STATUS_PENDING, 'card_balance' => $oldBalance],
                ['status' => Recharge::STATUS_APPROVED, 'txid' => $txid, 'card_balance' => $newBalance],
                $triggeredByUserId ?? $card->user_id
            );

            return $recharge;
        });
    }

    /**
     * Gera o payload EMVCo QRCPS-MPM (BR Code PIX) com cálculo real do CRC16-CCITT (0xFFFF, polinômio 0x1021).
     */
    public static function generateBrCode(
        string $pixKey,
        string $merchantName,
        string $merchantCity,
        float $amount,
        string $txid = '***',
    ): string {
        $cleanName = substr(preg_replace('/[^A-Za-z0-9 ]/', '', Str::ascii($merchantName)) ?: 'DIGITALCARD', 0, 25);
        $cleanCity = substr(preg_replace('/[^A-Za-z0-9 ]/', '', Str::ascii($merchantCity)) ?: 'MACEIO', 0, 15);
        $cleanTxid = substr(preg_replace('/[^A-Za-z0-9*]/', '', $txid) ?: '***', 0, 25);

        $gui = self::emvField('00', 'br.gov.bcb.pix');
        $keyField = self::emvField('01', $pixKey);
        $merchantAccount = self::emvField('26', $gui.$keyField);

        $additionalData = self::emvField('62', self::emvField('05', $cleanTxid));

        $payloadWithoutCrc = implode('', [
            self::emvField('00', '01'), // Payload Format Indicator
            $merchantAccount,
            self::emvField('52', '0000'), // Merchant Category Code
            self::emvField('53', '986'), // Transaction Currency (BRL)
            self::emvField('54', number_format($amount, 2, '.', '')),
            self::emvField('58', 'BR'),
            self::emvField('59', $cleanName),
            self::emvField('60', $cleanCity),
            $additionalData,
            '6304', // CRC16 ID + Length
        ]);

        return $payloadWithoutCrc.self::crc16Ccitt($payloadWithoutCrc);
    }

    /**
     * Calcula o CRC16-CCITT (polinômio 0x1021, valor inicial 0xFFFF) exigido pelo padrão PIX BACEN.
     */
    public static function crc16Ccitt(string $payload): string
    {
        $crc = 0xFFFF;
        $length = strlen($payload);

        for ($i = 0; $i < $length; $i++) {
            $crc ^= (ord($payload[$i]) << 8);
            for ($bit = 0; $bit < 8; $bit++) {
                if (($crc & 0x8000) !== 0) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    private static function emvField(string $id, string $value): string
    {
        $len = str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT);

        return $id.$len.$value;
    }
}
