<?php

session_start();

require_once __DIR__ . '/../config/db.php';

// Se já estiver logado, vai direto para o painel
if (isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '') {

        $erro = 'Preencha o e-mail e a senha.';

    } else {

        try {

            // Procura o administrador pelo e-mail
            $stmt = $pdo->prepare(
                "SELECT id, nome, email, senha
                 FROM usuarios
                 WHERE email = ?
                 LIMIT 1"
            );

            $stmt->execute([$email]);

            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verifica a senha criptografada
            if ($usuario && password_verify($senha, $usuario['senha'])) {

                // Regenera o ID da sessão por segurança
                session_regenerate_id(true);

                $_SESSION['usuario'] = $usuario['id'];
                $_SESSION['nome'] = $usuario['nome'];
                $_SESSION['email'] = $usuario['email'];

                header('Location: index.php');
                exit;

            } else {

                $erro = 'E-mail ou senha inválidos.';

            }

        } catch (PDOException $e) {

            $erro = 'Erro ao acessar o banco de dados.';

        }
    }
}

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Login - Painel Administrativo</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            color: #202020;
        }

        .container {
            width: 90%;
            max-width: 430px;
            margin: 80px auto;
        }

        .login-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.10);
        }

        h1 {
            margin-top: 0;
            margin-bottom: 8px;
            font-size: 30px;
        }

        .subtitulo {
            margin-top: 0;
            margin-bottom: 25px;
            color: #555;
        }

        label {
            display: block;
            margin-top: 18px;
            margin-bottom: 7px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }

        input:focus {
            outline: none;
            border-color: #202020;
        }

        button {
            width: 100%;
            margin-top: 22px;
            padding: 13px;
            border: none;
            border-radius: 6px;
            background: #202020;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #333;
        }

        .erro {
            background: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
            padding: 13px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .voltar {
            text-align: center;
            margin-top: 20px;
        }

        .voltar a {
            color: #555;
            text-decoration: none;
        }

        .voltar a:hover {
            text-decoration: underline;
        }

    </style>

    <link rel="stylesheet" href="../assets/css/admin.css">

</head>

<body>

<div class="container">

    <div class="login-box">

        <h1>Painel Administrativo</h1>

        <p class="subtitulo">
            Entre para gerenciar o cardápio.
        </p>

        <?php if ($erro !== ''): ?>

            <div class="erro">

                <?= htmlspecialchars($erro) ?>

            </div>

        <?php endif; ?>

        <form method="POST">

            <label for="email">
                E-mail
            </label>

            <input
                type="email"
                id="email"
                name="email"
                placeholder="Digite seu e-mail"
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                required
            >

            <label for="senha">
                Senha
            </label>

            <input
                type="password"
                id="senha"
                name="senha"
                placeholder="Digite sua senha"
                required
            >

            <button type="submit">
                Entrar
            </button>

        </form>

        <div class="voltar">

            <a href="../index.php">
                ← Voltar para o cardápio
            </a>

        </div>

    </div>

</div>

</body>

</html>
