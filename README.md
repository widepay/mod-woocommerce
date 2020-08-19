#  Módulo WooCommerce para Wide Pay
Módulo desenvolvido para integração entre o WooCommerce e Wide Pay. Com o módulo é possível gerar boletos para pagamento e liquidação automática pelo Wide Pay após o recebimento.

* **Versão atual:** 1.0.12
* **Versão WooCommerce Testada:** 2.6.x^
* **Acesso Wide Pay**: [Abrir Link](https://www.widepay.com/acessar)
* **API Wide Pay**: [Abrir Link](https://widepay.github.io/api/index.html)
* **Módulos Wide Pay**: [Abrir Link](https://widepay.github.io/api/modulos.html)

# Instalação Plugin

1. Para a instalação do plugin realize o download pelo link: https://github.com/widepay/mod-woocommerce
2. Após o download concluído, acesse o menu de plugins do WordPress, clique em Adicionar. Selecione o arquivo *.zip*. Clique em Upload.
3. Clique em Instalar.
4. Clique em Ativar.

# Configuração do Plugin
Lembre-se que para esta etapa, o plugin deve estar instalado e ativado no sistema Wordpress.

A configuração do Plugin Wide Pay pode ser encontrada no menu: Wordpress -> Plugins -> Woo Wide Pay -> Configurações. Ou Wordpress -> WooCommerce -> Settings -> Checkout -> Wide Pay.




Para configuração do Wide Pay é preciso que pelo menos os 5 campos obrigatórios sejam preenchidos. Segue a lista dos campos e descrição.

|Campo|Obrigatório|Descrição|
|--- |--- |--- |
|Title|**Sim**|Nome do método de pagamento que será exibido no checkout|
|Descrição|**Sim**|Descrição do método de pagamento que será exibido no checkout|
|Item na fatura|**Sim**|Descrição do item presente na fatura Wide Pay|
|ID da Carteira Wide Pay |**Sim** |Preencha este campo com o ID da carteira que deseja receber os pagamentos do sistema. O ID de sua carteira estará presente neste link: https://www.widepay.com/conta/configuracoes/carteiras|
|Token da Carteira Wide Pay|**Sim**|Preencha com o token referente a sua carteira escolhida no campo acima. Clique no botão: "Integrações" na página do Wide Pay, será exibido o Token|
|Taxa de Variação|Não|O valor final da fatura será recalculado de acordo com este campo.|
|Tipo da Taxa de Variação|Não|O campo acima "Taxa de Variação" será aplicado de acordo com este campo.|
|Acréscimo de Dias no Vencimento|Não|Qual a quantidade de dias para o vencimento após a data da geração da fatura.|
|Configuração de Multa|Não|Configuração de multa após o vencimento, máximo 20|
|Configuração de Juros|Não|Configuração de juros após o vencimento, máximo 20|
