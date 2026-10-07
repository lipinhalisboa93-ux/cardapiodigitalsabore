<?php
require_once 'auth.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$editando = $id > 0;
$erro = '';

$produto = [
    'categoria_id' => '',
    'nome' => '',
    'descricao' => '',
    'preco' => '',
    'imagem' => '',
    'ativo' => 1
];

// Busca categorias
$categorias = $pdo->query(
    "SELECT id, nome FROM categorias ORDER BY nome"
)->fetchAll(PDO::FETCH_ASSOC);


// Se estiver editando, busca o produto
if ($editando) {

    $stmt = $pdo->prepare(
        "SELECT * FROM produtos WHERE id = ? LIMIT 1"
    );

    $stmt->execute([$id]);

    $encontrado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$encontrado) {
        header('Location: index.php');
        exit;
    }

    $produto = $encontrado;
}


// SALVAR
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $categoria_id = (int)($_POST['categoria_id'] ?? 0);

    $nome = trim($_POST['nome'] ?? '');

    $descricao = trim($_POST['descricao'] ?? '');

    $precoTexto = str_replace(
        ',',
        '.',
        trim($_POST['preco'] ?? '')
    );

    $ativo = isset($_POST['ativo']) ? 1 : 0;

    $imagem = $produto['imagem'] ?? '';


    // Validação
    if (
        $categoria_id <= 0 ||
        $nome === '' ||
        $precoTexto === '' ||
        !is_numeric($precoTexto)
    ) {

        $erro = 'Preencha corretamente nome, categoria e preço.';

    }


    // UPLOAD DA IMAGEM
    if (
        $erro === '' &&
        isset($_FILES['imagem']) &&
        $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {

            $erro = 'Não foi possível enviar a imagem.';

        } else {

            $permitidos = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            $mime = mime_content_type(
                $_FILES['imagem']['tmp_name']
            );


            if (!isset($permitidos[$mime])) {

                $erro = 'Use uma imagem JPG, PNG ou WEBP.';

            } elseif (
                $_FILES['imagem']['size'] >
                5 * 1024 * 1024
            ) {

                $erro = 'A imagem deve ter no máximo 5 MB.';

            } else {

                $pasta = __DIR__ . '/../uploads';


                // Cria a pasta uploads caso não exista
                if (!is_dir($pasta)) {

                    mkdir(
                        $pasta,
                        0755,
                        true
                    );

                }


                $nomeArquivo =
                    'produto_' .
                    bin2hex(random_bytes(8)) .
                    '.' .
                    $permitidos[$mime];


                $destino =
                    $pasta .
                    '/' .
                    $nomeArquivo;


                if (
                    move_uploaded_file(
                        $_FILES['imagem']['tmp_name'],
                        $destino
                    )
                ) {

                    // Exclui imagem antiga ao editar
                    if (
                        $editando &&
                        !empty($produto['imagem'])
                    ) {

                        $antiga =
                            __DIR__ .
                            '/../' .
                            ltrim(
                                $produto['imagem'],
                                '/'
                            );


                        if (is_file($antiga)) {

                            @unlink($antiga);

                        }

                    }


                    $imagem =
                        'uploads/' .
                        $nomeArquivo;

                } else {

                    $erro =
                        'Não foi possível salvar a imagem.';

                }

            }

        }

    }


    // GRAVAR NO BANCO
    if ($erro === '') {

        $preco = (float)$precoTexto;


        if ($editando) {

            $stmt = $pdo->prepare(
                "UPDATE produtos
                 SET
                    categoria_id = ?,
                    nome = ?,
                    descricao = ?,
                    preco = ?,
                    imagem = ?,
                    ativo = ?
                 WHERE id = ?"
            );


            $stmt->execute([
                $categoria_id,
                $nome,
                $descricao,
                $preco,
                $imagem,
                $ativo,
                $id
            ]);

        } else {

            $stmt = $pdo->prepare(
                "INSERT INTO produtos
                (
                    categoria_id,
                    nome,
                    descricao,
                    preco,
                    imagem,
                    ativo
                )
                VALUES (?, ?, ?, ?, ?, ?)"
            );


            $stmt->execute([
                $categoria_id,
                $nome,
                $descricao,
                $preco,
                $imagem,
                $ativo
            ]);

        }


        header('Location: index.php');
        exit;

    }


    // Mantém dados preenchidos caso dê erro
    $produto['categoria_id'] = $categoria_id;

    $produto['nome'] = $nome;

    $produto['descricao'] = $descricao;

    $produto['preco'] = $precoTexto;

    $produto['imagem'] = $imagem;

    $produto['ativo'] = $ativo;

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
    <?= $editando ? 'Editar Produto' : 'Novo Produto' ?>
    - Cardápio Digital
</title>


<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: #f4f6f8;
    color: #252525;
}


/* MENU LATERAL */

.sidebar {
    width: 250px;
    height: 100vh;
    background: #1d1f21;
    color: white;
    position: fixed;
    left: 0;
    top: 0;
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
    text-decoration: none;
    color: #ddd;
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
    padding: 12px;
    border: 1px solid #555;
    border-radius: 7px;
}


/* CONTEÚDO */

.main {
    margin-left: 250px;
    padding: 32px;
    max-width: 1200px;
}

