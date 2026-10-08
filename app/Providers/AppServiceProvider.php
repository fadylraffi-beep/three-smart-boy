<?php

namespace App\Providers;

use Aacotroneo\Saml2\Events\Saml2LoginEvent;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        Event::listen(Saml2LoginEvent::class, function (Saml2LoginEvent $event) {
            $user = $event->getSaml2User();

            // 1. Ambil data yang dikirim Keycloak
            // Perhatikan: nama atribut ('email', 'name') bergantung pada mapper di Keycloak
            $email = $user->getAttribute('email') ? $user->getAttribute('email')[0] : $user->getUserId() . '@sso.local';
            $name = $user->getAttribute('name') ? $user->getAttribute('name')[0] : 'User SSO';

            // 2. Cari user di database lokal Laravel, atau buat baru jika belum ada
            $laravelUser = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => bcrypt(Str::random(16))
                ]
            );

            // 3. PENTING: Masukkan user tersebut ke dalam session Auth Laravel!
            Auth::login($laravelUser);
        });
    }
}
