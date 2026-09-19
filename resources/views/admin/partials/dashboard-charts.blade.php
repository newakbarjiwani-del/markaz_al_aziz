@if(!empty($charts))
    <div class="dashboard-charts">
        @foreach($charts as $chart)
            <x-dashboard-chart :id="$chart['id']" :title="$chart['title']" />
        @endforeach
    </div>

    @once
        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
            <script src="{{ asset('js/dashboard-charts.js') }}?v=2"></script>
        @endpush
    @endonce

    @push('scripts')
        <script id="dashboard-charts-data" type="application/json">@json($charts)</script>
    @endpush
@endif
