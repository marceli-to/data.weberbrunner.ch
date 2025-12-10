<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full overflow-y-scroll">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ config('app.name', 'Weberbrunner Daten') }}</title>
<link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
<link rel="icon" type="image/svg+xml" href="/favicon.svg" />
<link rel="shortcut icon" href="/favicon.ico" />
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
<meta name="apple-mobile-web-app-title" content="weberbrunner architektur" />
<link rel="manifest" href="/site.webmanifest" />
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 h-full">
  <div id="app" class="flex flex-col min-h-screen"></div>
</body>
</html>
