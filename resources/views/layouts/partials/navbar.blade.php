<header class="app-header sticky top-0 z-20 flex h-16 items-center justify-between px-4 sm:px-6">
    <div class="flex items-center gap-1">
        <button id="sidebar-toggle"
                type="button"
                class="icon-btn lg:hidden"
                aria-label="Buka menu"
                title="Buka menu">
            <x-icon name="menu-2" size="lg" />
        </button>
        <button id="sidebar-collapse"
                type="button"
                class="icon-btn hidden lg:inline-flex"
                aria-expanded="true"
                aria-controls="sidebar"
                aria-label="Ciutkan sidebar"
                title="Ciutkan sidebar">
            <span class="sidebar-collapse-icon" aria-hidden="true">
                <x-icon name="layout-sidebar-left-collapse" size="lg" />
            </span>
        </button>
    </div>

    <div class="flex items-center gap-2">
        <button type="button" data-theme-toggle class="icon-btn" title="Toggle tema">
            <span class="hidden dark:inline"><x-icon name="sun" size="md" /></span>
            <span class="dark:hidden"><x-icon name="moon" size="md" /></span>
        </button>

        <a href="{{ route('profile.show') }}"
           class="hidden items-center gap-2 rounded-lg px-2 py-1.5 text-right transition hover:bg-slate-100 dark:hover:bg-slate-800 sm:flex"
           title="Profil Akun">
            <div>
                <p class="text-sm font-semibold text-secondary">{{ auth()->user()->name }}</p>
                <p class="text-xs text-muted">{{ auth()->user()->getRoleNames()->first() }}</p>
            </div>
        </a>

        <a href="{{ route('profile.show') }}"
           class="icon-btn sm:hidden"
           title="Profil Akun"
           aria-label="Profil Akun">
            <x-icon name="user" size="md" />
        </a>

        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn-secondary text-xs">
                <x-icon name="logout" size="sm" class="sm:mr-1" />
                <span class="hidden sm:inline">Keluar</span>
            </button>
        </form>
    </div>
</header>
