<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
<link rel="stylesheet" href="{{ asset('css/datatable-chrome.css') }}?v=7">
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.12/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.12/vfs_fonts.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script src="{{ asset('js/theme.js') }}?v=4"></script>
<script src="{{ asset('js/ajax-select.js') }}?v=7"></script>
<script src="{{ asset('js/toast.js') }}?v=6"></script>
<script src="{{ asset('js/dialog.js') }}?v=8"></script>
<script src="{{ asset('js/password-toggle.js') }}?v=2"></script>
<script src="{{ asset('js/sidebar.js') }}?v=9"></script>
<script src="{{ asset('js/helper/formattedNumber.js') }}?v=5"></script>
<script src="{{ asset('js/fetch-form.js') }}?v=15"></script>
<script src="{{ asset('js/datatable.js') }}?v=43"></script>
<script src="{{ asset('js/export.js') }}?v=16"></script>
<script src="{{ asset('js/lightbox.js') }}?v=2"></script>
<script src="{{ asset('js/floating-menu-position.js') }}?v=1"></script>
<script src="{{ asset('js/dropdown-button.js') }}?v=3"></script>
<script src="{{ asset('js/app.js') }}?v=10"></script>
@if($portalPwa ?? false)
<script>
    window.PORTAL_PWA_SW_URL = '/portal/sw.js';
    window.PORTAL_PWA_ICON = '/pwa/icon-192.png';
    window.PORTAL_PWA_APP_NAME = @json(config('pwa.short_name'));
</script>
<script src="{{ asset('js/portal-pwa.js') }}?v=4"></script>
@endif
@stack('scripts')
