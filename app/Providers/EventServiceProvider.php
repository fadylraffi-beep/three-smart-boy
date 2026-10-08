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

    public function boot(): void
    {
        Event::listen(Saml2LoginEvent::class, function (Saml2LoginEvent $event): void {
            $samlUser = $event->getSaml2User();
            $attributes = $samlUser->getAttributes();
            $keycloakId = $samlUser->getUserId();
            $email = $this->attribute($attributes, 'email') ?: $keycloakId . '@sso.local';
            $name = $this->attribute($attributes, 'name')
                ?: trim(implode(' ', array_filter([
                    $this->attribute($attributes, 'given_name'),
                    $this->attribute($attributes, 'family_name'),
                ])))
                ?: $this->attribute($attributes, 'preferred_username')
                ?: $email;

            $laravelUser = User::where('keycloak_id', $keycloakId)
                ->orWhere('email', $email)
                ->first();

            if (! $laravelUser) {
                $laravelUser = new User([
                    'password' => bcrypt(Str::random(32)),
                ]);
            }

            $laravelUser->keycloak_id = $keycloakId;
            $laravelUser->email = $email;
            $laravelUser->name = $name;
            $laravelUser->save();

            Auth::login($laravelUser, true);
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
