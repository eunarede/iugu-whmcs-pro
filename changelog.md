# 1.6.0
+ implementada a geração de segunda via da fatura caso a atual esteja vencida.
+ implementada mensagem de erro quando fatura expirada;
+ removida a geração de clientes da Iugu.
+ Removida a biblioteca Iugu PHP.
+ Implementada a comunicação com a API através da biblioteca [Unirest PHP](https://github.com/Mashape/unirest-php).
+ +implementado gatilho para cancelamento da fatura Iugu caso a fatura vinculada no WHMCS for cancelada.
# 1.5.9
+ [fix] Inserido o parametro _number_ para cadastramento do cliente na Iugu para constante '000'. API Iugu exige que ao enviar o parametro _zip_code_ seja enviado _number_, WHMCS não possui este parametro, o que ocasionava problemas no cadastramento do cliente como mencionado na issue #14
# 1.5.8
+ corrigido erro 500 quando a fatura está vencida. Caso esteja a data do boleto é alterada para o dia atual #14
+ adicionado o campo personalizado de CPF/CNPJ para cadastramento na Iugu para atender a nova exigencia para boletos registrados #9
+ corrigido o problema de cadastramendo do cliente na Iugu quando é alterado a forma de pagamento entre cartão/boleto boleto/cartão #12 #14
# 1.5.7
+ Remoção do submodulo do SDK da Iugu.
+ Correção do diretório do SDK Iugu.
+ Provavelmente resolva o problema reportado aqui #12
# 1.5.3.1
+ correção de erro 505 após ativar o módulo #5
+ aprimoramento dos dados enviados a Iugu para cadastro do cliente #6
# v1.5.3
+ remoção de campo de usuário API e atualização da documentação
# v1.5.2
+ tratamento de geração de faturas no Painel Iugu para evitar duplicidades de faturas para a mesma Invoice do WHMCS
+ Criação de tabela adicional e módulo addon para ativação
+ pequenas melhorias nos tratamentos de erros
# v1.5.1
+ Inserção de variável para pagamento via boleto bancário;
# v1.5
+ Separação dos meios de pagamentos;
+ Tratamento de todas as variaveis para normatização com WHMCS v6.x;
+ Tratamento do retorno dos pagamentos de formas individuais (CC e Boleto);
