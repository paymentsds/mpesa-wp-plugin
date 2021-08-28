=== PaymentsDS - Mpesa Payment Gateway for WooCommerce  ===
Contributors: ltsaiete, karson9
Author URI: https://developers.paymentsds.org/
Plugin URL: https://wordpress.org/plugins/wc-paymentsds-mpesa/
Tags:  mpesa, m-pesa, woocommerce, mpesa api, gateway, Mobile Payments, mpesa online, Vodacom, Mpesa API Mozambique, Payments, PaymentsDS, Woocommerce payment gateway
Requires at least: 5.0
Tested up to: 5.8
Requires PHP: 7.0
Stable tag: 1.0.1
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.en.html

Receba pagamentos na sua loja virtual com Woocommerce através do Mpesa.

== Description ==

O plugin *PaymentsDS - Mpesa Payment Gateway for WooCommerce* é uma extensão para WooCommerce e WordPress que permite que você receba pagamentos diretamente em sua loja virtual através do M-Pesa da Vodacom Moçambique.
Por meio do *PaymentsDS - Mpesa Payment Gateway for WooCommerce*, os compradores de sua loja podem comprar produtos ou serviços em sua loja usando um número de telefone associado à conta M-Pesa.

== Other Notes ==

### Pré requisitos
Para usar o plugin é necessário:
* Ter [WooCommerce](https://wordpress.org/plugins/woocommerce) instalado.
* Criar uma conta no [portal de desenvolvedores do M-pesa](https://developer.mpesa.vm.co.mz/) onde irá obter as credenciais necessárias para configurar a conta.
* Solicite ao provedor de hospedagem que abra a conexão de saída no firewall para a porta *18352*.

### Dúvidas

Se tiver  alguma dúvida :

* Visite a nossa sessão de [Perguntas Frequentes](https://wordpress.org/plugins/wc-paymentsds-mpesa/#faq).
* Utilize o nosso fórum no [Github](https://github.com/paymentsds/mpesa-wp-plugin/issues).
* Crie um tópico no [fórum de ajuda do WordPress](https://wordpress.org/support/plugin/wc-paymentsds-mpesa/).

## Contribuir

Você pode contribuir com o código fonte no nosso [GitHub](https://github.com/paymentsds/mpesa-wp-plugin).

## Disclaimer

Este plugin foi desenvolvido sem nenhum incentivo da Vodacom. Nenhum dos desenvolvedores deste plugin possuem vínculos com estas duas empresas.

== Instalação ==

### Instalação automática

1. Faça login no seu painel do WordPress
2. Clique em *Plugins > Adicionar novo* no menu esquerdo.
3. Na caixa de pesquisa, digite **PaymentsDS - Mpesa Payment Gateway for WooCommerce**.
4. Clique em *Instalar agora* no **PaymentsDS - Mpesa Payment Gateway for WooCommerce** para instalar o plug-in no seu site e em seguida clique em  *ativar* o plug-in.

### Instalação manual

Caso a instalação automática não funcione, faça o download do plug-in aqui usando o botão Download.

1. Descompacte o arquivo e carregue a pasta via FTP no diretório *wp-content/plugins* da sua instalação do WordPress.
2. Vá para *Plugins > Plugins instalados* e clique em *Ativar* no PaymentsDS - Mpesa Payment Gateway for WooCommerce.

### Configuração

Após instalar e activar o plugin:
1. Clique em *WooCommerce > Configurações* no menu esquerdo e clique na guia *Pagamentos*.
2. Clique em **Mpesa for WooCommerce** na lista dos métodos de pagamento disponíveis
3. Defina as configurações do Mpesa for WooCommerce usando credenciais disponíveis no [Mpesa developer portal.](https://developer.mpesa.vm.co.mz/)

== Screenshots ==

1. Lista dos método de pagamento com o  *Mpesa for WooCommerce* ativo
2. Configuração das credenciais método de pagamento Mpesa.
3. Página da Finalização do pagamento com o método de pagamento selecionado com o campo para digitar o número do telefone mpesa
4. Página de pagamento com as instruções para que o cliente finalize o pagamento.

== Frequently Asked Questions ==

= Onde encontro as credenciais para configurar o plug-in? =

Para obter credenciais, crie uma conta em https://developer.mpesa.vm.co.mz/

= O que devo colocar no campo Código do provedor de serviços? =

* Se você estiver no ambiente de teste, use **171717**
* Se você estiver no ambiente de produção, use código de produção fornecido pela Vodacom.

= Não recebo notificação no processo de pagamento. O que deve ser? =

Se ao fazer o pedido de pagamento, não recebe nenhuma notificação e da timeout no final, provavelmente é resultado do firewall no servidor que está bloqueando as portas de saída. Solicite ao provedor de hospedagem que abra a conexão de saída para a porta 18352.

== Changelog ==

= 1.0 =

Adicionando suporte à refund. Mudanças no layout da página de pagamento.

= 0.1.1 =

Primeiro lançamento.
