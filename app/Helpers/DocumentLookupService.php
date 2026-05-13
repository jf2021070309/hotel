<?php

class DocumentLookupService
{
    private const DNI_ENDPOINT = 'https://api.apis.net.pe/v1/dni?numero=';
    private const RUC_ENDPOINT = 'https://api.apis.net.pe/v1/ruc?numero=';

    public function consultarDni(string $dni): ?array
    {
        if (!preg_match('/^\d{8}$/', $dni)) {
            return null;
        }

        $data = $this->requestJson(self::DNI_ENDPOINT . urlencode($dni), 10);
        if (!$data) {
            return null;
        }

        if (!empty($data['nombres'])) {
            $nombreCompleto = trim($data['nombres'])
                . ' ' . trim($data['apellidoPaterno'] ?? '')
                . ' ' . trim($data['apellidoMaterno'] ?? '');

            return [
                'nombre_completo' => $this->titleCase($nombreCompleto),
            ];
        }

        if (!empty($data['nombre'])) {
            return [
                'nombre_completo' => $this->titleCase($data['nombre']),
            ];
        }

        return null;
    }

    public function consultarRuc(string $ruc): ?array
    {
        if (!preg_match('/^\d{11}$/', $ruc)) {
            return null;
        }

        $data = $this->requestJson(self::RUC_ENDPOINT . urlencode($ruc), 5);
        if (empty($data['nombre'])) {
            return null;
        }

        return [
            'razon_social' => trim($data['nombre']),
            'direccion' => trim($data['direccion'] ?? ''),
        ];
    }

    private function requestJson(string $url, int $timeout): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return null;
        }

        $data = json_decode($response, true);
        return is_array($data) ? $data : null;
    }

    private function titleCase(string $value): string
    {
        $value = trim($value);
        return mb_convert_case(mb_strtolower($value, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }
}
