<?php
// 1. Permitir que o Ionic (localhost:8100) acesse a API
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// 2. Responder às requisições "preflight" (OPTIONS) que o browser faz automaticamente
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}


// api_cidadao.php - API de consulta de cidadão por BI
// Uso: http://seudominio.com/api_cidadao.php?bi=001234567LA045

// Configuração de CORS para permitir acesso de outros domínios (caso necessário)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Incluir configuração da base de dados (reutilizando o mesmo ficheiro)
// config.php - Configuração da base de dados
$host = 'localhost';
$dbname = 'sistema_registo_cidadao';
$username = 'root';      // Altere conforme seu ambiente
$password = '';          // Altere conforme seu ambiente

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erro na ligação à base de dados: " . $e->getMessage());
}
// Função para validar formato do BI (mesma do sistema de registo)
function validarBI($numero_bi) {
    return preg_match('/^\d{9}[A-Z]{2}\d{3}$/', $numero_bi);
}

// Função para limpar e formatar a resposta
function formatarDadosCidadao($cidadao) {
    return [
        'status' => 'sucesso',
        'dados' => [
            'numero_bi' => $cidadao['numero_bi'],
            'arquivo_identificacao' => $cidadao['arquivo_identificacao'],
            'data_emissao' => $cidadao['data_emissao'],
            'data_validade' => $cidadao['data_validade'],
            'validade' => [
                'expirado' => ($cidadao['data_validade'] < date('Y-m-d')),
                'dias_restantes' => diasRestantes($cidadao['data_validade'])
            ],
            'nome_completo' => $cidadao['nome_completo'],
            'data_nascimento' => $cidadao['data_nascimento'],
            'idade' => calcularIdade($cidadao['data_nascimento']),
            'genero' => $cidadao['genero'],
            'naturalidade' => $cidadao['naturalidade'],
            'filiacao' => [
                'pai' => $cidadao['pai_nome'],
                'mae' => $cidadao['mae_nome']
            ],
            'morada' => [
                'endereco' => $cidadao['endereco'],
                'bairro' => $cidadao['bairro'],
                'municipio' => $cidadao['municipio'],
                'provincia' => $cidadao['provincia']
            ],
            'contacto' => [
                'telefone' => $cidadao['telefone'],
                'email' => $cidadao['email']
            ],
            'estado_civil' => $cidadao['estado_civil'],
            'profissao' => $cidadao['profissao'],
            'dados_biometricos' => [
                'altura_cm' => $cidadao['altura_cm'],
                'olhos_cor' => $cidadao['olhos_cor']
            ],
            'registo' => [
                'data_registo' => $cidadao['data_registo'],
                'status' => $cidadao['status_registo']
            ]
        ]
    ];
}

// Função auxiliar para calcular idade
function calcularIdade($data_nascimento) {
    $nasc = new DateTime($data_nascimento);
    $hoje = new DateTime();
    $idade = $hoje->diff($nasc);
    return $idade->y;
}

// Função auxiliar para dias restantes até validade
function diasRestantes($data_validade) {
    $validade = new DateTime($data_validade);
    $hoje = new DateTime();
    $diferenca = $hoje->diff($validade);
    return $validade < $hoje ? -$diferenca->days : $diferenca->days;
}

// --- Lógica principal da API ---

// Verificar se foi fornecido o parâmetro 'bi'
if (!isset($_GET['bi']) || empty(trim($_GET['bi']))) {
    http_response_code(400); // Bad Request
    echo json_encode([
        'status' => 'erro',
        'codigo' => 400,
        'mensagem' => 'Parâmetro BI é obrigatório. Use ?bi=XXXXXXXXXXX'
    ]);
    exit;
}

$bi = strtoupper(trim($_GET['bi']));

// Validar formato do BI
if (!validarBI($bi)) {
    http_response_code(400); // Bad Request
    echo json_encode([
        'status' => 'erro',
        'codigo' => 400,
        'mensagem' => 'Formato de BI inválido. Utilize 9 dígitos + 2 letras + 3 dígitos (ex: 001234567LA045)'
    ]);
    exit;
}

try {
    // Consultar o cidadão pelo número do BI
    $sql = "SELECT * FROM cidadaos WHERE numero_bi = :bi AND status_registo = 'Ativo'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':bi' => $bi]);
    
    $cidadao = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cidadao) {
        // Cidadão encontrado - retorna 200 OK com os dados
        http_response_code(200);
        echo json_encode(formatarDadosCidadao($cidadao), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        // BI não encontrado ou inativo
        http_response_code(404); // Not Found
        echo json_encode([
            'status' => 'erro',
            'codigo' => 404,
            'mensagem' => 'Cidadão não encontrado com o BI fornecido ou registo inativo.'
        ]);
    }
    
} catch (PDOException $e) {
    // Erro interno do servidor (ex: base de dados fora do ar)
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'status' => 'erro',
        'codigo' => 500,
        'mensagem' => 'Erro interno no servidor. Tente novamente mais tarde.'
    ]);
    
    // Log do erro (opcional, para depuração)
    error_log("API Cidadão - Erro BD: " . $e->getMessage());
}
?>
