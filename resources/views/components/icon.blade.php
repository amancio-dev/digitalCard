@props(['name' => 'package'])
@php
    $resolved = preg_replace('/[^a-z0-9-]/', '', strtolower((string) $name));
    if ($resolved === '') {
        $resolved = 'package';
    }
    $path = resource_path("svg/icons/{$resolved}.svg");
    if (! is_file($path)) {
        $path = resource_path('svg/icons/package.svg');
    }
    $svg = is_file($path) ? file_get_contents($path) : '';
    $attrString = $attributes->merge(['class' => 'size-4 shrink-0 inline-block', 'aria-hidden' => 'true'])->toHtml();
    $svg = preg_replace('/<svg\b/', '<svg '.$attrString, (string) $svg, 1);
@endphp
{!! $svg !!}
