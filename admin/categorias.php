<?php
require_once 'auth.php';

$erro = '';
$sucesso = '';

$idEditar = isset($_GET['editar']) ? (int) $_GET['editar'] : 0;
$nomeEditar = '';

/* =========================
   BUSCAR CATEGORIA PARA EDITAR
========================= */

if ($idEditar > 0) {

    $stmt = $pdo->prepare(
        "SELECT * FROM categorias WHERE id = ?"
    );

    $stmt->execute([$idEditar]);

    $categoriaEditar = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($categoriaEditar) {

        $nomeEditar = $categoriaEditar['nome'];

    } else {

        header('Location: categorias.php');
        exit;

    }
}


/* =========================
   CADASTRAR / EDITAR
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');

    $id = isset($_POST['id'])
        ? (int) $_POST['id']
        : 0;


    if ($nome === '') {

        $erro = 'Digite o nome da categoria.';

    } else {

        try {

            /* EDITAR */

            if ($id > 0) {

                $stmt = $pdo->prepare(
                    "SELECT id
                     FROM categorias
                     WHERE nome = ?
                     AND id <> ?"
                );

                $stmt->execute([
                    $nome,
                    $id
                ]);


                if ($stmt->fetch()) {

                    $erro =
                        'Já existe outra categoria com esse nome.';

                } else {

                    $stmt = $pdo->prepare(
                        "UPDATE categorias
                         SET nome = ?
                         WHERE id = ?"
                    );

                    $stmt->execute([
                        $nome,
                        $id
                    ]);

                    header(
                        'Location: categorias.php?sucesso=editada'
                    );

                    exit;

                }

            }

            /* CADASTRAR */

            else {

                $stmt = $pdo->prepare(
                    "SELECT id
                     FROM categorias
                     WHERE nome = ?"
                );

                $stmt->execute([$nome]);


                if ($stmt->fetch()) {

                    $erro =
                        'Essa categoria já está cadastrada.';

                } else {

                    $stmt = $pdo->prepare(
                        "INSERT INTO categorias (nome)
                         VALUES (?)"
                    );

                    $stmt->execute([$nome]);

                    header(
                        'Location: categorias.php?sucesso=cadastrada'
                    );

                    exit;

                }

            }

        } catch (PDOException $e) {

            $erro =
                'Não foi possível salvar a categoria.';

        }

    }

}


/* =========================
   EXCLUIR
========================= */

if (isset($_GET['excluir'])) {

    $idExcluir = (int) $_GET['excluir'];


    /* Verifica produtos */

    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM produtos
         WHERE categoria_id = ?"
    );

    $stmt->execute([$idExcluir]);

    $quantidadeProdutos =
        (int) $stmt->fetchColumn();


    if ($quantidadeProdutos > 0) {

        header(
            'Location: categorias.php?erro=possui_produtos'
        );

        exit;

    }


    $stmt = $pdo->prepare(
        "DELETE FROM categorias
         WHERE id = ?"
    );

    $stmt->execute([$idExcluir]);


    header(
        'Location: categorias.php?sucesso=excluida'
    );

    exit;

}


/* =========================
   MENSAGENS
========================= */

if (isset($_GET['sucesso'])) {

    if ($_GET['sucesso'] === 'cadastrada') {

        $sucesso =
            'Categoria cadastrada com sucesso!';

    }

    elseif ($_GET['sucesso'] === 'editada') {

        $sucesso =
            'Categoria atualizada com sucesso!';

    }

    elseif ($_GET['sucesso'] === 'excluida') {

        $sucesso =
            'Categoria excluída com sucesso!';

    }

}


if (
    isset($_GET['erro']) &&
    $_GET['erro'] === 'possui_produtos'
) {

    $erro =
        'Essa categoria possui produtos cadastrados e não pode ser excluída.';

}


/* =========================
   LISTAR CATEGORIAS
========================= */

$sql = "
    SELECT
        c.id,
        c.nome,
        COUNT(p.id) AS total_produtos

    FROM categorias c

    LEFT JOIN produtos p
        ON p.categoria_id = c.id

    GROUP BY
        c.id,
        c.nome

    ORDER BY
        c.nome
";

$categorias =
    $pdo->query($sql)
        ->fetchAll(PDO::FETCH_ASSOC);

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
    Categorias - Cardápio Digital
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

    color: #252525;

}


/* =========================
   MENU
========================= */

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


/* =========================
   CONTEÚDO
========================= */

.main {

    margin-left: 250px;

    padding: 32px;

}


