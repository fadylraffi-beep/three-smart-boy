<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

use Aacotroneo\Saml2\Events\Saml2LoginEvent;
use Illuminate\Support\Facades\Event;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

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
                    'password' => bcrypt(Str::random(16)) // Password acak karena otentikasi ditangani Keycloak
                ]
            );

            // 3. PENTING: Masukkan user tersebut ke dalam session Auth Laravel!
            Auth::login($laravelUser);
        });
    }

    private function attribute(array $attributes, string $key): ?string
    {
        $value = $attributes[$key][0] ?? $attributes[$key] ?? null;

        return is_scalar($value) && trim((string) $value) !== ''
            ? trim((string) $value)
            : null;
    }
}
