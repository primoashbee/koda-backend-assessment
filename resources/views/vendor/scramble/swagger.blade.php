<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ $config->get('ui.title') ?? config('app.name').' - API Docs' }}</title>
    <link rel="stylesheet" href="{{ $config->renderer()->get('cdn') }}/swagger-ui.css">
</head>
<body>
<div id="swagger-ui"></div>
<script src="{{ $config->renderer()->get('cdn') }}/swagger-ui-bundle.js"></script>
<script>
    window.ui = SwaggerUIBundle({
        spec: @json($spec),
        dom_id: '#swagger-ui',
        ...@json($config->renderer()->all(except: ['cdn'])),
    });
</script>
</body>
</html>
