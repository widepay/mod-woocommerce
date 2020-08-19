<?php

class WidePay
{
    private $autenticacao = array();
    public $requisicoes = array();

    public function __construct($carteira, $token)
    {
        $this->autenticacao = array(
            'carteira' => $carteira,
            'token' => $token
        );
    }

    public function api($local, $parametros = array())
    {

        $auth = base64_encode($this->autenticacao['carteira'] . ':' . $this->autenticacao['token']);
        $url = 'https://api.widepay.com/v1/' . trim($local, '/');

        $args = [
            'headers' => [
                'Authorization' => "Basic $auth"
            ],
            'body' => $parametros,
        ];
        $response = wp_remote_post($url, $args);
        $response_body = wp_remote_retrieve_body($response);
        if ($response_body) {
            $requisicao = json_decode($response_body, true);
            if (!is_array($requisicao)) {
                $requisicao = array(
                    'sucesso' => false,
                    'erro' => 'Não foi possível tratar o retorno.'
                );
            }
        } else {
            $requisicao = array(
                'sucesso' => false,
                'erro' => 'Sem comunicação com o servidor.'
            );
        }
        $this->requisicoes[] = (object)$requisicao;
        return end($this->requisicoes);
    }
}
