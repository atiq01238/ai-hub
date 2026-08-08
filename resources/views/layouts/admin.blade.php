<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Dashboard') · AI Intelligence Admin</title>

{{-- Fonts --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">

{{-- Bootstrap (grid/utilities/modal JS only — visuals are overridden by our design system) --}}
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
{{-- Lucide icons --}}
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>

<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@stack('styles')
</head>
<body>

<div class="app-shell">
    @include('partials.sidebar')

    <div class="main-col">
        @include('partials.header')

        <main class="content">
            @yield('content')
        </main>
    </div>
</div>

@include('partials.preview-modal')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  lucide.createIcons();

  // Sidebar collapse toggle
  document.getElementById('sidebarToggle')?.addEventListener('click', function(){
    document.getElementById('sidebar').classList.toggle('is-collapsed');
    document.getElementById('sidebar').classList.toggle('is-mobile-open');
  });

  // Simple dropdown toggler used across header menus
  document.querySelectorAll('[data-dropdown-toggle]').forEach(function(btn){
    btn.addEventListener('click', function(e){
      e.stopPropagation();
      var menu = document.querySelector(btn.getAttribute('data-dropdown-toggle'));
      document.querySelectorAll('.dropdown-menu-custom.is-open').forEach(function(m){ if(m!==menu) m.classList.remove('is-open'); });
      menu.classList.toggle('is-open');
    });
  });
  document.addEventListener('click', function(){
    document.querySelectorAll('.dropdown-menu-custom.is-open').forEach(function(m){ m.classList.remove('is-open'); });
  });
</script>
@stack('scripts')
</body>
</html>
