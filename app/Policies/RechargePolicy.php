<?php

namespace App\Policies;

use App\Models\Recharge;
use App\Models\User;

class RechargePolicy
{
    /**
     * Apenas o titular do cartão ou um administrador podem visualizar o comprovante.
     */
    public function viewReceipt(User $user, Recharge $recharge): bool
    {
        if ($user->is_admin) {
            return true;
        }

        return (int) $recharge->virtualCard?->user_id === (int) $user->id;
    }

    /**
     * Apenas um administrador que NÃO seja o titular do cartão pode aprovar ou recusar a recarga.
     */
    public function review(User $user, Recharge $recharge): bool
    {
        if (! $user->is_admin) {
            return false;
        }

        return (int) $recharge->virtualCard?->user_id !== (int) $user->id;
    }
}
