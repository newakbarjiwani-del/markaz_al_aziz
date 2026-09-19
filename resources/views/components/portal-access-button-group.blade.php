@props([
    'label',
    'hasActiveToken' => false,
    'generateUrl',
    'waUrl',
    'copyUrl',
    'revokeUrl',
    'revokeRole' => null,
    'layout' => 'compact',
    'waIcon' => 'brand-whatsapp',
    'waTitle' => 'Kirim link via WhatsApp',
    'generateTitle' => 'Buat token login portal',
])

@if($layout === 'menu')
    <p class="row-action-menu-heading">{{ $label }}</p>

    @if($hasActiveToken)
        <button type="button"
                class="row-action-menu-item row-action-menu-item--wa"
                role="menuitem"
                title="{{ $waTitle }}"
                data-portal-wa
                data-portal-url="{{ $waUrl }}">
            <x-icon :name="$waIcon" size="sm" />
            <span>Kirim link WhatsApp</span>
        </button>
        <button type="button"
                class="row-action-menu-item"
                role="menuitem"
                title="Salin link login portal"
                data-portal-copy
                data-portal-url="{{ $copyUrl }}">
            <x-icon name="clipboard-copy" size="sm" />
            <span>Salin link login</span>
        </button>
        <button type="button"
                class="row-action-menu-item row-action-menu-item--revoke"
                role="menuitem"
                title="Cabut token portal"
                data-portal-revoke
                @if($revokeRole) data-portal-role="{{ $revokeRole }}" @endif
                data-portal-url="{{ $revokeUrl }}">
            <x-icon name="key-off" size="sm" />
            <span>Cabut token</span>
        </button>
    @else
        <button type="button"
                class="row-action-menu-item row-action-menu-item--generate"
                role="menuitem"
                title="{{ $generateTitle }}"
                data-portal-generate
                data-portal-url="{{ $generateUrl }}">
            <x-icon name="key" size="sm" />
            <span>Buat token login</span>
        </button>
    @endif
@else
    <div class="portal-access-group" aria-label="{{ $label }}">
        <span class="portal-access-label">{{ $label }}</span>

        @if($hasActiveToken)
            <button type="button"
                    class="btn-action btn-action-wa"
                    title="{{ $waTitle }}"
                    data-portal-wa
                    data-portal-url="{{ $waUrl }}">
                <x-icon :name="$waIcon" size="sm" />
                <span class="btn-action-label">WA</span>
            </button>
            <button type="button"
                    class="btn-action btn-action-copy"
                    title="Salin link login portal"
                    data-portal-copy
                    data-portal-url="{{ $copyUrl }}">
                <x-icon name="clipboard-copy" size="sm" />
                <span class="btn-action-label">Salin</span>
            </button>
            <button type="button"
                    class="btn-action btn-action-revoke"
                    title="Cabut token portal"
                    data-portal-revoke
                    @if($revokeRole) data-portal-role="{{ $revokeRole }}" @endif
                    data-portal-url="{{ $revokeUrl }}">
                <x-icon name="key-off" size="sm" />
                <span class="btn-action-label">Cabut</span>
            </button>
        @else
            <button type="button"
                    class="btn-action btn-action-generate"
                    title="{{ $generateTitle }}"
                    data-portal-generate
                    data-portal-url="{{ $generateUrl }}">
                <x-icon name="key" size="sm" />
                <span class="btn-action-label">Buat Token</span>
            </button>
        @endif
    </div>
@endif
