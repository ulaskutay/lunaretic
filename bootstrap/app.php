<?php

use App\Etic\SEO\Http\Middleware\ApplyRedirects;
use App\Etic\Storefront\Http\Middleware\IdentifyStore;
use App\Etic\Support\Console\ServeCommand;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        ServeCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->prepend(IdentifyStore::class);
        $middleware->append(ApplyRedirects::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->reportable(function (ValidationException $e) {
            if (! request()->is('livewire/upload-file')) {
                return;
            }

            logger()->warning('Livewire file upload rejected', [
                'errors' => $e->errors(),
                'php_upload_max_filesize' => ini_get('upload_max_filesize'),
                'php_post_max_size' => ini_get('post_max_size'),
            ]);
        });
    })->create();
