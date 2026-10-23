@php
    $breadcrumbItems = [];
    foreach ($items as $index => $item) {
        $breadcrumbItems[] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $item['name'],
            'item' => $item['url'] ?? request()->url(),
        ];
    }
    $breadcrumbSchema = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $breadcrumbItems];
@endphp
<script type="application/ld+json">{!! json_encode($breadcrumbSchema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
