<?php declare(strict_types=1);
// app/FeedClient.php
// Simple client for feed API (gpt-simple-generator).

namespace App;

final class FeedClient
{
    private string $base;
    private string $apiKey;

    public function __construct(string $base, string $apiKey)
    {
        $this->base   = rtrim($base, '/');
        $this->apiKey = $apiKey;
    }

    /** POST JSON to feed API and decode. */
    private function post(string $path, array $payload): array
    {
        $url  = $this->base . $path;
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 120,
        ]);

        $resp = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            throw new \RuntimeException('HTTP failed: ' . $err);
        }

        $data = json_decode((string)$resp, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Bad JSON from feed: ' . substr((string)$resp, 0, 300));
        }
        if ($code >= 400) {
            $msg = $data['error'] ?? ('HTTP ' . $code);
            throw new \RuntimeException('Feed error: ' . $msg);
        }
        return $data;
    }

    /** Fetch a unique template bundle (files to write locally). */
    public function templateBundle(string $lang, string $seed, array $styleFlags): array
    {
        $payload = ['lang'=>$lang, 'seed'=>$seed, 'styleFlags'=>$styleFlags];
        $out = $this->post('/template_bundle', $payload);
        if (!($out['ok'] ?? false)) {
            throw new \RuntimeException('template_bundle failed');
        }
        return $out['bundle'] ?? [];
    }

    /** Generate one article. */
    public function generateArticle(string $lang): array
    {
        $payload = [
            'lang' => $lang,
            'keywords' => ['camel milk'],
            'paragraphs' => 6,
            'faqCount' => 5,
            'minSentencesPerParagraph' => 4,
            'styleFlags' => ['human-like','evidence-based'],
        ];
        return $this->post('/generate', $payload);
    }
}