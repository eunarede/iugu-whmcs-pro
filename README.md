# iugu-whmcs

- Envie todas as pastas e documentos para a raiz do WHMCS.
- Acesse o admin do WHMCS.
- Entre em Setup -> Addon Modules.
- Encontre o addon da Iugu e clique em Activate.
- Entre em Setup -> Custom Client Fields e insira um novo campo. Obrigatoriamente o Field Name deve ser "CPF/CNPJ" (sem aspas).
- Entre em Setup -> Payments -> Payment Gateways e selecione a aba All Payment Gateways (Apenas na V6).
- Encontre a Iugu na lista e clique em Ativar.
- Selecione a aba Manage Existing Gateways (Apenas na V6).
- Informe o Token da Iugu.
- Clique em Save Changes.

Baseado no [domhost/iugu-whmcs][1]

[1]: https://github.com/domhost/iugu-whmcs
