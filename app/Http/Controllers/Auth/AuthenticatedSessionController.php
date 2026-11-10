<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\CartController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Exibe a tela de login de clientes.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Exibe a tela dedicada de login administrativo (S11).
     */
    public function createAdmin(): View
    {
        return view('auth.admin-login');
    }

    /**
     * Autentica o usuário e faz merge do carrinho/favoritos da sessão com o banco (F3).
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate(false);

        $request->session()->regenerate();

        CartController::mergeSessionIntoDatabase($request);

        if ($request->user()?->is_admin && $request->input('user_type') === 'administrador') {
            return redirect()->intended(route('admin.recharges.index'));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Autentica exclusivamente administradores na rota dedicada /admin/login (S11).
     */
    public function storeAdmin(LoginRequest $request): RedirectResponse
    {
        $request->authenticate(true);

        $request->session()->regenerate();

        CartController::mergeSessionIntoDatabase($request);

        return redirect()->intended(route('admin.recharges.index'));
    }

    /**
     * Encerra a sessão autenticada.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
