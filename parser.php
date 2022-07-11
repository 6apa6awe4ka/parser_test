<?php
require __DIR__ . '/vendor/autoload.php';

use Ds\Set;
use Kassner\LogParser\LogParser;

function strContains(string $str, string $subStr): bool {
    return str_contains(strtolower($str), $subStr);
}

const ACCESS_LOG_PATH = './access_log';

$log = file(ACCESS_LOG_PATH, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$parser = new LogParser();
$parser->setFormat('%h %l %u %t "%m %U" %>s %O "%{Referer}i" \"%{User-Agent}i"');

$urls = new Set();
$traffic = 0;
$googleCount = 0;
$bingCount = 0;
$baiduCount = 0;
$yandexCount = 0;
$code200Count = 0;
$code301Count = 0;

foreach ($log as $line) {
    $entry = $parser->parse($line);

    $urls->add($entry->URL);
    $traffic += $entry->sentBytes;

    if (strContains($entry->HeaderUserAgent, 'googlebot')) {
        $googleCount += 1;
    } elseif (strContains($entry->HeaderUserAgent, 'bingbot')) {
        $bingCount += 1;
    } elseif (strContains($entry->HeaderUserAgent, 'baiduspider')) {
        $baiduCount += 1;
    } elseif (strContains($entry->HeaderUserAgent, 'yandexbot')) {
        $yandexCount += 1;
    }

    switch ((int)$entry->status) {
        case 200:
            $code200Count += 1;
            break;
        case 301:
            $code301Count += 1;
            break;
    }
}

$res = [
    'views' => count($log),
    'urls' => $urls->count(),
    'traffic' => $traffic,
    'crawlers' => [
        'Google' => $googleCount,
        'Bing' => $bingCount,
        'Baidu' => $baiduCount,
        'Yandex' => $yandexCount,
    ],
    'statusCodes' => [
        200 => $code200Count,
        301 => $code301Count,
    ],
];

return json_encode($res);
