<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Str;

class SsoController extends Controller
{
    // 1. Arahkan user ke halaman login Keycloak
    public function redirect()
    {
        $query = http_build_query([
            'client_id' => env('KEYCLOAK_CLIENT_ID'),
            'redirect_uri' => env('KEYCLOAK_REDIRECT_URI'),
            'response_type' => 'code',
            'scope' => 'openid profile email',
        ]);

        return redirect(env('KEYCLOAK_BASE_URL') . '/protocol/openid-connect/auth?' . $query);
    }

    // 2. Tangkap kode balasan dan tukar dengan data User
    public function callback(Request $request)
    {
        // Jika user membatalkan login
        if ($request->has('error')) {
            return redirect('/')->with('error', 'Login dibatalkan.');
        }

        // Tukar otorisasi 'code' dengan Access Token & ID Token ke Keycloak
        $response = Http::asForm()->post(env('KEYCLOAK_BASE_URL') . '/protocol/openid-connect/token', [
            'grant_type' => 'authorization_code',
            'client_id' => env('KEYCLOAK_CLIENT_ID'),
            'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
            'redirect_uri' => env('KEYCLOAK_REDIRECT_URI'),
            'code' => $request->code,
        ]);

        if ($response->failed()) {
            return redirect('/')->with('error', 'Gagal memverifikasi token Keycloak.');
        }

        $tokens = $response->json();

        // Ambil data profil user menggunakan Access Token
        $userResponse = Http::withToken($tokens['access_token'])
                            ->get(env('KEYCLOAK_BASE_URL') . '/protocol/openid-connect/userinfo');
        
        if ($userResponse->failed()) {
            return redirect('/')->with('error', 'Gagal mengambil data profil.');
        }

        $keycloakUser = $userResponse->json();

        // Cari atau buat user di database lokal (email sebagai acuan utama)
        $user = User::firstOrCreate(
            ['email' => $keycloakUser['email']],
            [
                'name' => $keycloakUser['preferred_username'] ?? $keycloakUser['name'],
                'password' => bcrypt(Str::random(16))
            ]
        );

        // Simpan id_token ke session lokal (Syarat wajib untuk proses logout Keycloak versi baru)
        session(['keycloak_id_token' => $tokens['id_token']]);

        // Login ke session Laravel
        Auth::login($user);

        return redirect()->route('dashboard');
    }

    // 3. Logout dari Laravel dan Keycloak sekaligus
    public function logout()
    {
        $idToken = session('keycloak_id_token');
        
        Auth::logout();
        session()->flush();

        // Arahkan ke endpoint logout Keycloak sambil membawa ID Token sebagai bukti
        $query = http_build_query([
            'id_token_hint' => $idToken,
            'post_logout_redirect_uri' => env('APP_URL'),
        ]);

        return redirect(env('KEYCLOAK_BASE_URL') . '/protocol/openid-connect/logout?' . $query);
    }
}
