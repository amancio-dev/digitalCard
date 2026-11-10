<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\AuditLog;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Bloqueia exclusão se houver saldo > 0 ou pedido em aberto, e usa SoftDeletes/anonimização preservando histórico financeiro.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $card = $user->virtualCard;

        if ($card && (float) $card->balance > 0) {
            throw ValidationException::withMessages([
                'balance' => 'Não é possível excluir a conta enquanto houver saldo disponível no Cartão Virtual (R$ '.number_format((float) $card->balance, 2, ',', '.').'). Utilize o saldo antes de encerrar a conta.',
            ])->errorBag('userDeletion');
        }

        $hasOpenOrders = $user->orders()
            ->whereIn('status', [
                Order::STATUS_REQUESTED,
                Order::STATUS_SEPARATING,
                Order::STATUS_SHIPPED,
            ])
            ->exists();

        if ($hasOpenOrders) {
            throw ValidationException::withMessages([
                'orders' => 'Não é possível excluir a conta enquanto houver pedidos em andamento.',
            ])->errorBag('userDeletion');
        }

        Auth::logout();

        DB::transaction(function () use ($user, $card) {
            AuditLog::record(
                'user.account_deleted',
                $user,
                ['email' => $user->email],
                ['anonymized' => true],
                $user->id
            );

            if ($card) {
                $card->update(['is_active' => false]);
                $card->delete(); // SoftDelete preserva transações e recargas
            }

            $user->forceFill([
                'name' => 'Usuário Removido #'.$user->id,
                'email' => "deleted+{$user->id}@digitalcard.local",
            ])->save();

            $user->delete(); // SoftDelete
        });

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
