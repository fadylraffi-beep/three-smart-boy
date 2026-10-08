<?php

namespace App\Providers;

use Aacotroneo\Saml2\Events\Saml2LoginEvent;
use Aacotroneo\Saml2\Events\Saml2LogoutEvent;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
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
            $keycloakId = trim((string) $samlUser->getUserId());

            if ($keycloakId === '') {
                throw new \RuntimeException('Keycloak did not provide a SAML NameID.');
            }

            $email = $this->attribute($attributes, 'email') ?: $keycloakId . '@sso.local';
            $name = $this->attribute($attributes, 'name')
                ?: trim(implode(' ', array_filter([
                    $this->attribute($attributes, 'given_name'),
                    $this->attribute($attributes, 'family_name'),
                ])))
                ?: $this->attribute($attributes, 'preferred_username')
                ?: $email;

            try {
                $laravelUser = DB::transaction(function () use ($keycloakId, $email, $name): User {
                    $laravelUser = User::where('keycloak_id', $keycloakId)
                        ->orWhere('email', $email)
                        ->lockForUpdate()
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

                    return $laravelUser;
                });
            } catch (\Illuminate\Database\QueryException $exception) {
                // Another callback may have created this user between the lookup and insert.
                if ($exception->getCode() !== '23000') {
                    throw $exception;
                }

                $laravelUser = User::where('keycloak_id', $keycloakId)
                    ->orWhere('email', $email)
                    ->firstOrFail();
            }

            Auth::login($laravelUser, true);
        });

        Event::listen(Saml2LogoutEvent::class, function (Saml2LogoutEvent $event) {
            Auth::logout();
            Session::flush();
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
