<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ShippingCalculator
{
    /**
     * Calcula o frete simulado com base no CEP e no subtotal em centavos (F2).
     * Compras com subtotal líquido >= config('digitalcard.free_shipping_min_total') têm frete grátis.
     *
     * @return array{cost_cents: int, cost: float, is_free: bool, estimated_days: int, region: string, uf: string}
     */
    public function calculate(string $cep, int $merchandiseTotalCents): array
    {
        $cleanCep = preg_replace('/\D/', '', $cep) ?: '57000000';
        $firstDigit = (int) substr(str_pad($cleanCep, 8, '0'), 0, 1);

        $freeShippingMinCents = CartPricing::toCents((float) config('digitalcard.free_shipping_min_total', 299.00));

        [$region, $uf, $baseCostCents, $days] = match ($firstDigit) {
            0, 1 => ['Sudeste (SP)', 'SP', 1990, 3],
            2 => ['Sudeste (RJ/ES)', 'RJ', 2290, 4],
            3 => ['Sudeste (MG)', 'MG', 2290, 4],
            4 => ['Nordeste (BA/SE)', 'BA', 1890, 3],
            5 => ['Nordeste (PE/AL/PB/RN)', 'AL', 1490, 2],
            6 => ['Norte/Nordeste (CE/PI/MA/PA/AM)', 'CE', 2690, 5],
            7 => ['Centro-Oeste/Norte (DF/GO/MT/MS/TO/RO/AC)', 'DF', 2490, 4],
            8, 9 => ['Sul (PR/SC/RS)', 'PR', 2590, 5],
            default => ['Nacional', 'BR', 2490, 4],
        };

        $isFree = $merchandiseTotalCents >= $freeShippingMinCents;
        $finalCostCents = $isFree ? 0 : $baseCostCents;

        return [
            'cost_cents' => $finalCostCents,
            'cost' => CartPricing::toDecimal($finalCostCents),
            'is_free' => $isFree,
            'estimated_days' => $days,
            'region' => $region,
            'uf' => $uf,
        ];
    }

    /**
     * Consulta endereço no ViaCEP com cache de 24h e fallback offline determinístico (F2).
     *
     * @return array{cep: string, street: string, neighborhood: string, city: string, state: string}
     */
    public function lookupCep(string $cep): array
    {
        $clean = substr(preg_replace('/\D/', '', $cep) ?: '', 0, 8);
        $formatted = strlen($clean) === 8
            ? substr($clean, 0, 5).'-'.substr($clean, 5, 3)
            : $cep;

        if (strlen($clean) !== 8) {
            return [
                'cep' => $formatted,
                'street' => 'Endereço informado manualmente',
                'neighborhood' => 'Centro',
                'city' => 'Maceió',
                'state' => 'AL',
            ];
        }

        return Cache::remember("viacep:{$clean}", 86400, function () use ($clean, $formatted) {
            if (! app()->environment('testing')) {
                try {
                    $response = Http::timeout(2)->acceptJson()->get("https://viacep.com.br/ws/{$clean}/json/");
                    if ($response->successful() && ! $response->json('erro')) {
                        $data = $response->json();

                        return [
                            'cep' => $data['cep'] ?? $formatted,
                            'street' => $data['logradouro'] ?: 'Logradouro geral do CEP',
                            'neighborhood' => $data['bairro'] ?: 'Centro',
                            'city' => $data['localidade'] ?: 'Maceió',
                            'state' => $data['uf'] ?: 'AL',
                        ];
                    }
                } catch (\Throwable) {
                    // Fallback offline para ambientes acadêmicos sem saída de rede
                }
            }

            $quote = $this->calculate($clean, 0);

            return [
                'cep' => $formatted,
                'street' => 'Avenida Principal (Consulta Simulada)',
                'neighborhood' => 'Centro',
                'city' => $quote['uf'] === 'AL' ? 'Rio Largo' : 'Capital ('.$quote['uf'].')',
                'state' => $quote['uf'],
            ];
        });
    }
}
