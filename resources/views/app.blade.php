<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    {{-- Aplica o tema antes do CSS para não piscar. Mesma regra de resources/js/composables/useTema.ts. --}}
    <script>
        (function () {
            var preferencia = null;
            try { preferencia = localStorage.getItem('caderneta.tema'); } catch (e) {}
            var escuro = preferencia === 'escuro' || (preferencia !== 'claro'
                && !!window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.setAttribute('data-theme', escuro ? 'dark' : 'light');
        })();
    </script>
    <title inertia>{{ config('app.name') }}</title>
    @vite(['resources/js/app.ts'])
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
