@php
    $items = $bottomNavMenu ?? [];

    $isBottomNavActive = function (array $item): bool {
        $matchesPatterns = function (string|array $patterns): bool {
            $list = is_array($patterns)
                ? $patterns
                : preg_split('/\s*\|\s*/', $patterns);

            foreach ($list as $pattern) {
                if (is_string($pattern) && $pattern !== '' && request()->routeIs($pattern)) {
                    return true;
                }
            }

            return false;
        };

        if (isset($item['active'])) {
            return $matchesPatterns($item['active']);
        }

        if (! isset($item['route'])) {
            return false;
        }

        $route = $item['route'];
        $pattern = str_ends_with($route, '.index')
            ? Str::beforeLast($route, '.').'.*'
            : $route;

        return request()->routeIs($route) || request()->routeIs($pattern);
    };
@endphp

@if($items !== [])
    <nav class="mobile-bottom-nav lg:hidden" aria-label="Navigasi utama">
        <div class="mobile-bottom-nav__inner">
            @foreach($items as $item)
                @php $active = ($item['action'] ?? null) !== 'sidebar' && $isBottomNavActive($item); @endphp

                @if(($item['action'] ?? null) === 'sidebar')
                    <button type="button"
                            data-mobile-sidebar-toggle
                            class="mobile-bottom-nav__item"
                            aria-label="Buka menu lengkap">
                        <span class="mobile-bottom-nav__icon-wrap" aria-hidden="true">
                            <x-icon :name="$item['icon']" size="md" />
                        </span>
                        <span class="mobile-bottom-nav__label">{{ $item['label'] }}</span>
                    </button>
                @else
                    <a href="{{ route($item['route']) }}"
                       @if($active) aria-current="page" @endif
                       class="mobile-bottom-nav__item {{ $active ? 'is-active' : '' }}">
                        <span class="mobile-bottom-nav__icon-wrap" aria-hidden="true">
                            <x-icon :name="$item['icon']" size="md" />
                        </span>
                        <span class="mobile-bottom-nav__label">{{ $item['label'] }}</span>
                    </a>
                @endif
            @endforeach
        </div>
    </nav>
@endif
