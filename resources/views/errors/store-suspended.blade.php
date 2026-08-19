<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $store->name }}</title>
</head>
<body style="font-family: system-ui, sans-serif; margin: 0; min-height: 100vh; display: grid; place-items: center; background: #fafafa; color: #111;">
    <main style="max-width: 32rem; padding: 2rem; text-align: center;">
        <h1 style="font-size: 1.5rem; margin-bottom: 0.75rem;">{{ __('etic.tenancy.maintenance.title') }}</h1>
        <p style="color: #525252;">{{ __('etic.tenancy.maintenance.body', ['store' => $store->name]) }}</p>
    </main>
</body>
</html>
