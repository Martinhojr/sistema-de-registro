<?php
// Se existir o link público completo da Railway, usamos ele. Caso contrário, monta o PDO normal.
$db_url = getenv('MYSQL_PUBLIC_URL');

try {
    if ($db_url) {
        // Extrai os dados automaticamente do link público da Railway
        $url = parse_url($db_url);
        $host     = $url["host"];
        $port     = $url["port"];
        $username = $url["user"];
        $password = $url["pass"];
        $dbname   = substr($url["path"], 1);
    } else {
        // Teu plano B para rodar no teu PC local
        $host     = 'localhost';
        $dbname   = 'sistema_registo_cidadao';
        $username = 'root';
        $password = '';
        $port     = '3306';
    }

    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch(PDOException $e) {
    die("Erro na ligação à base de dados: " . $e->getMessage());
}
// Função para validar BI (formato simples)
function validarBI($numero_bi) {
    // Formato esperado: 9 dígitos + 2 letras + 3 dígitos (ex: 001234567LA045)
    return preg_match('/^\d{9}[A-Z]{2}\d{3}$/', $numero_bi);
}

// Processar formulário de registo
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Recolher e sanitizar dados
        $dados = [
            'numero_bi' => strtoupper(trim($_POST['numero_bi'] ?? '')),
            'data_emissao' => $_POST['data_emissao'] ?? '',
            'data_validade' => $_POST['data_validade'] ?? '',
            'arquivo_identificacao' => strtoupper(trim($_POST['arquivo_identificacao'] ?? '')),
            'nome_completo' => trim($_POST['nome_completo'] ?? ''),
            'data_nascimento' => $_POST['data_nascimento'] ?? '',
            'genero' => $_POST['genero'] ?? '',
            'naturalidade' => trim($_POST['naturalidade'] ?? ''),
            'pai_nome' => trim($_POST['pai_nome'] ?? ''),
            'mae_nome' => trim($_POST['mae_nome'] ?? ''),
            'endereco' => trim($_POST['endereco'] ?? ''),
            'bairro' => trim($_POST['bairro'] ?? ''),
            'municipio' => trim($_POST['municipio'] ?? ''),
            'provincia' => $_POST['provincia'] ?? '',
            'telefone' => trim($_POST['telefone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'estado_civil' => $_POST['estado_civil'] ?? '',
            'profissao' => trim($_POST['profissao'] ?? ''),
            'altura_cm' => !empty($_POST['altura_cm']) ? (int)$_POST['altura_cm'] : null,
            'olhos_cor' => trim($_POST['olhos_cor'] ?? '')
        ];

        // Validações básicas
        if (!validarBI($dados['numero_bi'])) {
            throw new Exception('Formato de BI inválido. Use 9 dígitos + 2 letras + 3 dígitos (ex: 001234567LA045)');
        }

        // Inserir na base de dados
        $sql = "INSERT INTO cidadaos (
                    numero_bi, data_emissao, data_validade, arquivo_identificacao,
                    nome_completo, data_nascimento, genero, naturalidade,
                    pai_nome, mae_nome, endereco, bairro, municipio, provincia,
                    telefone, email, estado_civil, profissao, altura_cm, olhos_cor
                ) VALUES (
                    :numero_bi, :data_emissao, :data_validade, :arquivo_identificacao,
                    :nome_completo, :data_nascimento, :genero, :naturalidade,
                    :pai_nome, :mae_nome, :endereco, :bairro, :municipio, :provincia,
                    :telefone, :email, :estado_civil, :profissao, :altura_cm, :olhos_cor
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($dados);

        $mensagem = 'Cidadão registado com sucesso!';
        $tipo_mensagem = 'sucesso';
    } catch (Exception $e) {
        $mensagem = 'Erro: ' . $e->getMessage();
        $tipo_mensagem = 'erro';
    }
}

// Listar cidadãos (para a tabela)
$stmt = $pdo->query("SELECT id, nome_completo, numero_bi, provincia, data_nascimento, telefone, email FROM cidadaos ORDER BY id DESC LIMIT 10");
$cidadaos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registo Nacional · Cidadão Angolano</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f0f3f8;
            color: #1e293b;
            line-height: 1.5;
            padding: 24px;
        }

        .dashboard {
            max-width: 1440px;
            margin: 0 auto;
        }

        /* Header estilo Plutus */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            background: linear-gradient(135deg, #6c5ce7, #a594fd);
            width: 48px;
            height: 48px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            box-shadow: 0 12px 22px -10px #6c5ce7;
        }

        .logo-text h1 {
            font-size: 24px;
            font-weight: 700;
            color: #0f172a;
        }

        .logo-text p {
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
        }

        .user-badge {
            display: flex;
            align-items: center;
            gap: 20px;
            background: white;
            padding: 8px 20px 8px 16px;
            border-radius: 60px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.02);
        }

        .user-badge i {
            color: #6c5ce7;
            font-size: 20px;
        }

        .user-badge .avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(145deg, #6c5ce7, #8f7dfc);
            border-radius: 50%;
        }

        /* Grid principal */
        .grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 24px;
            margin-bottom: 30px;
        }

        /* Card de formulário */
        .form-card {
            background: white;
            border-radius: 32px;
            padding: 28px;
            box-shadow: 0 20px 35px -10px rgba(0,0,0,0.03);
            border: 1px solid #ffffff50;
        }

        .card-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            color: #6c5ce7;
            background: #edeaff;
            padding: 10px;
            border-radius: 18px;
        }

        .card-sub {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 28px;
            border-left: 3px solid #6c5ce7;
            padding-left: 16px;
            background: #f8fafd;
            border-radius: 0 12px 12px 0;
            line-height: 1.4;
        }

        /* Formulário */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .full-width {
            grid-column: span 2;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .input-group label {
            font-weight: 600;
            font-size: 13px;
            color: #475569;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .input-group label i {
            color: #6c5ce7;
            font-size: 13px;
        }

        .input-group input,
        .input-group select,
        .input-group textarea {
            padding: 14px 18px;
            border: 2px solid #e9edf5;
            border-radius: 22px;
            font-size: 15px;
            transition: 0.2s;
            background: #ffffff;
            font-family: 'Inter', sans-serif;
        }

        .input-group input:focus,
        .input-group select:focus,
        .input-group textarea:focus {
            border-color: #6c5ce7;
            outline: none;
            box-shadow: 0 0 0 4px #e1daff;
        }

        .input-group textarea {
            resize: vertical;
            min-height: 90px;
        }

        .btn-submit {
            background: #6c5ce7;
            color: white;
            border: none;
            padding: 16px 28px;
            border-radius: 40px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: 0.2s;
            box-shadow: 0 12px 22px -10px #6c5ce7;
            width: 100%;
            margin-top: 16px;
        }

        .btn-submit:hover {
            background: #5b4bc4;
            transform: scale(0.98);
        }

        .mensagem {
            padding: 16px 22px;
            border-radius: 40px;
            margin-bottom: 24px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sucesso {
            background: #e6faf5;
            color: #0d7c5a;
            border-left: 5px solid #2cc197;
        }

        .erro {
            background: #fff1f0;
            color: #b34033;
            border-left: 5px solid #f07b7b;
        }

        /* Card de cidadãos recentes */
        .recent-card {
            background: white;
            border-radius: 32px;
            padding: 28px;
            box-shadow: 0 20px 35px -10px rgba(0,0,0,0.03);
        }

        .recent-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .recent-header h3 {
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .badge {
            background: #f0f2fa;
            padding: 4px 12px;
            border-radius: 40px;
            font-size: 13px;
            font-weight: 600;
            color: #3f4a6b;
        }

        .tabela-cidadaos {
            width: 100%;
            border-collapse: collapse;
        }

        .tabela-cidadaos th {
            text-align: left;
            padding: 12px 8px;
            color: #718096;
            font-weight: 600;
            font-size: 12px;
            border-bottom: 2px solid #edf2f7;
        }

        .tabela-cidadaos td {
            padding: 16px 8px;
            border-bottom: 1px solid #f0f4fa;
            font-size: 14px;
        }

        .tabela-cidadaos tr:hover td {
            background-color: #f9faff;
        }

        .id-chip {
            background: #6c5ce7;
            color: white;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 12px;
            display: inline-block;
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .contact-info small {
            color: #94a3b8;
            font-size: 11px;
        }

        .provincia-tag {
            background: #e6e9ff;
            color: #463aa7;
            padding: 4px 12px;
            border-radius: 40px;
            font-weight: 500;
            font-size: 12px;
            display: inline-block;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 24px;
        }

        .stat-mini {
            background: white;
            padding: 18px 20px;
            border-radius: 26px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 8px 22px -12px rgba(0,0,0,0.04);
        }

        .stat-mini i {
            font-size: 32px;
            color: #6c5ce7;
            background: #f1efff;
            padding: 12px;
            border-radius: 20px;
        }

        .stat-mini div p:first-child {
            font-weight: 600;
            font-size: 18px;
        }
        .stat-mini div p:last-child {
            color: #6f7c98;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="header">
            <div class="logo-area">
                <div class="logo-icon">
                    <i class="fas fa-id-card"></i>
                </div>
                <div class="logo-text">
                    <h1>Registo Nacional</h1>
                    <p>Base de dados do cidadão angolano · BI</p>
                </div>
            </div>
            <div class="user-badge">
                <i class="fas fa-bell"></i>
                <span>Admin</span>
                <div class="avatar"></div>
            </div>
        </div>

        <?php if ($mensagem): ?>
        <div class="mensagem <?= $tipo_mensagem ?>">
            <i class="fas <?= $tipo_mensagem === 'sucesso' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
            <?= htmlspecialchars($mensagem) ?>
        </div>
        <?php endif; ?>

        <div class="grid">
            <div class="form-card">
                <div class="card-title">
                    <i class="fas fa-pen-alt"></i> Novo registo de cidadão
                </div>
                <div class="card-sub">
                    <i class="fas fa-info-circle"></i> Preencha os dados conforme o Bilhete de Identidade (formato angolano)
                </div>

                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="input-group">
                            <label><i class="fas fa-qrcode"></i> Nº do BI *</label>
                            <input type="text" name="numero_bi" placeholder="001234567LA045" required pattern="\d{9}[A-Z]{2}\d{3}" title="9 dígitos + 2 letras + 3 dígitos">
                        </div>
                        <div class="input-group">
                            <label><i class="fas fa-calendar"></i> Data emissão *</label>
                            <input type="date" name="data_emissao" required>
                        </div>
                        <div class="input-group">
                            <label><i class="fas fa-calendar-check"></i> Data validade *</label>
                            <input type="date" name="data_validade" required>
                        </div>
                        <div class="input-group">
                            <label><i class="fas fa-barcode"></i> Arquivo identificação *</label>
                            <input type="text" name="arquivo_identificacao" placeholder="001234567 LA 045" required>
                        </div>

                        <div class="full-width input-group">
                            <label><i class="fas fa-user"></i> Nome completo *</label>
                            <input type="text" name="nome_completo" placeholder="Nome completo conforme BI" required>
                        </div>
                        <div class="input-group">
                            <label><i class="fas fa-cake"></i> Data nascimento *</label>
                            <input type="date" name="data_nascimento" required>
                        </div>
                        <div class="input-group">
                            <label><i class="fas fa-venus-mars"></i> Género *</label>
                            <select name="genero" required>
                                <option value="">Selecionar</option>
                                <option value="Masculino">Masculino</option>
                                <option value="Feminino">Feminino</option>
                                <option value="Outro">Outro</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label><i class="fas fa-map-pin"></i> Naturalidade *</label>
                            <input type="text" name="naturalidade" placeholder="Província de nascimento" required>
                        </div>
                        <div class="input-group">
                            <label><i class="fas fa-user-tie"></i> Nome do pai</label>
                            <input type="text" name="pai_nome" placeholder="Nome completo">
                        </div>
                        <div class="input-group">
                            <label><i class="fas fa-user-tie"></i> Nome da mãe</label>
                            <input type="text" name="mae_nome" placeholder="Nome completo">
                        </div>

                        <div class="full-width input-group">
                            <label><i class="fas fa-map-marker-alt"></i> Endereço *</label>
                            <textarea name="endereco" placeholder="Rua/Avenida, número, etc" required></textarea>
                        </div>
                        <div class="input-group">
                            <label><i class="fas fa-city"></i> Bairro</label>
                            <input type="text" name="bairro">
                        </div>
                        <div class="input-group">
                            <label><i class="fas fa-building"></i> Município *</label>
                            <input type="text" name="municipio" required>
                        </div>
                        <div class="input-group">
                            <label><i class="fas fa-globe-africa"></i> Província *</label>
                            <select name="provincia" required>
                                <option value="">Selecionar</option>
                                <option value="Luanda">Luanda</option>
                                <option value="Benguela">Benguela</option>
                                <option value="Huambo">Huambo</option>
                                <option value="Lubango">Lubango</option>
                                <option value="Cabinda">Cabinda</option>
                                <option value="Malanje">Malanje</option>
                                <option value="Namibe">Namibe</option>
                                <option value="Uíge">Uíge</option>
                                <option value="Zaire">Zaire</option>
                                <option value="Cuanza Norte">Cuanza Norte</option>
                                <option value="Cuanza Sul">Cuanza Sul</option>
                                <option value="Lunda Norte">Lunda Norte</option>
                                <option value="Lunda Sul">Lunda Sul</option>
                                <option value="Bengo">Bengo</option>
                                <option value="Bié">Bié</option>
                                <option value="Cuando Cubango">Cuando Cubango</option>
                                <option value="Cunene">Cunene</option>
                                <option value="Huíla">Huíla</option>
                                <option value="Moxico">Moxico</option>
                                <option value="Cuando">Cuando</option>
                            </select>
                        </div>

                        <div class="input-group">
                            <label><i class="fas fa-phone-alt"></i> Telefone</label>
                            <input type="tel" name="telefone" placeholder="+244 923 456 789">
                        </div>
                        <div class="input-group">
                            <label><i class="fas fa-envelope"></i> E-mail</label>
                            <input type="email" name="email" placeholder="exemplo@email.com">
                        </div>

                        <div class="input-group">
                            <label><i class="fas fa-heart"></i> Estado civil</label>
                            <select name="estado_civil">
                                <option value="Solteiro(a)">Solteiro(a)</option>
                                <option value="Casado(a)">Casado(a)</option>
                                <option value="Divorciado(a)">Divorciado(a)</option>
                                <option value="Viúvo(a)">Viúvo(a)</option>
                                <option value="União de Facto">União de Facto</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label><i class="fas fa-briefcase"></i> Profissão</label>
                            <input type="text" name="profissao">
                        </div>
                        <div class="input-group">
                            <label><i class="fas fa-ruler-vertical"></i> Altura (cm)</label>
                            <input type="number" name="altura_cm" min="50" max="250" step="1">
                        </div>
                        <div class="input-group">
                            <label><i class="fas fa-eye"></i> Cor dos olhos</label>
                            <input type="text" name="olhos_cor" placeholder="Castanhos, pretos...">
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Registar Cidadão
                    </button>
                </form>
            </div>

            <div class="recent-card">
                <div class="recent-header">
                    <h3><i class="fas fa-clock"></i> Últimos registos</h3>
                    <span class="badge">10 mais recentes</span>
                </div>
                <table class="tabela-cidadaos">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome / BI</th>
                            <th>Província</th>
                            <th>Contacto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cidadaos as $c): ?>
                        <tr>
                            <td><span class="id-chip">#<?= $c['id'] ?></span></td>
                            <td>
                                <strong><?= htmlspecialchars($c['nome_completo']) ?></strong><br>
                                <small style="color:#6c5ce7;"><?= htmlspecialchars($c['numero_bi']) ?></small>
                            </td>
                            <td><span class="provincia-tag"><?= htmlspecialchars($c['provincia']) ?></span></td>
                            <td>
                                <div class="contact-info">
                                    <?= htmlspecialchars($c['telefone'] ?: '—') ?>
                                    <small><?= htmlspecialchars($c['email'] ?: '') ?></small>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($cidadaos)): ?>
                        <tr>
                            <td colspan="4" style="text-align:center; padding:30px; color:#94a3b8;">
                                <i class="fas fa-database"></i> Nenhum cidadão registado ainda.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="stats-row">
            <div class="stat-mini">
                <i class="fas fa-users"></i>
                <div>
                    <p><?= count($cidadaos) ?>+</p>
                    <p>Cidadãos registados</p>
                </div>
            </div>
            <div class="stat-mini">
                <i class="fas fa-id-card"></i>
                <div>
                    <p>🇦🇴</p>
                    <p>BI angolano</p>
                </div>
            </div>
            <div class="stat-mini">
                <i class="fas fa-check-circle"></i>
                <div>
                    <p>✓ válido</p>
                    <p>documento activo</p>
                </div>
            </div>
            <div class="stat-mini">
                <i class="fas fa-shield-alt"></i>
                <div>
                    <p>seguro</p>
                    <p>LGPD / protecção</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
