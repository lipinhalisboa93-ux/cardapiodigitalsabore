<?php
require_once 'auth.php';

$erro = '';
$sucesso = '';

/* =========================
   BUSCAR CONFIGURAÇÕES
========================= */

$stmt = $pdo->query(
    "SELECT * FROM configuracoes ORDER BY id LIMIT 1"
);

$config = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$config) {

    $pdo->exec("
        INSERT INTO configuracoes
        (
            nome_estabelecimento,
            descricao,
            whatsapp,
            endereco,
            horario,
            logo
        )
        VALUES
        (
            'Cardápio Digital',
            'Escolha seus produtos favoritos',
            '',
            '',
            '',
            ''
        )
    ");

    $stmt = $pdo->query(
        "SELECT * FROM configuracoes ORDER BY id LIMIT 1"
    );

    $config = $stmt->fetch(PDO::FETCH_ASSOC);
}


/* =========================
   SALVAR CONFIGURAÇÕES
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim(
        $_POST['nome_estabelecimento'] ?? ''
    );

    $descricao = trim(
        $_POST['descricao'] ?? ''
    );

    $whatsapp = trim(
        $_POST['whatsapp'] ?? ''
    );

    $endereco = trim(
        $_POST['endereco'] ?? ''
    );

    $horario = trim(
        $_POST['horario'] ?? ''
    );

    $logo = $config['logo'] ?? '';


    if ($nome === '') {

        $erro = 'Digite o nome do estabelecimento.';

    }


    /* =========================
       UPLOAD DA LOGO
    ========================= */

    if (
        $erro === '' &&
        isset($_FILES['logo']) &&
        $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES['logo']['error'] !== UPLOAD_ERR_OK) {

            $erro = 'Não foi possível enviar a logo.';

        } else {

            $permitidos = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            $mime = mime_content_type(
                $_FILES['logo']['tmp_name']
            );

            if (!isset($permitidos[$mime])) {

                $erro = 'A logo deve ser JPG, PNG ou WEBP.';

            } elseif (
                $_FILES['logo']['size'] > 5 * 1024 * 1024
            ) {

                $erro = 'A logo deve ter no máximo 5 MB.';

            } else {

                $pasta = __DIR__ . '/../uploads';

                if (!is_dir($pasta)) {

                    mkdir(
                        $pasta,
                        0755,
                        true
                    );
                }

                $nomeArquivo =
                    'logo_' .
                    bin2hex(random_bytes(8)) .
                    '.' .
                    $permitidos[$mime];

                $destino =
                    $pasta .
                    '/' .
                    $nomeArquivo;


                if (
                    move_uploaded_file(
                        $_FILES['logo']['tmp_name'],
                        $destino
                    )
                ) {

                    /* APAGA LOGO ANTIGA */

                    if (!empty($config['logo'])) {

                        $logoAntiga =
                            __DIR__ .
                            '/../' .
                            ltrim(
                                $config['logo'],
                                '/'
                            );

                        if (is_file($logoAntiga)) {

                            @unlink($logoAntiga);
                        }
                    }

                    $logo =
                        'uploads/' .
                        $nomeArquivo;

                } else {

                    $erro =
                        'Não foi possível salvar a logo.';
                }
            }
        }
    }


    /* =========================
       ATUALIZAR BANCO
    ========================= */

    if ($erro === '') {

        $stmt = $pdo->prepare("
            UPDATE configuracoes
            SET
                nome_estabelecimento = ?,
                descricao = ?,
                whatsapp = ?,
                endereco = ?,
                horario = ?,
                logo = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $nome,
            $descricao,
            $whatsapp,
            $endereco,
            $horario,
            $logo,
            $config['id']
        ]);

        header(
            'Location: configuracoes.php?salvo=1'
        );

        exit;
    }
}


/* MENSAGEM */

if (isset($_GET['salvo'])) {

    $sucesso =
        'Configurações salvas com sucesso!';
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

<title>
    Configurações - Cardápio Digital
</title>

<style>

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: #f4f6f8;

    color: #222;
}


/* MENU */

.sidebar {

    width: 250px;

    height: 100vh;

    position: fixed;

    left: 0;

    top: 0;

    background: #1d1f21;

    color: white;

    padding: 25px 18px;
}

