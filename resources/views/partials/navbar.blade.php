<nav class="navbar navbar-expand-lg bg-white shadow-sm mb-4">
    <div class="container">

        <a class="navbar-brand fw-bold text-primary" href="{{ route('home') }}">
            Three Musketeer Blog
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse"
            data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
            aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('home') ? 'active fw-semibold text-primary' : '' }}"
                        href="{{ route('home') }}">
                        Home
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                @auth
                    <span class="text-muted me-2">
                        Halo, <strong>{{ Auth::user()->name }}</strong>
                    </span>
                    <a href="{{ route('dashboard') }}"
                        class="btn btn-outline-primary {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('saml2_logout', ['idpName' => 'keycloak', 'returnTo' => route('home')]) }}" class="btn btn-outline-secondary">
                        Logout
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary">Login via Keycloak</a>
                @endauth
            </div>
        </div>

    </div>
</nav>
