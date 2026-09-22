@once
    <link rel="stylesheet" href="{{ asset('css/coqp-toasts.css') }}?v={{ filemtime(public_path('css/coqp-toasts.css')) }}" data-navigate-track>
    <script src="{{ asset('js/coqp-toasts.js') }}?v={{ filemtime(public_path('js/coqp-toasts.js')) }}" defer data-navigate-once></script>
@endonce
