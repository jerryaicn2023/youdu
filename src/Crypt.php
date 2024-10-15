<?php

namespace Jerryaicn2023\Youdu;

class Crypt
{
    private int $blockSize = 32;
    private string $key;
    private string $cipher = 'aes-256-cbc';

    /**
     * Crypt constructor.
     * @param string $key
     */
    public function __construct(string $key)
    {
        $this->key = base64_decode($key);
    }

    /**
     * @param string $text
     * @param string $appId
     * @return string
     */
    public function encrypt(string $text, string $appId): string
    {
        $random = $this->getRandomStr();
        $text = $random . pack("N", strlen($text)) . $text . $appId;
        $text = $this->padding($text);
        $iv = substr($this->key, 0, 16);
        $encrypted = openssl_encrypt($text, $this->cipher, $this->key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
        return base64_encode($encrypted);
    }

    /**
     * @return string
     */
    private function getRandomStr(): string
    {
        $str = "";
        $str_pol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($str_pol) - 1;
        for ($i = 0; $i < 16; $i++) {
            $str .= $str_pol[mt_rand(0, $max)];
        }
        return $str;
    }

    /**
     * @param string $text
     * @return string
     */
    public function padding(string $text): string
    {
        $text_length = strlen($text);
        $amount_to_pad = $this->blockSize - ($text_length % $this->blockSize);
        if ($amount_to_pad == 0) {
            $amount_to_pad = $this->blockSize;
        }
        $pad_chr = chr($amount_to_pad);
        $tmp = "";
        for ($index = 0; $index < $amount_to_pad; $index++) {
            $tmp .= $pad_chr;
        }
        return $text . $tmp;
    }

    /**
     * @param string $encrypted
     * @param string $appId
     * @return string
     * @throws YouduException
     */
    public function decrypt(string $encrypted, string $appId): string
    {
        $encrypted = base64_decode($encrypted);
        $iv = substr($this->key, 0, 16);
        $decrypted = openssl_decrypt($encrypted, $this->cipher, $this->key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
        $result = $this->removePadding($decrypted);
        $dataLength = unpack('N', substr($result, 16, 4))[1];
        $data = substr($result, 20, $dataLength);
        $fromAppId = substr($result, 20 + $dataLength);
        if ($fromAppId !== $appId) {
            throw new YouduException("appId不匹配");
        }
        return $data;
    }

    /**
     * @param string $text
     * @return false|string
     */
    public function removePadding(string $text)
    {
        $pad = ord(substr($text, -1));
        if ($pad < 1 || $pad > $this->blockSize) {
            $pad = 0;
        }
        return substr($text, 0, (strlen($text) - $pad));
    }
}