<?php

namespace Jerryaicn2023\Youdu;

use CURLFile;
use stdClass;

class Youdu
{
    private ?stdClass $accessToken = null;
    private string $host;
    private string $key;
    private string $appId;
    private int $buin;
    private bool $debug = false;

    /**
     * Youdu constructor.
     * @param string $host
     * @param int $buin
     * @param string $appId
     * @param string $key
     */
    function __construct(string $host, int $buin, string $appId, string $key)
    {
        $this->host = $host;
        $this->buin = $buin;
        $this->appId = $appId;
        $this->key = $key;
    }

    function debug()
    {
        $this->debug = true;
    }

    /**
     * @param string $to
     * @param array $news
     * @return array
     * @throws YouduException
     */
    function sendNews(string $to, array $news): array
    {
        foreach ($news as &$item) {
            if (isset($item['media_id'])) {
                $item['media_id'] = $this->upload($item['media_id']);
            }
        }
        return $this->send(json_encode([
            'toUser' => $to,
            'msgType' => 'mpnews',
            'mpnews' => $news
        ]));
    }

    /**
     * @param string $path
     * @return string
     * @throws YouduException
     */
    function upload(string $path): string
    {
        $content = file_get_contents($path);
        $tmpFile = tempnam(sys_get_temp_dir(), 'youdu');
        $fd = fopen($tmpFile, "w");
        fwrite($fd, $this->encrypt($content));
        fclose($fd);
        if (in_array(mime_content_type($path), ['image/jpeg', 'image/png'])) {
            $type = 'image';
        } else {
            $type = 'file';
        }
        $data = json_encode(
            [
                'type' => $type,
                'name' => basename($path)
            ]
        );
        $encrypt = $this->encrypt($data);
        $response = $this->post(
            $this->getTokenUrl($this->host . '/cgi/media/upload'),
            $this->getParam($encrypt),
            realpath($tmpFile)
        );
        $json = $this->json($response, ['encrypt']);
        return json_decode($this->decrypt($json['encrypt']), true)['mediaId'];
    }

    /**
     * @param string $data
     * @return string
     */
    function encrypt(string $data): string
    {
        $crypt = new Crypt($this->key);
        return $crypt->encrypt($data, $this->appId);
    }

    /**
     * @param string $url
     * @param array $data
     * @param null|string $file
     * @return string
     * @throws YouduException
     */
    public function post(string $url, array $data, $file = null): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($file) {
            $data['file'] = new CURLFile($file, mime_content_type($file), basename($file));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'content-Length: ' . strlen(json_encode($data)))
            );
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        $server_output = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($server_output, 0, $header_size);
        $body = substr($server_output, $header_size);
        curl_close($ch);
        if ($this->debug) {
            echo $url . PHP_EOL;
            var_dump($data);
            var_dump($body);
        }
        if ($httpCode != '200') {
            throw new YouduException("接口http响应错误[{$httpCode}]");
        }
        return $body;
    }

    /**
     * @param string $url
     * @return string
     * @throws YouduException
     */
    private function getTokenUrl(string $url): string
    {
        if (!$this->accessToken) {
            $this->accessToken = $this->getToken();
        }
        return $url . '?accessToken=' . $this->accessToken->accessToken;
    }

    /**
     * @return mixed
     * @throws YouduException
     */
    function getToken()
    {
        $data = $this->encrypt(time());
        $param = $this->getParam($data);
        $response = $this->post($this->host . '/cgi/gettoken', $param);
        $json = $this->json($response);
        return json_decode($this->decrypt($json['encrypt']));
    }

    /**
     * @param string $data
     * @return array
     */
    private function getParam($data = ''): array
    {
        return [
            "buin" => $this->buin,
            "appId" => $this->appId,
            "encrypt" => $data
        ];
    }

    /**
     * @param $string
     * @param array $keys
     * @return array
     * @throws YouduException
     */
    function json($string, $keys = []): array
    {
        $body = json_decode($string, true);
        if (!$body) {
            throw new YouduException("接口响应不是json");
        }
        if ($body['errcode']) {
            throw new YouduException(sprintf("接口响应:%s[%s]", $body['errmsg'], $body['errcode']));
        }
        foreach ($keys as $key) {
            if (!key_exists($key, $body)) {
                throw new YouduException(sprintf('接口响应的结构中，找不到"%s"', $key));
            }
        }
        return $body;
    }

    /**
     * @param string $encrypt
     * @return string
     * @throws YouduException
     */
    function decrypt(string $encrypt): string
    {
        $crypt = new Crypt($this->key);
        return $crypt->decrypt($encrypt, $this->appId);
    }

    /**
     * @param string $content
     * @return array
     */
    function send(string $content): array
    {
        try {
            $response = $this->post($this->getTokenUrl($this->host . '/cgi/msg/send'), $this->getParam($this->encrypt($content)));
            return $this->json($response);
        } catch (YouduException $e) {
            return [
                'errcode' => -1,
                'errmsg' => $e->getMessage()
            ];
        }
    }

    function sendPop(string $content): array
    {
        try {
            $response = $this->post($this->getTokenUrl($this->host . '/cgi/popwindow'),
                [
                    'buin' => $this->buin,
                    'app_id' => $this->appId,
                    'msg_encrypt' => $this->encrypt($content)
                ]);
            return $this->json($response);
        } catch (YouduException $e) {
            return [
                'errcode' => -1,
                'errmsg' => $e->getMessage()
            ];
        }
    }

    /**
     * @param string $to
     * @param array $link
     * @return array
     * @throws YouduException
     */
    function sendLink(string $to, array $link): array
    {
        foreach ($link as &$item) {
            if (isset($item['media_id'])) {
                $item['media_id'] = $this->upload($item['media_id']);
            }
        }
        return $this->send(json_encode([
            'toUser' => $to,
            'msgType' => 'exlink',
            'exlink' => $link
        ]));
    }

    /**
     * @param string $to
     * @param string $text
     * @return array
     */
    function sendText(string $to, string $text): array
    {
        return $this->send(json_encode([
            'toUser' => $to,
            'msgType' => 'text',
            'text' => [
                'content' => $text
            ]
        ]));
    }

    /**
     * @param string $to
     * @param string $file
     * @return array
     * @throws YouduException
     */
    function sendImage(string $to, string $file): array
    {
        $mediaId = $this->upload($file);
        return $this->send(json_encode([
            'toUser' => $to,
            'msgType' => 'image',
            'image' => [
                'media_id' => $mediaId
            ]
        ]));
    }

    /**
     * @param string $to
     * @param string $file
     * @return array
     * @throws YouduException
     */
    function sendFile(string $to, string $file): array
    {
        $mediaId = $this->upload($file);
        return $this->send(json_encode([
            'toUser' => $to,
            'msgType' => 'file',
            'file' => [
                'media_id' => $mediaId
            ]
        ]));
    }
}