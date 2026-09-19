@php
    $menu = $navigationMenu ?? [];

    $isRouteActive = function (string $route, array $params = [], string|array|null $active = null, ?string $hash = null): bool {
        if ($hash !== null) {
            return false;
        }

        $patterns = $active !== null
            ? (is_array($active) ? $active : [$active])
            : [
                $route,
                str_ends_with($route, '.index')
                    ? Str::beforeLast($route, '.').'.*'
                    : $route,
            ];

        $matched = false;
        foreach ($patterns as $pattern) {
            if (request()->routeIs($pattern)) {
                $matched = true;
                break;
            }
        }

        if (! $matched) {
            return false;
        }

        if ($params === []) {
            return true;
        }

        foreach ($params as $key => $value) {
            if ((string) request()->route($key) !== (string) $value) {
                return false;
            }
        }

        return true;
    };

    $isGroupActive = function (array $children) use ($isRouteActive): bool {
        foreach ($children as $child) {
            if ($isRouteActive($child['route'], $child['params'] ?? [], $child['active'] ?? null, $child['hash'] ?? null)) {
                return true;
            }
        }

        return false;
    };

    $menuRoute = function (array $item): string {
        $url = isset($item['params'])
            ? route($item['route'], $item['params'])
            : route($item['route']);

        if (isset($item['hash'])) {
            $url .= '#'.ltrim($item['hash'], '#');
        }

        return $url;
    };
@endphp

<aside id="sidebar"
       class="sidebar app-sidebar fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col lg:translate-x-0">
    <div class="sidebar-header flex h-16 shrink-0 items-center border-b px-3">
        <a href="{{ route($navigationHomeRoute ?? 'login') }}"
           class="sidebar-brand flex min-w-0 flex-1 items-center justify-center lg:justify-start"
           title="{{ config('app.name') }}">
            <x-app-logo size="sm" class="sidebar-brand-logo" />
        </a>
    </div>

    <nav class="sidebar-nav flex-1 overflow-x-hidden overflow-y-auto p-2" aria-label="Menu utama">
        @foreach($menu as $item)
            @if(($item['type'] ?? null) === 'divider')
                <div class="sidebar-section-divider" data-sidebar-label>
                    <span class="sidebar-section-divider__label">{{ $item['label'] }}</span>
                </div>
            @elseif(isset($item['children']))
                @php
                    $subId = 'submenu-'.Str::slug($item['label']);
                    $groupActive = $isGroupActive($item['children']);
                @endphp
                <div class="mb-0.5" data-sidebar-group>
                    <button type="button"
                            data-submenu-toggle="{{ $subId }}"
                            data-sidebar-tooltip="{{ $item['label'] }}"
                            aria-expanded="{{ $groupActive ? 'true' : 'false' }}"
                            class="sidebar-group-toggle {{ $groupActive ? 'has-active-child is-open' : '' }}">
                        <span class="sidebar-icon-wrap">
                            <x-icon :name="$item['icon']" class="shrink-0" size="md" />
                        </span>
                        <span data-sidebar-label class="sidebar-item-label flex-1 text-left">{{ $item['label'] }}</span>
                        <x-icon name="chevron-down" data-chevron class="sidebar-chevron shrink-0 {{ $groupActive ? 'rotate-180' : '' }}" size="sm" />
                    </button>
                    <div id="{{ $subId }}"
                         data-submenu
                         data-submenu-title="{{ $item['label'] }}"
                         class="sidebar-submenu space-y-0.5 {{ $groupActive ? '' : 'hidden' }}">
                        @foreach($item['children'] as $child)
                            @php $childActive = $isRouteActive($child['route'], $child['params'] ?? [], $child['active'] ?? null, $child['hash'] ?? null); @endphp
                            <a href="{{ $menuRoute($child) }}"
                               @if($childActive) aria-current="page" @endif
                               class="sidebar-sublink {{ $childActive ? 'is-active' : '' }}">
                                <span class="sidebar-sublink-icon" aria-hidden="true">
                                    <x-icon name="point" size="xs" class="sidebar-sublink-bullet sidebar-sublink-bullet--idle" />
                                    <x-icon name="point-filled" size="xs" class="sidebar-sublink-bullet sidebar-sublink-bullet--active" />
                                </span>
                                <span data-sidebar-label class="sidebar-sublink-label">{{ $child['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @else
                @php $linkActive = $isRouteActive($item['route'], $item['params'] ?? [], $item['active'] ?? null, $item['hash'] ?? null); @endphp
                <a href="{{ $menuRoute($item) }}"
                   data-sidebar-tooltip="{{ $item['label'] }}"
                   @if($linkActive) aria-current="page" @endif
                   class="sidebar-link mb-0.5 {{ $linkActive ? 'is-active' : '' }}">
                    <span class="sidebar-icon-wrap">
                        <x-icon :name="$item['icon']" class="shrink-0" size="md" />
                    </span>
                    <span data-sidebar-label class="sidebar-item-label">{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach
    </nav>
</aside>

<div id="sidebar-flyout" class="sidebar-flyout hidden" role="menu" aria-hidden="true"></div>
<div id="sidebar-tooltip" class="sidebar-tooltip hidden" role="tooltip" aria-hidden="true"></div>
