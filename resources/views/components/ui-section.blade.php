@props(['id', 'title', 'description' => null])

<section id="{{ $id }}" class="ui-section scroll-mt-24">
    <div class="mb-4 border-b border-slate-200 pb-3 dark:border-slate-800">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $title }}</h2>
        @if($description)
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>
        @endif
    </div>
    {{ $slot }}
</section>
