<?php
// src/Services/PingService.php
declare(strict_types=1);

namespace App\Services;

final class PingService
{
    /** Returns true on HTTP 200 */
    public function pingPingOMatic(string $siteName, string $homeUrl, ?string $rssUrl = null): bool
    {
        $endpoint = 'http://rpc.pingomatic.com/RPC2'; // XML-RPC
        $xml = $this->xmlRpcEnvelope('weblogUpdates.ping', [$siteName, $homeUrl]);
        if ($rssUrl && $rssUrl !== '') {
            // Extended ping (weblogUpdates.extendedPing) includes RSS URL; fall back to basic if it fails.
            $xmlExt = $this->xmlRpcEnvelope('weblogUpdates.extendedPing', [$siteName, $homeUrl, $rssUrl]);
            if ($this->postXml($endpoint, $xmlExt)) return true;
        }
        return $this->postXml($endpoint, $xml);
    }

    private function xmlRpcEnvelope(string $method, array $params): string
    {
        $paramsXml = '';
        foreach ($params as $p) {
            $v = htmlspecialchars((string)$p, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $paramsXml .= "<param><value><string>{$v}</string></value></param>";
        }
        return "<?xml version=\"1.0\"?><methodCall><methodName>{$method}</methodName><params>{$paramsXml}</params></methodCall>";
    }

    private function postXml(string $url, string $xml): bool
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST            => true,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_HTTPHEADER      => ['Content-Type: text/xml'],
            CURLOPT_POSTFIELDS      => $xml,
            CURLOPT_TIMEOUT         => 12,
            CURLOPT_CONNECTTIMEOUT  => 6,
        ]);
        curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code === 200;
    }
}
