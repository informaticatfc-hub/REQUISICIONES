<?php
/**
 * Cliente HTTP para pruebas de integración (cookies + CSRF).
 * Compartido por las suites de tests/.
 */
class ApiClient
{
    private $base;
    private $cookieFile;
    public $csrf = '';

    public function __construct($base)
    {
        $this->base = rtrim($base, '/');
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'tfcook');
    }

    private function exec($ch)
    {
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_TIMEOUT => 20,
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $ctype = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $err = curl_error($ch);
        curl_close($ch);
        return [$status, $body === false ? null : $body, $ctype, $err];
    }

    /** POST JSON. Devuelve [status, arrayDecodificado]. */
    public function post($path, array $payload, $withCsrf = true)
    {
        $ch = curl_init($this->base . $path);
        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        if ($withCsrf && $this->csrf !== '') {
            $headers[] = 'X-CSRF-Token: ' . $this->csrf;
        }
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        list($status, $body, , $err) = $this->exec($ch);
        if ($body === null) {
            return [0, ['curl_error' => $err]];
        }
        $decoded = json_decode($body, true);
        return [$status, is_array($decoded) ? $decoded : ['raw' => $body]];
    }

    /** POST multipart (subida de archivos). $files: campo => ruta. */
    public function postMultipart($path, array $fields, array $files)
    {
        $ch = curl_init($this->base . $path);
        $headers = ['Accept: application/json'];
        if ($this->csrf !== '') {
            $headers[] = 'X-CSRF-Token: ' . $this->csrf;
        }
        $payload = $fields;
        foreach ($files as $campo => $ruta) {
            $payload[$campo] = new CURLFile($ruta, mime_content_type($ruta), basename($ruta));
        }
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        list($status, $body, , $err) = $this->exec($ch);
        if ($body === null) {
            return [0, ['curl_error' => $err]];
        }
        $decoded = json_decode($body, true);
        return [$status, is_array($decoded) ? $decoded : ['raw' => $body]];
    }

    /** GET crudo. Devuelve [status, body, contentType]. */
    public function getRaw($path)
    {
        $ch = curl_init($this->base . $path);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        list($status, $body, $ctype) = $this->exec($ch);
        return [$status, $body, $ctype];
    }

    public function login($user, $password)
    {
        list($status, $data) = $this->post('/api/LoginAcces.php', [
            'user' => $user,
            'password' => $password,
        ], false);
        if (($data['bandera'] ?? '') === 'true') {
            $this->csrf = (string)($data['csrf'] ?? '');
        }
        return [$status, $data];
    }
}