.topo {
    display: flex;
    justify-content: space-between;
    align-items: center;
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

.voltar {
    background: white;
    color: #333;
    border: 1px solid #ddd;
    padding: 11px 17px;
    border-radius: 7px;
    text-decoration: none;
}


/* FORMULÁRIO */

.form-box {
    background: white;
    border: 1px solid #e5e5e5;
    border-radius: 10px;
    padding: 28px;
    box-shadow: 0 2px 8px rgba(0,0,0,.03);
}

.grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.campo-full {
    grid-column: 1 / -1;
}

label {
    display: block;
    font-weight: bold;
    margin-bottom: 8px;
    font-size: 14px;
}

input[type="text"],
select,
textarea,
input[type="file"] {

    width: 100%;
    padding: 12px;
    border: 1px solid #d7dce1;
    border-radius: 7px;
    font: inherit;
    background: white;
}

textarea {
    min-height: 120px;
    resize: vertical;
}

input:focus,
select:focus,
textarea:focus {

    outline: none;
    border-color: #555;
}

.ajuda {
    font-size: 12px;
    color: #777;
    margin-top: 7px;
}


/* IMAGEM */

.preview {
    margin-top: 12px;
}

.preview img {
    width: 150px;
    height: 110px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid #ddd;
}


/* STATUS */

.status-box {

    display: flex;
    align-items: center;
    gap: 10px;

    padding: 14px;

    background: #f7f8f9;

    border-radius: 7px;
}


/* BOTÕES */

.acoes {

    display: flex;

    justify-content: flex-end;

    gap: 10px;

    margin-top: 25px;

    border-top: 1px solid #eee;

    padding-top: 20px;
}

.cancelar,
.salvar {

    padding: 12px 20px;

    border-radius: 7px;

    text-decoration: none;

    font-size: 15px;
}

.cancelar {

    background: #eef1f4;

    color: #333;
}

.salvar {

    border: none;

    background: #202020;

    color: white;

    cursor: pointer;
}

.salvar:hover {

    background: #333;
}


/* ERRO */

.erro {

    background: #f8d7da;

    color: #842029;

    border: 1px solid #f5c2c7;

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

        gap: 5px;

        overflow-x: auto;

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

    .topo {

        align-items: flex-start;

        flex-direction: column;

        gap: 15px;

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

        <a
            href="produto.php"
            class="ativo"
        >
            Produtos
        </a>

        <a href="categorias.php">
            Categorias
        </a>

        <a href="configuracoes.php">
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

        <div>

            <h1>

                <?= $editando
                    ? 'Editar Produto'
                    : 'Novo Produto'
                ?>

            </h1>


            <p>

                <?= $editando
                    ? 'Atualize as informações do produto.'
                    : 'Cadastre um novo item no cardápio.'
                ?>

            </p>

        </div>


        <a
            href="index.php"
            class="voltar"
        >
            ← Voltar
        </a>

    </div>


    <div class="form-box">


        <?php if ($erro !== ''): ?>

            <div class="erro">

                <?= htmlspecialchars($erro) ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <div class="grid">


                <!-- NOME -->

                <div>

                    <label for="nome">
                        Nome do produto *
                    </label>

                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        maxlength="120"
                        value="<?= htmlspecialchars(
                            $produto['nome']
                        ) ?>"
                        required
                    >

                </div>


                <!-- CATEGORIA -->

                <div>

                    <label for="categoria_id">
                        Categoria *
                    </label>


                    <select
                        id="categoria_id"
                        name="categoria_id"
                        required
                    >

                        <option value="">
                            Selecione uma categoria
                        </option>


                        <?php foreach (
                            $categorias as $categoria
                        ): ?>


                            <option

                                value="<?= $categoria['id'] ?>"

                                <?= (int)$produto['categoria_id']
                                    ===
                                    (int)$categoria['id']
                                    ? 'selected'
                                    : ''
                                ?>

                            >

                                <?= htmlspecialchars(
                                    $categoria['nome']
                                ) ?>

                            </option>


                        <?php endforeach; ?>


                    </select>

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

                        placeholder="Ex.: Pão, hambúrguer, queijo, alface e tomate"

                    ><?= htmlspecialchars(
                        $produto['descricao']
                    ) ?></textarea>

                </div>


                <!-- PREÇO -->

                <div>

                    <label for="preco">
                        Preço (R$) *
                    </label>


                    <input

                        type="text"

                        id="preco"

                        name="preco"

                        placeholder="Ex.: 22,90"

                        value="<?= htmlspecialchars(
                            (string)$produto['preco']
                        ) ?>"

                        required
                    >

                </div>


                <!-- IMAGEM -->

                <div>

                    <label for="imagem">
                        Foto do produto
                    </label>


                    <input

                        type="file"

                        id="imagem"

                        name="imagem"

                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    >


                    <div class="ajuda">

                        JPG, PNG ou WEBP.
                        Máximo de 5 MB.

                    </div>


                    <?php if (
                        !empty($produto['imagem'])
                    ): ?>


                        <div class="preview">

                            <img

                                src="../<?= htmlspecialchars(
                                    $produto['imagem']
                                ) ?>"

                                alt="Imagem atual"

                            >

                        </div>


                    <?php endif; ?>


                </div>


                <!-- STATUS -->

                <div class="campo-full">

                    <label>
                        Status
                    </label>


                    <div class="status-box">


                        <input

                            type="checkbox"

                            id="ativo"

                            name="ativo"

                            value="1"

                            <?= !empty($produto['ativo'])
                                ? 'checked'
                                : ''
                            ?>

                        >


                        <label
                            for="ativo"
                            style="
                                margin:0;
                                font-weight:normal;
                            "
                        >

                            Produto ativo e visível no cardápio

                        </label>


                    </div>

                </div>


            </div>


            <!-- BOTÕES -->

            <div class="acoes">


                <a
                    href="index.php"
                    class="cancelar"
                >
                    Cancelar
                </a>


                <button
                    type="submit"
                    class="salvar"
                >

                    <?= $editando
                        ? 'Salvar Alterações'
                        : 'Cadastrar Produto'
                    ?>

                </button>


            </div>


        </form>


    </div>


</main>


</body>

</html>
