<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configurações de Domínio — DigitalCard (Ambiente Universitário)
    |--------------------------------------------------------------------------
    */

    'academic_notice' => 'Projeto universitário — ambiente de demonstração. Nenhum pagamento, cartão ou entrega é real.',

    // Limite máximo permitido para percentual de cashback por produto (S10)
    'max_cashback_percent' => (float) env('MAX_CASHBACK_PERCENT', 15.0),

    // Valor mínimo de subtotal (após cupom) para frete grátis em reais (F2)
    'free_shipping_min_total' => (float) env('FREE_SHIPPING_MIN_TOTAL', 299.00),

    // Configurações do Gateway PIX Simulado (F1)
    'pix' => [
        'key' => env('PIX_SIMULATED_KEY', 'simulacao-pix@digitalcard.edu.br'),
        'merchant_name' => env('PIX_MERCHANT_NAME', 'DIGITALCARD ACADEMICO'),
        'merchant_city' => env('PIX_MERCHANT_CITY', 'MACEIO'),
        'expiration_minutes' => (int) env('PIX_EXPIRATION_MINUTES', 30),
    ],
];
