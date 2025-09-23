<?php

use Craftile\Laravel\Middlewares\PreviewModeMiddleware;
use Craftile\Laravel\PreviewDataCollector;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

test('injects preview scripts when in preview mode', function () {
    $collector = app(PreviewDataCollector::class);
    $middleware = new PreviewModeMiddleware($collector);

    // Mock a request with preview mode
    $request = Request::create('/test', 'GET', ['_preview' => 'true']);
    $this->app->instance('request', $request);

    // Mock response with HTML content
    $response = new Response('<html><body><h1>Test Page</h1></body></html>');
    $response->headers->set('Content-Type', 'text/html');

    $result = $middleware->handle($request, function () use ($response) {
        return $response;
    });

    $content = $result->getContent();
    expect($content)->toContain('page-data');
    expect($content)->toContain('@craftile/preview-client-html');
    expect($content)->toContain('HtmlPreviewClient.init()');
});

test('does not inject scripts when not in preview mode', function () {
    $collector = app(PreviewDataCollector::class);
    $middleware = new PreviewModeMiddleware($collector);

    $request = Request::create('/test', 'GET');
    $this->app->instance('request', $request);

    $originalContent = '<html><body><h1>Test Page</h1></body></html>';
    $response = new Response($originalContent);
    $response->headers->set('Content-Type', 'text/html');

    $result = $middleware->handle($request, function () use ($response) {
        return $response;
    });

    expect($result->getContent())->toBe($originalContent);
});

test('does not inject scripts for non-html responses', function () {
    $collector = app(PreviewDataCollector::class);
    $middleware = new PreviewModeMiddleware($collector);

    $request = Request::create('/test', 'GET', ['_preview' => 'true']);
    $this->app->instance('request', $request);

    $originalContent = '{"data": "test"}';
    $response = new Response($originalContent);
    $response->headers->set('Content-Type', 'application/json');

    $result = $middleware->handle($request, function () use ($response) {
        return $response;
    });

    expect($result->getContent())->toBe($originalContent);
});

test('does not inject scripts when no body tag present', function () {
    $collector = app(PreviewDataCollector::class);
    $middleware = new PreviewModeMiddleware($collector);

    $request = Request::create('/test', 'GET', ['_preview' => 'true']);
    $this->app->instance('request', $request);

    $originalContent = '<html><head><title>Test</title></head></html>';
    $response = new Response($originalContent);
    $response->headers->set('Content-Type', 'text/html');

    $result = $middleware->handle($request, function () use ($response) {
        return $response;
    });

    expect($result->getContent())->toBe($originalContent);
});

test('includes page data in injected scripts', function () {
    $collector = app(PreviewDataCollector::class);
    $middleware = new PreviewModeMiddleware($collector);

    $collector->startRegion('content');
    $collector->endRegion('content');

    // Mock a request with preview mode
    $request = Request::create('/test', 'GET', ['_preview' => 'true']);
    $this->app->instance('request', $request);

    $response = new Response('<html><body><h1>Test</h1></body></html>');
    $response->headers->set('Content-Type', 'text/html');

    $result = $middleware->handle($request, function () use ($response) {
        return $response;
    });

    $content = $result->getContent();

    // Check that page data is included
    expect($content)->toContain('"regions"');
    expect($content)->toContain('page-data');
});

test('handles empty response content gracefully', function () {
    $collector = app(PreviewDataCollector::class);
    $middleware = new PreviewModeMiddleware($collector);

    $request = Request::create('/test', 'GET', ['_preview' => 'true']);
    $this->app->instance('request', $request);

    $response = new Response('');
    $response->headers->set('Content-Type', 'text/html');

    $result = $middleware->handle($request, function () use ($response) {
        return $response;
    });

    expect($result->getContent())->toBe('');
});
