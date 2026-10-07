<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

use Aacotroneo\Saml2\Events\Saml2LoginEvent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\User;

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
	   Event::listen(Saml2LoginEvent::class, function (Saml2LoginEvent $event) {
	      $user = $event->getSaml2User();

	      // Ambil email dari Keycloak
	      $email = $user->getUserId();

	      // Cari atau buat user baru di database Laravel lokal
	      $laravelUser = User::firstOrCreate(
	            ['email' => $email],
	            [
	               'name' => $email, // Default nama sesuai email
	               'password' => bcrypt(Str::random(16)) // Menggunakan Str::random() yang valid
	            ]
	      );

	      // Login ke sesi Laravel
	      Auth::login($laravelUser);
	   });
	}
}
