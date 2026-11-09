<?php
require __DIR__ . '/../../settings.php';    
global $settings;

//declare(strict_types=1);

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=300'); // 5 min cache

$host = $settings['explorer_address'];

// static core pages
$pages = [
    [
        'loc'        => $host . '/',
        'changefreq' => 'hourly',
        'priority'   => '1.0',
    ],
    [
        'loc'        => $host . '/blocks/',
        'changefreq' => 'hourly',
        'priority'   => '0.9',
    ],
        [
        'loc'        => $host . '/groups/',
        'changefreq' => 'daily',
        'priority'   => '0.8',
    ],
    [
        'loc'        => $host . '/addresses/',
        'changefreq' => 'daily',
        'priority'   => '0.7',
    ],
    [
        'loc'        => $host . '/stats/',
        'changefreq' => 'daily',
        'priority'   => '0.8',
    ],
    [
        'loc'        => $host . '/network/',
        'changefreq' => 'daily',
        'priority'   => '0.5',
    ],
    [
        'loc'        => $host . '/api/',
        'changefreq' => 'monthly',
        'priority'   => '0.8',
    ],
    [
        'loc'        => $host . '/about/',
        'changefreq' => 'monthly',
        'priority'   => '0.5',
    ],
];

if (isset($settings["show_pages"]["burned"]) && $settings["show_pages"]["burned"]){
    $pages[] = [
        'loc'        => $host . '/burnedcoins/',
        'changefreq' => 'daily',
        'priority'   => '0.5',
    ];
}

if (isset($settings["show_pages"]["assets"]) && $settings["show_pages"]["assets"]){
    $pages[] = [
        'loc'        => $host . '/assets/',
        'changefreq' => 'hourly',
        'priority'   => '0.7',
    ];
}

if (isset($settings['show_pages']['masternodes']) && $settings['show_pages']['masternodes']){
    $pages[] = [
        'loc'        => $host . '/masternodes/',
        'changefreq' => 'hourly',
        'priority'   => '0.7',
    ];
}

if (isset($settings['show_pages']['staking']) && $settings['show_pages']['staking']){
    $pages[] = [
        'loc'        => $host . '/staking/',
        'changefreq' => 'hourly',
        'priority'   => '0.6',
    ];
}

if (isset($settings['show_pages']['mining']) && $settings['show_pages']['mining']){
    $pages[] = [
        'loc'        => $host . '/mining/',
        'changefreq' => 'hourly',
        'priority'   => '0.6',
    ];
}

// helper
function xmlEntities(string $s): string {
    return htmlspecialchars($s, ENT_XML1 | ENT_COMPAT, 'UTF-8');
}
function iso8601(int $ts): string {
    return gmdate('c', $ts);
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

foreach ($pages as $p) {
    echo "  <url>\n";
    echo "    <loc>" . xmlEntities($p['loc']) . "</loc>\n";
    echo "    <lastmod>" . iso8601(time()) . "</lastmod>\n";
    echo "    <changefreq>" . $p['changefreq'] . "</changefreq>\n";
    echo "    <priority>" . $p['priority'] . "</priority>\n";
    echo "  </url>\n";
}

echo "</urlset>\n";