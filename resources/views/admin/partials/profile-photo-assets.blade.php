@once
    @push('styles')
        <link href="https://unpkg.com/filepond@^4/dist/filepond.min.css" rel="stylesheet">
        <link href="https://unpkg.com/filepond-plugin-image-preview@^4/dist/filepond-plugin-image-preview.min.css" rel="stylesheet">
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/filepond-plugin-file-validate-type@^1/dist/filepond-plugin-file-validate-type.min.js"></script>
        <script src="https://unpkg.com/filepond-plugin-file-validate-size@^2/dist/filepond-plugin-file-validate-size.min.js"></script>
        <script src="https://unpkg.com/filepond-plugin-image-preview@^4/dist/filepond-plugin-image-preview.min.js"></script>
        <script src="https://unpkg.com/filepond-plugin-image-validate-size@^1/dist/filepond-plugin-image-validate-size.min.js"></script>
        <script src="https://unpkg.com/filepond@^4/dist/filepond.min.js"></script>
        <script src="{{ asset('js/profile-photo-upload.js') }}?v=2"></script>
    @endpush
@endonce
