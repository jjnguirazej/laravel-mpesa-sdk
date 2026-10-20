<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ambiente
    |--------------------------------------------------------------------------
    |
    | Ambiente da API M-Pesa a utilizar: "sandbox" (testes) ou "production".
    | O valor por omissão é "sandbox". O pacote nunca muda para produção
    | sem que este valor seja definido explicitamente.
    |
    */

    'environment' => env('MPESA_ENV', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | Host da API (opcional)
    |--------------------------------------------------------------------------
    |
    | Substitui o host associado ao ambiente (api.sandbox.vm.co.mz ou
    | api.vm.co.mz). Indique apenas o nome do host, sem "https://", porta
    | ou caminho. Deixe vazio na maioria dos casos.
    |
    */

    'api_host' => env('MPESA_API_HOST'),

    /*
    |--------------------------------------------------------------------------
    | Credenciais
    |--------------------------------------------------------------------------
    |
    | "api_key" e "public_key" são fornecidas no portal de desenvolvedores
    | da Vodacom (https://developer.mpesa.vm.co.mz). A chave pública pode ser
    | indicada só com o conteúdo base64 ou em formato PEM completo.
    |
    */

    'api_key' => env('MPESA_API_KEY'),

    'public_key' => env('MPESA_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Código do provedor de serviço (shortcode)
    |--------------------------------------------------------------------------
    |
    | Enviado como "input_ServiceProviderCode" (e "input_PrimaryPartyCode" no
    | B2B). MPESA_PROVIDER_NUMBER é aceite por compatibilidade com o pacote
    | samuelbie/mpesa.
    |
    */

    'service_provider_code' => env('MPESA_SERVICE_PROVIDER_CODE', env('MPESA_PROVIDER_NUMBER')),

    /*
    |--------------------------------------------------------------------------
    | Cabeçalho Origin
    |--------------------------------------------------------------------------
    |
    | Valor do cabeçalho "Origin" enviado em todos os pedidos.
    |
    */

    'origin' => env('MPESA_ORIGIN', '*'),

    /*
    |--------------------------------------------------------------------------
    | Credenciais do iniciador (apenas para reversões)
    |--------------------------------------------------------------------------
    |
    | Necessárias apenas para a operação de reversão. As restantes operações
    | funcionam sem estes valores.
    |
    */

    'initiator_identifier' => env('MPESA_INITIATOR_IDENTIFIER'),

    'security_credential' => env('MPESA_SECURITY_CREDENTIAL'),

    /*
    |--------------------------------------------------------------------------
    | Tempos limite (segundos)
    |--------------------------------------------------------------------------
    |
    | "timeout" é o tempo máximo de espera pela resposta. No C2B a API só
    | responde depois de o cliente confirmar (ou não) o PIN no telemóvel,
    | por isso o valor por omissão é generoso. "connect_timeout" é o tempo
    | máximo para estabelecer a ligação.
    |
    */

    'timeout' => (int) env('MPESA_TIMEOUT', 120),

    'connect_timeout' => (int) env('MPESA_CONNECT_TIMEOUT', 10),

];
