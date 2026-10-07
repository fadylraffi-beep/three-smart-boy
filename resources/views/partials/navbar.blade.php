<nav class="navbar navbar-expand-lg bg-white shadow-sm mb-4">
  <div class="container">
    
    <a class="navbar-brand fw-bold text-primary" href="{{ route('home') }}">
        Three Musketeer Blog
    </a>
    
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('home') ? 'active fw-semibold text-primary' : '' }}" href="{{ route('home') }}">
              Home
          </a>
        </li>
      </ul>
      
      <div class="d-flex align-items-center gap-3 mt-3 mt-lg-0">
          
        @auth
            <span class="text-muted d-none d-lg-block">Halo, {{ Auth::user()->name }}</span>
        @endauth

        <!-- Form Logout wajib menggunakan POST dan @csrf di Laravel -->
        {{-- <form action="{{ route('logout') }}" method="POST" class="m-0">
          @csrf
          <button class="btn btn-outline-danger btn-sm px-4 rounded-pill" type="submit">
              Logout
          </button>
        </form> --}}
	<a href="{{ route('saml2_login', 'keycloak') }}" class="btn btn-primary">Login via Keycloak</a>
        
      </div>
    </div>
    
  </div>
</nav>