.topo {

    display: flex;

    justify-content:
        space-between;

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


/* =========================
   GRID
========================= */

.conteudo {

    display: grid;

    grid-template-columns:
        380px 1fr;

    gap: 25px;

}


/* =========================
   CARDS
========================= */

.card {

    background: white;

    border: 1px solid #e5e5e5;

    border-radius: 10px;

    padding: 25px;

    box-shadow:
        0 2px 8px
        rgba(0,0,0,.03);

}


.card h2 {

    margin-top: 0;

    margin-bottom: 6px;

}


.subtitulo {

    color: #777;

    font-size: 14px;

    margin-bottom: 22px;

}


/* =========================
   FORM
========================= */

label {

    display: block;

    font-weight: bold;

    font-size: 14px;

    margin-bottom: 8px;

}


input[type="text"] {

    width: 100%;

    padding: 12px;

    border: 1px solid #d7dce1;

    border-radius: 7px;

    font-size: 15px;

}


input[type="text"]:focus {

    outline: none;

    border-color: #555;

}


.salvar {

    width: 100%;

    border: none;

    background: #202020;

    color: white;

    padding: 12px;

    border-radius: 7px;

    font-size: 15px;

    cursor: pointer;

    margin-top: 15px;

}


.salvar:hover {

    background: #333;

}


.cancelar {

    display: block;

    text-align: center;

    margin-top: 12px;

    color: #555;

    text-decoration: none;

}


/* =========================
   TABELA
========================= */

.tabela {

    width: 100%;

    border-collapse: collapse;

}


.tabela th {

    text-align: left;

    color: #666;

    font-size: 13px;

    padding: 14px;

    background: #fafafa;

    border-bottom:
        1px solid #eee;

}


.tabela td {

    padding: 15px 14px;

    border-bottom:
        1px solid #eee;

}


.nome {

    font-weight: bold;

}


.quantidade {

    display: inline-block;

    background: #eef1f4;

    padding: 6px 10px;

    border-radius: 20px;

    font-size: 12px;

}


.acoes {

    display: flex;

    gap: 8px;

}


.editar,
.excluir {

    text-decoration: none;

    padding: 8px 11px;

    border-radius: 6px;

    font-size: 13px;

}


.editar {

    background: #eef2f6;

    color: #333;

}


.excluir {

    background: #fae7e7;

    color: #a52727;

}


/* =========================
   ALERTAS
========================= */

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


/* =========================
   RESPONSIVO
========================= */

@media (max-width: 900px) {

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


    .conteudo {

        grid-template-columns: 1fr;

    }


    .topo {

        flex-direction: column;

        align-items: flex-start;

        gap: 15px;

    }

}

</style>

<link rel="stylesheet" href="../assets/css/admin.css">

</head>


<body>


<!-- MENU -->

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


        <a
            href="categorias.php"
            class="ativo"
        >

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
                Categorias
            </h1>

            <p>
                Organize os produtos do seu cardápio.
            </p>

        </div>


        <a
            href="index.php"
            class="voltar"
        >

            ← Voltar ao Dashboard

        </a>


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


    <div class="conteudo">


        <!-- FORMULÁRIO -->

        <section class="card">


            <h2>

                <?= $idEditar > 0
                    ? 'Editar Categoria'
                    : 'Nova Categoria'
                ?>

            </h2>


            <div class="subtitulo">

                <?= $idEditar > 0
                    ? 'Altere o nome da categoria.'
                    : 'Crie uma categoria para organizar seus produtos.'
                ?>

            </div>


            <form method="POST">


                <?php if ($idEditar > 0): ?>


                    <input
                        type="hidden"
                        name="id"
                        value="<?= $idEditar ?>"
                    >


                <?php endif; ?>


                <label for="nome">

                    Nome da categoria

                </label>


                <input

                    type="text"

                    id="nome"

                    name="nome"

                    maxlength="100"

                    placeholder="Ex.: Pizzas"

                    value="<?= htmlspecialchars(
                        $nomeEditar
                    ) ?>"

                    required

                >


                <button
                    type="submit"
                    class="salvar"
                >

                    <?= $idEditar > 0
                        ? 'Salvar Alterações'
                        : '+ Cadastrar Categoria'
                    ?>

                </button>


                <?php if ($idEditar > 0): ?>


                    <a
                        href="categorias.php"
                        class="cancelar"
                    >

                        Cancelar edição

                    </a>


                <?php endif; ?>


            </form>


        </section>


        <!-- LISTA -->

        <section class="card">


            <h2>
                Categorias cadastradas
            </h2>


            <div class="subtitulo">

                <?= count($categorias) ?>
                categoria(s) cadastrada(s).

            </div>


            <div style="overflow-x:auto">


                <table class="tabela">


                    <thead>


                        <tr>

                            <th>
                                Categoria
                            </th>

                            <th>
                                Produtos
                            </th>

                            <th>
                                Ações
                            </th>

                        </tr>


                    </thead>


                    <tbody>


                    <?php foreach (
                        $categorias as $categoria
                    ): ?>


                        <tr>


                            <td class="nome">

                                <?= htmlspecialchars(
                                    $categoria['nome']
                                ) ?>

                            </td>


                            <td>

                                <span class="quantidade">

                                    <?= $categoria[
                                        'total_produtos'
                                    ] ?>

                                    produto(s)

                                </span>

                            </td>


                            <td>


                                <div class="acoes">


                                    <a

                                        href="categorias.php?editar=<?= $categoria['id'] ?>"

                                        class="editar"

                                    >

                                        Editar

                                    </a>


                                    <a

                                        href="categorias.php?excluir=<?= $categoria['id'] ?>"

                                        class="excluir"

                                        onclick="return confirm('Deseja realmente excluir esta categoria?')"

                                    >

                                        Excluir

                                    </a>


                                </div>


                            </td>


                        </tr>


                    <?php endforeach; ?>


                    </tbody>


                </table>


            </div>


        </section>


    </div>


</main>


</body>

</html>
