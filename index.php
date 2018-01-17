<?php
/*
   Plugin Name: Woo Wide Pay
   Description: Com o Wide Pay, suas transações geram comprovantes com autenticação bancária, disponibilizados em sua conta e enviados por e-mail. Tudo de forma simples e rápida.
   Version: 1.0.2
   Plugin URI: https://widepay.com/
   Author: Wide Soft
   Author URI: https://widepay.com/
   License: Under GPL2
*/

require_once dirname(__FILE__) . '/widepay/WidePay.php';


add_action('plugins_loaded', 'woo_widepay_module', 0);

function woo_widepay_module()
{
    if (!class_exists('WC_Payment_Gateway'))
        return;

    class WC_WidePay_Payment extends WC_Payment_Gateway
    {
        public function __construct()
        {
            $this->id = 'widepay';
            $this->method_title = 'Wide Pay';
            $this->icon = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/logo.png';
            $this->has_fields = true;
            $this->init_form_fields();
            $this->init_settings();
            $this->title = $this->settings['title'];
            $this->description = $this->settings['description'];
            $this->item_name = $this->settings['item_name'];
            $this->wallet_id = $this->settings['wallet_id'];
            $this->wallet_token = $this->settings['wallet_token'];
            $this->tax = $this->settings['tax'];
            $this->tax_type = $this->settings['tax_type'];
            $this->plus_date_due = $this->settings['plus_date_due'];
            $this->fine = $this->settings['fine'];
            $this->interest = $this->settings['interest'];
            $this->return_url = WC()->api_request_url('WC_WidePay_Payment');

            add_action('init', array(&$this, 'check_widepay_response'));

            if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
            } else {
                add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
            }

            add_action('woocommerce_receipt_widepay', array(&$this, 'receipt_page'));
            add_action('woocommerce_thankyou_widepay', array(&$this, 'thankyou_page'));
            add_action('woocommerce_api_wc_widepay_payment', array($this, 'check_widepay_response'));
        }

        function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Ativar/Desativar'),
                    'type' => 'checkbox',
                    'label' => __('Ativar módulo de pagamento Wide Pay.'),
                    'default' => 'no'),
                'title' => array(
                    'title' => __('Title:'),
                    'type' => 'text',
                    'description' => __('Nome do método de pagamento que será exibido no checkout'),
                    'default' => __('Wide Pay')),
                'description' => array(
                    'title' => __('Descrição:'),
                    'type' => 'textarea',
                    'description' => __('Descrição do método de pagamento que será exibido no checkout'),
                    'default' => __('Pagar com Wide Pay')),
                'item_name' => array(
                    'title' => __('Item na fatura:'),
                    'type' => 'text',
                    'description' => __('Descrição do item presente na fatura Wide Pay'),
                    'default' => __('Compra realizada no site: ' . get_bloginfo('name'))),
                'wallet_id' => array(
                    'title' => __('ID da Carteira:'),
                    'type' => 'text',
                    'description' => __('Preencha este campo com o ID da carteira que deseja receber os pagamentos do sistema.<br>O ID de sua carteira estará presente neste link: <a href="https://www.widepay.com/conta/configuracoes/carteiras" target="_blank">https://www.widepay.com/conta/configuracoes/carteiras</a>')),
                'wallet_token' => array(
                    'title' => __('Token da carteira Wide Pay:'),
                    'type' => 'text',
                    'description' => __('Preencha com o token referente à sua carteira escolhida no campo acima.<br>Clique no botão: "Integrações" na <a href="https://www.widepay.com/conta/configuracoes/carteiras" target="_blank">página do Wide Pay</a>, será exibido o Token')),
                'tax' => array(
                    'title' => __('Taxa de Variação:'),
                    'type' => 'text',
                    'description' => __('O valor final da fatura será recalculado de acordo com este campo.<br>Coloque 0 para não alterar.'),
                    'default' => __('0')),
                'tax_type' => array(
                    'title' => __('Tipo da Taxa de Variação:'),
                    'type' => 'select',
                    'options' => array(
                        '1' => 'Acrécimo em %',
                        '2' => 'Acrécimo valor fixo em R$',
                        '3' => 'Desconto em %',
                        '4' => 'Desconto valor fixo em R$'),
                    'description' => __('A "<strong>Taxa de Variação</strong>" será aplicada de acordo com este campo.')),
                'plus_date_due' => array(
                    'title' => __('Acréscimo de Dias no Vencimento:'),
                    'type' => 'text',
                    'description' => __('Configure aqui a quantidade de dias corridos para o vencimento após a geração da fatura'),
                    'default' => __('7')),
                'fine' => array(
                    'title' => __('Configuração de Multa:'),
                    'type' => 'text',
                    'description' => __('Configuração em porcentagem. Exemplo: 2')),
                'interest' => array(
                    'title' => __('Configuração de Juros:'),
                    'type' => 'text',
                    'description' => __('Configuração em porcentagem. Exemplo: 2')),
            );
        }

        public function admin_options()
        {
            echo '<h3>' . __('Wide Pay Processador de Pagamentos') . '</h3>';
            echo '<p>' . __(
                    '<strong>Sobre</strong>: <a href="https://www.widepay.com/" target="_blank">https://www.widepay.com/</a> | ' .
                    '<strong>Acessar</strong>: <a href="https://www.widepay.com/acessar" target="_blank">https://www.widepay.com/acessar</a> | ' .
                    '<strong>Carteiras</strong>: <a href="https://www.widepay.com/conta/configuracoes/carteiras" target="_blank">https://www.widepay.com/conta/configuracoes/carteiras</a> | ' .
                    '<strong>Guia</strong>: <a href="https://widepay.github.io/api/index.html" target="_blank">https://widepay.github.io/api/index.html</a>  <hr>') .
                '</p>';
            echo '<table class="form-table">';
            $this->generate_settings_html();
            echo '</table>';
        }

        function payment_fields()
        {
            echo '<div id="">';
            woocommerce_form_field('billing_cpf_cnpj', array(
                'type' => 'text',
                'required' => true,
                'label' => __('Preencha com seu CPF ou CNPJ'),
                'placeholder' => __(''),
            ));
            echo '</div>';
            if ($this->description)
                echo wpautop(wptexturize($this->description));
        }

        public function thankyou_page($order_id)
        {
        }

        function receipt_page($order)
        {
            echo '<p>' . __('Obrigado pelo pedido, aguarde, você será redirecionado para páginda de pagamento Wide Pay!') . '</p>';
            echo $this->generate_form($order);
        }

        function process_payment($order_id)
        {
            global $woocommerce;
            $woocommerce->cart->empty_cart();
            $order = new WC_Order($order_id);
            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(true)
            );
        }

        function check_widepay_response()
        {
            @ob_clean();
            header('HTTP/1.1 200 OK');
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["notificacao"])) {
                $wp = new WidePay($this->wallet_id, $this->wallet_token); // ID e token da carteira
                $notificacao = $wp->api('recebimentos/cobrancas/notificacao', array(
                    'id' => $_POST["notificacao"] // ID da notificação recebido do Wide Pay via POST
                ));
                if ($notificacao->sucesso) {
                    $order_id = $notificacao->cobranca['referencia'];
                    $transactionID = $notificacao->cobranca['id'];
                    $status = $notificacao->cobranca['status'];
                    if ($status == 'Baixado' || $status == 'Recebido') {
                        $order = new WC_Order($order_id);
                        $order->payment_complete($transactionID);
                        $order->add_order_note(__('Wide Pay aprovou o pagamento (ID: ' . $transactionID . ', Status: ' . $status . ')'));
                        echo 'Pagamento atualizado';
                    } else {
                        echo 'Status não suportado';
                    }
                    exit();
                } else {
                    echo $notificacao->erro; // Erro
                    exit();
                }
            }

            wp_redirect(wc_get_page_permalink('cart'));
            exit();
        }

        public function generate_form($order_id)
        {
            $order = new WC_Order($order_id);
            if (version_compare(WOOCOMMERCE_VERSION, '3.0.0', '>=')) {
                $order_data = $this->extract_data_3($order_id);
            } else {
                $order_data = $this->extract_data_2($order_id);
            }
            // Parâmetros Wide Pay
            $widepayWalletNumber = trim($this->wallet_id);
            $widepayWalletToken = trim($this->wallet_token);
            $widepayTax = $this->tax;
            $widepayTaxType = (int)$this->tax_type;
            $widepayPlusDateDue = (int)$this->plus_date_due;
            $widepayFine = (double)str_replace(',', '.', $this->fine);
            $widepayInterest = (double)str_replace(',', '.', $this->interest);
            $widepayCpf = ''; //Será populado mais abaixo.
            $widepayCnpj = ''; //Será populado mais abaixo.
            $widepayPessoa = ''; //Será populado mais abaixo.
            // Parâmetros da Fatura
            $invoiceId = $order_id;
            $invoiceDuedate = $order_data['date_created'];
            $description = $this->item_name;
            $amount = round((double)$order_data['total'], 2);
            // Parâmetros do Cliente
            $firstname = $order_data['firstname'];
            $lastname = $order_data['lastname'];
            $phone = $order_data['phone'];
            $email = $order_data['email'];
            $address1 = (isset($order_data['address_1']))?$order_data['address_1']:$order_data['address1'];
            $address2 = (isset($order_data['address_1']))?$order_data['address_1']:$order_data['address2'];
            $city = $order_data['city'];
            $state = $order_data['state'];
            $postcode = $order_data['postcode'];
            // Parâmetros do Sistema
            $systemUrl = $this->return_url;
            $widepayCpf = get_post_meta($order_id, 'billing_cpf_cnpj', true);
            //+++++++++++++++++++++++++++++[Configuração de Pessoa - CPF - CNPJ Wide Pay ]+++++++++++++++++++++++++++++++++
            if (strlen($order_data['cpf_cnpj']) == 11) {
                $widepayCpf = $order_data['cpf_cnpj'];
                $widepayPessoa = 'Física';
            } else {
                $widepayCnpj = $order_data['cpf_cnpj'];
                $widepayPessoa = 'Jurídica';
            }
            //+++++++++++++++++++++++++++++[Configuração de Itens Wide Pay //  Tratamento caso haja crédito na fatura ou taxa adicional ]+++++++++++++++++++++++++++++++++
            //Itens WidePay
            $widepayItens = [];
            $widepayTotal = 0; // Valor total fatura WidePay.
            $widepayTax = (float)str_replace(',', '.', $widepayTax);
            //Formatação para calculo ou exibição na descrição
            $widepayTaxDouble = number_format((double)$widepayTax, 2, '.', '');
            $widepayTaxReal = number_format((double)$widepayTax, 2, ',', '');

            // Configuração da taxa de variação nos Itens da fatura.
            if ($widepayTax > 0) {
                if ($widepayTaxType == 1) {//Acrécimo em Porcentagem
                    $widepayItens[] = [
                        'descricao' => $description,
                        'valor' => $amount
                    ];
                    $widepayTotal = $widepayTotal + $amount;
                    $widepayItens[] = [
                        'descricao' => 'Referente a taxa adicional de ' . $widepayTaxReal . '%',
                        'valor' => round((((double)$widepayTaxDouble / 100) * $amount), 2)
                    ];
                    $widepayTotal = $widepayTotal + round((((double)$widepayTaxDouble / 100) * $amount), 2);
                } elseif ($widepayTaxType == 2) {//Acrécimo valor Fixo
                    $widepayItens[] = [
                        'descricao' => $description,
                        'valor' => $amount
                    ];
                    $widepayTotal = $widepayTotal + $amount;
                    $widepayItens[] = [
                        'descricao' => 'Referente a taxa adicional de R$' . $widepayTaxReal,
                        'valor' => ((double)$widepayTaxDouble),
                    ];
                    $widepayTotal = $widepayTotal + ((double)$widepayTaxDouble);
                } elseif ($widepayTaxType == 3) {//Desconto em Porcentagem
                    $widepayItens[] = [
                        'descricao' => $description,
                        'valor' => $amount
                    ];
                    $widepayItens[] = [
                        'descricao' => 'Item referente ao desconto: ' . $widepayTaxReal . '%',
                        'valor' => round((((double)$widepayTaxDouble / 100) * $amount), 2) * (-1)
                    ];
                    $widepayTotal = $widepayTotal + ($amount - round((((double)$widepayTaxDouble / 100) * $amount), 2));
                } elseif ($widepayTaxType == 4) {//Desconto valor Fixo
                    $widepayItens[] = [
                        'descricao' => $description,
                        'valor' => $amount
                    ];
                    $widepayItens[] = [
                        'descricao' => 'Item referente ao desconto: R$' . $widepayTaxReal,
                        'valor' => $widepayTaxDouble * (-1)
                    ];
                    $widepayTotal = $widepayTotal + (round(($amount - $widepayTaxDouble), 2));
                }
            } else {// Caso não tenha taxa de variação será adicionado o valor da fatura neste campo. Mesmo caso haja crédito da fatura.
                $widepayItens[] = [
                    'descricao' => $description,
                    'valor' => $amount
                ];
                $widepayTotal = $widepayTotal + $amount;
            }
            //+++++++++++++++++++++++++++++[Configuração de data de vencimento ]+++++++++++++++++++++++++++++++++
            if ($widepayPlusDateDue == null || $widepayPlusDateDue == '') {
                $widepayPlusDateDue = '0';
            }
            if ($invoiceDuedate < date('Y-m-d')) {
                $invoiceDuedate = date('Y-m-d');
            }
            $invoiceDuedate = new DateTime($invoiceDuedate);
            $invoiceDuedate->modify('+' . $widepayPlusDateDue . ' day');
            $invoiceDuedate = $invoiceDuedate->format('Y-m-d');
            //+++++++++++++++++++++++++++++[ Processo final para mostrar fatura ]+++++++++++++++++++++++++++++++++
            //Pega fatura no banco de dados caso já gerada anteriormente.
            $widepayInvoice = $this->widepay_get_invoice($order_id, $widepayTotal, $widepayTaxType, $widepayFine, $widepayInterest, $order_data['cpf_cnpj']);
            //Caso a fatura não tenha sido gerada anteriormente
            if (!$widepayInvoice['success']) {
                $wp = new WidePay($widepayWalletNumber, $widepayWalletToken);
                $widepayData = array(
                    'forma' => 'Boleto',
                    'referencia' => $invoiceId,
                    'notificacao' => $systemUrl,
                    'vencimento' => $invoiceDuedate,
                    'cliente' => $firstname . ' ' . $lastname,
                    'telefone' => $phone,
                    'email' => $email,
                    'pessoa' => $widepayPessoa,
                    'cpf' => $widepayCpf,
                    'cnpj' => $widepayCnpj,
                    'endereco' => array(
                        'rua' => $address1,
                        'complemento' => $address2,
                        'cep' => $postcode,
                        'estado' => $state,
                        'cidade' => $city
                    ),
                    'itens' => $widepayItens,
                    'boleto' => array(
                        'gerar' => 'Nao',
                        'desconto' => 0,
                        'multa' => $widepayFine,
                        'juros' => $widepayInterest
                    )
                );
                // Enviando solicitação ao Wide Pay
                $dados = $wp->api('recebimentos/cobrancas/adicionar', $widepayData);
                //Verificando sucesso no retorno
                if (!$dados->sucesso) {
                    $validacao = '';

                    if ($dados->erro)
                        $order->add_order_note(__('Wide Pay: Erro (' . $dados->erro . ')'));

                    if ($dados->validacao) {
                        foreach ($dados->validacao as $item) {
                            $validacao .= '- ' . strtoupper($item['id']) . ': ' . $item['erro'] . '<br>';
                        }
                        $order->add_order_note(__('Wide Pay: Erro de validação (' . $validacao . ')'));
                    }
                    echo '<div class="alert alert-danger" role="alert">Wide Pay: ' . $dados->erro . '<br>' . $validacao . '</div>';
                    return;
                }
                //Caso sucesso, será enviada ao banco de dados
                $this->widepay_save_invoice($order_id, $widepayTotal, $widepayTaxType, $widepayFine, $widepayInterest, $invoiceDuedate, $dados->id, $dados->link, $cpf_cnpf);
                $link = $dados->link;
            } else {
                $link = $widepayInvoice['link'];
            }
            //Exibindo link para pagamento
            echo "<script>window.location = '$link';</script>
                  <br><a class='btn btn-success' href='$link'>Pagar agora com Wide Pay</a>";
        }

        function extract_data_3($order_id)
        {
            $order = new WC_Order($order_id);
            $data = $order->get_data();
            return [
                'firstname' => $data['billing']['first_name'],
                'lastname' => $data['billing']['last_name'],
                'phone' => $data['billing']['phone'],
                'email' => $data['billing']['email'],
                'address1' => $data['billing']['address_1'],
                'address2' => $data['billing']['address_2'],
                'city' => $data['billing']['city'],
                'state' => $data['billing']['state'],
                'postcode' => $data['billing']['postcode'],
                'total' => $data['total'],
                'date_created' => $data['date_created']->date('Y-m-d H:i:s'),
                'cpf_cnpj' => get_post_meta($order_id, 'billing_cpf_cnpj', true)
            ];
        }
        function extract_data_2($order_id)
        {
            $post_meta = get_post_meta($order_id);
            $post = get_post($order_id);
            return [
                'firstname' => $post_meta['_billing_first_name'][0],
                'lastname' => $post_meta['_billing_last_name'][0],
                'phone' => (isset($post_meta['_billing_phone']))?$post_meta['_billing_phone'][0]: '',
                'email' => $post_meta['_billing_email'][0],
                'address1' => $post_meta['_billing_address_1'][0],
                'address2' => $post_meta['_billing_address_2'][0],
                'city' => $post_meta['_billing_city'][0],
                'state' => $post_meta['_billing_state'][0],
                'postcode' => $post_meta['_billing_postcode'][0],
                'total' => $post_meta['_order_total'][0],
                'cpf_cnpj' => $post_meta['billing_cpf_cnpj'][0],
                'date_created' => $post->post_date,
            ];
        }

        function widepay_save_invoice($order_id, $widepayTotal, $widepayTaxType, $widepayFine, $widepayInterest, $invoiceDuedate, $idTransaction, $linkWidePay, $cpf_cnpj)
        {
            $widepay_payment = new stdClass();
            $widepay_payment->order_id = $order_id;
            $widepay_payment->widepayTotal = $widepayTotal;
            $widepay_payment->widepayTaxType = $widepayTaxType;
            $widepay_payment->widepayFine = $widepayFine;
            $widepay_payment->widepayInterest = $widepayInterest;
            $widepay_payment->invoiceDuedate = $invoiceDuedate;
            $widepay_payment->idTransaction = $idTransaction;
            $widepay_payment->linkWidePay = $linkWidePay;
            $widepay_payment->cpf_cnpj = $cpf_cnpj;
            update_post_meta($order_id, 'widepay_payment', sanitize_text_field(serialize($widepay_payment)));
        }

        function widepay_get_invoice($order_id, $widepayTotal, $widepayTaxType, $widepayFine, $widepayInterest, $cpf_cnpj)
        {
            $widepay_payment = get_post_meta($order_id, 'widepay_payment', true);

            $widepay_payment_exists = (strlen($widepay_payment) > 0);
            if ($widepay_payment_exists) {
                $widepay_payment = unserialize($widepay_payment);
                $error_reported = false;
                if ($widepay_payment->widepayTotal != $widepayTotal ||
                    $widepay_payment->widepayTaxType != $widepayTaxType ||
                    $widepay_payment->widepayFine != $widepayFine ||
                    $widepay_payment->widepayInterest != $widepayInterest ||
                    $widepay_payment->cpf_cnpj != $cpf_cnpj ||
                    $widepay_payment->invoiceDuedate < date('Y-m-d')) {
                    $error_reported = true;
                }
                if (!$error_reported)
                    return ['success' => true, 'link' => $widepay_payment->linkWidePay];
            }
            return ['success' => false];
        }
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_widepay_gateway');
    function woocommerce_add_widepay_gateway($methods)
    {
        $methods[] = 'WC_WidePay_Payment';
        return $methods;
    }
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'widepay_action_links');
function widepay_action_links($links)
{
    $links_edited = array(
        '<a href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section=widepay">Configurações</a>'
    );
    $links_edited = array_merge($links_edited, $links);
    $links_edited = array_merge($links_edited, [
        '<a href="https://www.widepay.com/" target="_blank">Suporte Wide Pay</a>'
    ]);
    return $links_edited;

}

add_action('woocommerce_checkout_process', 'validate_cpf_cnpj');
function validate_cpf_cnpj()
{
    $cpf_cnpf = preg_replace("/[^0-9]/", "", $_POST['billing_cpf_cnpj']);
    if ($_POST['payment_method'] == 'widepay' && strlen($cpf_cnpf) != 11 && strlen($cpf_cnpf) != 14)
        wc_add_notice(__('O Campo CPF ou CNPJ está inválido'), 'error');
}

add_action('woocommerce_checkout_update_order_meta', 'store_cpf_cnpj');
add_action('woocommerce_before_pay_action', 'store_cpf_cnpj');
function store_cpf_cnpj($param)
{
    if (version_compare(WOOCOMMERCE_VERSION, '3.0.0', '>=')) {
        $order_id = is_int($param) ? $param : $param->get_id();
    } else {
        $order_id = is_int($param) ? $param : $param->post->ID;
    }
    update_post_meta($order_id, 'billing_cpf_cnpj', sanitize_text_field(preg_replace("/[^0-9]/", "", $_POST['billing_cpf_cnpj'])));
}
