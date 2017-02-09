# Iugu WHMCS Pro

# Atenção: Este módulo encontra-se em fase de finalização e homologação, por favor não o utilize em ambiente de produção

O Módulo para WHMCS Iugu Pro desenvolvido pela EunaRede proporciona uma integração completa e transparente com o gateway de pagamento [Iugu](https://iugu.com)

Este módulo consiste em dois métodos de pagamento diferentes, boleto bancário e cartão de crédito, confira os recursos a seguir:

## Módulo Cartão de Crédito

![Tela do cartão de crédito](img/frontend-credit-card.png)

Através do método de pagamento por cartão de crédito do Módulo WHMCS Iugu Pro, é possível realizar o recebimento de faturas via cartão de crédito diretamente no WHMCS, sem necessidade de redirecionamento. O módulo utiliza os campos originais de cartão de crédito do WHMCS sem necessidade de modificação no tema.

Ao realizar um pedido ou na atualização do cartão de crédito no perfil de um cliente já existente, o módulo captura os dados do cartão e utilizando a API da Iugu, gera um código único criptogrado: o token de pagamento.

Os dados do cartão de crédito são criptografados e armazenados nos servidores da Iugu, gerando um token de representação destes dados, que são atrelados ao cliente dentro do sistema da Iugu. Este token então é armazenado e vinculado a conta do cliente no WHMCS.

Através do token de pagamento, o WHMCS poderá realizar capturas automáticas de pedidos e cobranças recorrentes de faturas no cartão de crédito. O WHMCS armazena apenas a data de vencimento do cartão, os 4 últimos digitos e bandeira (estas informações são utilizadas para alertas de vencimento do cartão).

### Com este módulo você poderá:

* Realizar a cobrança da fatura sem redirecionar o cliente para o site da Iugu;
* Capturar os cartão de crédito de forma transparente;
* Capturar faturas recorrentes automaticamente;
* Realizar cobranças de qualquer fatura gerada no WHMCS para o cliente;
* Cadastrar o cliente do WHMCS na Iugu automaticamente;
* Excluir o cliente na Iugu quando excluido no WHMCS;
* Atualizar os dados do cartão do cliente diretamente no WHMCS;

## Módulo Boleto Bancário

![Boleto Bancário](img/frontend-bank_slip.png)

Através do método de pagamento por boleto bancário do Módulo WHMCS Iugu Pro, é possível gerar o boleto diretamente no WHMCS, sem necessidade de redirecionamento.

### Com este módulo você poderá:

* Gerar o boleto sem redirecionar o cliente para o site da Iugu;
* Dar baixa na fatura automaticamente após a compensação (retorno automático);
* Cadastrar o cliente do WHMCS na Iugu automaticamente;
* Excluir o cliente na Iugu quando excluido no WHMCS;

# Instalação

Acesse nosso Wiki para consultar a [documentação de instalação](https://github.com/eunarede/iugu-whmcs-pro/wiki/Instala%C3%A7%C3%A3o) deste módulo.

# Suporte

Você pode exclarecer suas dúvidas ou pedir ajuda abrindo uma [issue](https://github.com/eunarede/iugu-whmcs-pro/issues) neste repositório.

# Créditos

Este módulo foi desenvolvido pela [EunaRede](https://www.eunarede.com) sem nenhum incentivo da Iugu. Nenhum dos desenvolvedores deste plugin possuem vínculos com esta empresa.

Este plugin foi feito baseado na [documentação oficial da Iugu](https://iugu.com/referencias/api).

# Licença