.logo {

    font-size: 23px;

    font-weight: bold;

    margin-bottom: 8px;
}

.logo-sub {

    color: #aaa;

    font-size: 13px;

    margin-bottom: 35px;
}

.menu a {

    display: block;

    color: #ddd;

    text-decoration: none;

    padding: 13px 15px;

    margin-bottom: 7px;

    border-radius: 7px;
}

.menu a:hover {

    background: #34373a;

    color: white;
}

.menu .ativo {

    background: white;

    color: #202020;

    font-weight: bold;
}

.sair {

    position: absolute;

    bottom: 25px;

    left: 18px;

    right: 18px;
}

.sair a {

    display: block;

    text-align: center;

    color: #ddd;

    text-decoration: none;

    border: 1px solid #555;

    padding: 12px;

    border-radius: 7px;
}


/* CONTEÚDO */

.main {

    margin-left: 250px;

    padding: 32px;

    max-width: 1250px;
}

.topo {

    margin-bottom: 28px;
}

.topo h1 {

    margin: 0 0 6px;

    font-size: 29px;
}

.topo p {

    margin: 0;

    color: #777;
}


/* CARD */

.card {

    background: white;

    border: 1px solid #e5e5e5;

    border-radius: 10px;

    padding: 28px;

    box-shadow:
        0 2px 8px
        rgba(0,0,0,.03);
}

.card h2 {

    margin-top: 0;

    margin-bottom: 7px;
}

.subtitulo {

    color: #777;

    margin-bottom: 25px;
}


/* FORMULÁRIO */

.grid {

    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 22px;
}

.campo-full {

    grid-column: 1 / -1;
}

label {

    display: block;

    font-size: 14px;

    font-weight: bold;

    margin-bottom: 8px;
}

input[type="text"],
input[type="tel"],
textarea,
input[type="file"] {

    width: 100%;

    border: 1px solid #d7dce1;

    border-radius: 7px;

    padding: 12px;

    font-size: 15px;

    font-family: inherit;

    background: white;
}

textarea {

    resize: vertical;

    min-height: 100px;
}

input:focus,
textarea:focus {

    outline: none;

    border-color: #555;
}

.ajuda {

    color: #777;

    font-size: 12px;

    margin-top: 7px;
}


/* LOGO */

.logo-atual {

    margin-top: 15px;

    padding: 15px;

    border: 1px solid #eee;

    border-radius: 8px;

    background: #fafafa;
}

.logo-atual span {

    display: block;

    color: #777;

    font-size: 12px;

    margin-bottom: 10px;
}

.logo-atual img {

    max-width: 180px;

    max-height: 120px;

    object-fit: contain;

    display: block;
}


/* BOTÃO */

.acoes {

    display: flex;

    justify-content: flex-end;

    margin-top: 25px;

    padding-top: 20px;

    border-top: 1px solid #eee;
}

.salvar {

    border: none;

    background: #202020;

    color: white;

    padding: 13px 25px;

    border-radius: 7px;

    cursor: pointer;

    font-size: 15px;
}

.salvar:hover {

    background: #333;
}


/* MENSAGENS */

.erro {

    background: #f8d7da;

    color: #842029;

    border: 1px solid #f5c2c7;

    padding: 13px;

    border-radius: 7px;

    margin-bottom: 20px;
}

.sucesso {

    background: #dff3e4;

    color: #236b36;

    border: 1px solid #c7e8d0;

    padding: 13px;

    border-radius: 7px;

    margin-bottom: 20px;
}


/* CELULAR */

@media (max-width: 800px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;
    }

    .menu {

        display: flex;

        overflow-x: auto;

        gap: 5px;
    }

    .menu a {

        white-space: nowrap;
    }

    .sair {

        position: static;

        margin-top: 15px;
    }

    .main {

        margin-left: 0;

        padding: 20px;
    }

    .grid {

        grid-template-columns: 1fr;
    }

    .campo-full {

        grid-column: auto;
    }
}

</style>

<link rel="stylesheet" href="../assets/css/admin.css">

</head>

<body>


<!-- MENU LATERAL -->

