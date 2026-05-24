<?php
session_start();

// Se já estiver logado, vai direto para o painel principal
if (isset($_SESSION['admin_logado'])) {
    header('Location: index.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_introduzido = $_POST['usuario'] ?? '';
    $senha_introduzida   = $_POST['senha'] ?? '';

    // Puxa as credenciais das variáveis de ambiente (Railway) ou usa o padrão local
    $admin_valido = getenv('ADMIN_USER') ?: 'admin';
    $senha_valida = getenv('ADMIN_PASS') ?: 'admin123';

    if ($usuario_introduzido === $admin_valido && $senha_introduzida === $senha_valida) {
        $_SESSION['admin_logado'] = true;
        $_SESSION['admin_user']   = $admin_valido;
        header('Location: index.php');
        exit;
    } else {
        $erro = 'Utilizador ou senha incorretos!';
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login · Registo Nacional</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #f0f3f8; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .login-card { background: white; padding: 40px; border-radius: 32px; width: 100%; max-width: 420px; box-shadow: 0 20px 35px -10px rgba(0,0,0,0.05); text-align: center; }
        .logo-icon { background: linear-gradient(135deg, #6c5ce7, #a594fd); width: 60px; height: 60px; border-radius: 22px; display: flex; align-items: center; justify-content: center; color: white; font-size: 28px; margin: 0 auto 20px; box-shadow: 0 12px 22px -10px #6c5ce7; }
        h2 { font-size: 24px; color: #0f172a; margin-bottom: 8px; }
        p { color: #64748b; font-size: 14px; margin-bottom: 32px; }
        .input-group { display: flex; flex-direction: column; gap: 8px; text-align: left; margin-bottom: 20px; }
        label { font-weight: 600; font-size: 13px; color: #475569; }
        input { padding: 14px 18px; border: 2px solid #e9edf5; border-radius: 22px; font-size: 15px; transition: 0.2s; outline: none; }
        input:focus { border-color: #6c5ce7; box-shadow: 0 0 0 4px #e1daff; }
        .btn-login { background: #6c5ce7; color: white; border: none; padding: 16px; border-radius: 40px; font-weight: 700; font-size: 16px; cursor: pointer; width: 100%; margin-top: 10px; transition: 0.2s; box-shadow: 0 12px 22px -10px #6c5ce7; }
        .btn-login:hover { background: #5b4bc4; transform: scale(0.99); }
        .erro { background: #fff1f0; color: #b34033; padding: 12px; border-radius: 20px; margin-bottom: 20px; font-size: 14px; font-weight: 500; border-left: 4px solid #f07b7b; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-icon"><i class="fas fa-lock"></i></div>
        <h2>Painel de Controlo</h2>
        <p>Introduza as suas credenciais de Administrador</p>

        <?php if ($erro): ?>
            <div class="erro"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="input-group">
                <label>Utilizador</label>
                <input type="text" name="usuario" placeholder="Ex: admin" required autocomplete="off">
            </div>
            <div class="input-group">
                <label>Palavra-passe</label>
                <input type="password" name="senha" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-login">Entrar no Sistema</button>
        </form>
    </div>
</body>
</html>