<aside class="sidebar">

    <div class="logo">
        Cardápio Digital
    </div>

    <div class="logo-sub">
        Painel Administrativo
    </div>


    <nav class="menu">

        <a href="index.php">
            Dashboard
        </a>

        <a href="produto.php">
            Produtos
        </a>

        <a href="categorias.php">
            Categorias
        </a>

        <a
            href="configuracoes.php"
            class="ativo"
        >
            Configurações
        </a>

        <a href="pedidos.php">
            Pedidos
        </a>

        <a
            href="../index.php"
            target="_blank"
        >
            Ver Cardápio
        </a>

    </nav>


    <div class="sair">

        <a href="logout.php">
            Sair
        </a>

    </div>

</aside>


<!-- CONTEÚDO -->

<main class="main">


    <div class="topo">

        <h1>
            Configurações
        </h1>

        <p>
            Personalize as informações do seu estabelecimento.
        </p>

    </div>


    <?php if ($erro !== ''): ?>

        <div class="erro">

            <?= htmlspecialchars($erro) ?>

        </div>

    <?php endif; ?>


    <?php if ($sucesso !== ''): ?>

        <div class="sucesso">

            <?= htmlspecialchars($sucesso) ?>

        </div>

    <?php endif; ?>


    <section class="card">


        <h2>
            Dados do estabelecimento
        </h2>

        <div class="subtitulo">
            Essas informações poderão ser exibidas no cardápio dos clientes.
        </div>


        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <div class="grid">


                <!-- NOME -->

                <div class="campo-full">

                    <label for="nome_estabelecimento">

                        Nome do estabelecimento *

                    </label>

                    <input
                        type="text"
                        id="nome_estabelecimento"
                        name="nome_estabelecimento"
                        maxlength="150"
                        value="<?= htmlspecialchars(
                            $config['nome_estabelecimento']
                        ) ?>"
                        placeholder="Ex.: Lanchonete Central"
                        required
                    >

                </div>


                <!-- DESCRIÇÃO -->

                <div class="campo-full">

                    <label for="descricao">

                        Descrição

                    </label>

                    <textarea
                        id="descricao"
                        name="descricao"
                        maxlength="255"
                        placeholder="Ex.: Os melhores lanches da região"
                    ><?= htmlspecialchars(
                        $config['descricao']
                    ) ?></textarea>

                </div>


                <!-- WHATSAPP -->

                <div>

                    <label for="whatsapp">

                        WhatsApp

                    </label>

                    <input
                        type="tel"
                        id="whatsapp"
                        name="whatsapp"
                        maxlength="30"
                        value="<?= htmlspecialchars(
                            $config['whatsapp']
                        ) ?>"
                        placeholder="Ex.: (11) 99999-9999"
                    >

                </div>


                <!-- HORÁRIO -->

                <div>

                    <label for="horario">

                        Horário de funcionamento

                    </label>

                    <input
                        type="text"
                        id="horario"
                        name="horario"
                        maxlength="150"
                        value="<?= htmlspecialchars(
                            $config['horario']
                        ) ?>"
                        placeholder="Ex.: Seg a Sáb - 18h às 23h"
                    >

                </div>


                <!-- ENDEREÇO -->

                <div class="campo-full">

                    <label for="endereco">

                        Endereço

                    </label>

                    <input
                        type="text"
                        id="endereco"
                        name="endereco"
                        maxlength="255"
                        value="<?= htmlspecialchars(
                            $config['endereco']
                        ) ?>"
                        placeholder="Ex.: Rua das Flores, 100 - São Paulo"
                    >

                </div>


                <!-- LOGO -->

                <div class="campo-full">

                    <label for="logo">

                        Logo do estabelecimento

                    </label>

                    <input
                        type="file"
                        id="logo"
                        name="logo"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    >

                    <div class="ajuda">

                        JPG, PNG ou WEBP. Máximo de 5 MB.

                    </div>


                    <?php if (
                        !empty($config['logo'])
                    ): ?>

                        <div class="logo-atual">

                            <span>
                                Logo atual
                            </span>

                            <img
                                src="../<?= htmlspecialchars(
                                    $config['logo']
                                ) ?>"
                                alt="Logo do estabelecimento"
                            >

                        </div>

                    <?php endif; ?>

                </div>


            </div>


            <div class="acoes">

                <button
                    type="submit"
                    class="salvar"
                >
                    Salvar Configurações
                </button>

            </div>


        </form>


    </section>


</main>


</body>

</html>
