<?php

require_once 'auth.php';

// Total de produtos
$totalProdutos = $pdo->query(
    "SELECT COUNT(*) FROM produtos"
)->fetchColumn();

// Produtos ativos
$produtosAtivos = $pdo->query(
    "SELECT COUNT(*) FROM produtos WHERE ativo = 1"
)->fetchColumn();

// Total de categorias
$totalCategorias = $pdo->query(
    "SELECT COUNT(*) FROM categorias"
)->fetchColumn();

// Pedidos recebidos
$totalPedidos = $pdo->query(
    "SELECT COUNT(*) FROM pedidos"
)->fetchColumn();

// Faturamento registrado
$faturamentoTotal = $pdo->query(
    "SELECT COALESCE(SUM(total), 0) FROM pedidos"
)->fetchColumn();

// Pedidos em preparação
$pedidosPreparacao = $pdo->query(
    "SELECT COUNT(*) FROM pedidos WHERE status = 'Em preparação'"
)->fetchColumn();

// Pedidos prontos
$pedidosProntos = $pdo->query(
    "SELECT COUNT(*) FROM pedidos WHERE status = 'Pronto'"
)->fetchColumn();

// Pedidos recentes (resumo)
$pedidosRecentes = $pdo->query(
    "SELECT id, nome_cliente, total, status, criado_em
     FROM pedidos
     ORDER BY criado_em DESC, id DESC
     LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);

// Mesmas cores de status usadas na página de pedidos
function classeStatusResumo(string $status): string
{
    if (str_starts_with($status, 'Em prepara')) {
        return 'status-preparacao';
    }

    return match ($status) {
        'Pronto' => 'status-pronto',
        'Saiu para entrega' => 'status-entrega',
        'Finalizado' => 'status-finalizado',
        default => 'status-recebido',
    };
}

// Lista de produtos
$sql = "
    SELECT 
        p.*,
        c.nome AS categoria
    FROM produtos p
    INNER JOIN categorias c
        ON c.id = p.categoria_id
    ORDER BY p.id DESC
";

$produtos = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Painel Administrativo - Saborê</title>

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

/* =========================
   MENU LATERAL
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
    transition: 0.2s;
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

.sair a:hover {
    background: #34373a;
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

.visualizar {
    text-decoration: none;
    color: #333;
    background: white;
    border: 1px solid #ddd;
    padding: 11px 17px;
    border-radius: 7px;
}

.visualizar:hover {
    background: #eee;
}

/* =========================
   CARDS
========================= */

.cards {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.card {
    background: white;
    padding: 23px;
    border-radius: 10px;
    border: 1px solid #e6e6e6;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
}

.card span {
    display: block;
    color: #777;
    font-size: 14px;
    margin-bottom: 10px;
}

.card strong {
    font-size: 31px;
}

/* =========================
   PRODUTOS
========================= */

.secao {
    background: white;
    border: 1px solid #e5e5e5;
    border-radius: 10px;
    overflow: hidden;
}

.secao-topo {
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #eee;
}

.secao-topo h2 {
    margin: 0;
    font-size: 20px;
}

.novo {
    background: #202020;
    color: white;
    padding: 11px 17px;
    border-radius: 7px;
    text-decoration: none;
}

.novo:hover {
    background: #333;
}

/* =========================
   TABELA
========================= */

.tabela-container {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: #fafafa;
    color: #666;
    text-align: left;
    padding: 14px 16px;
    font-size: 13px;
    border-bottom: 1px solid #eee;
}

td {
    padding: 14px 16px;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
}

tr:last-child td {
    border-bottom: none;
}

.produto-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.produto-img {
    width: 55px;
    height: 55px;
    border-radius: 7px;
    object-fit: cover;
    background: #eee;
}

.sem-imagem {
    width: 55px;
    height: 55px;
    border-radius: 7px;
    background: #eee;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    color: #888;
    text-align: center;
}

.nome-produto {
    font-weight: bold;
}

.preco {
    font-weight: bold;
}

/* =========================
   STATUS
========================= */

.status {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.ativo-status {
    background: #e4f6e8;
    color: #247a39;
}

.inativo-status {
    background: #f5e5e5;
    color: #a32f2f;
}

/* =========================
   AÇÕES
========================= */

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

.editar:hover {
    background: #ddd;
}

.excluir:hover {
    background: #f2cccc;
}

.vazio {
    text-align: center;
    padding: 40px;
    color: #777;
}

/* =========================
   CELULAR
========================= */

@media (max-width: 1100px) {

    .cards {
        grid-template-columns: repeat(2, 1fr);
    }

}

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
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }

    .cards {
        grid-template-columns: 1fr;
    }

}

</style>

<link rel="stylesheet" href="../assets/css/admin.css">

</head>

<body>

<!-- MENU -->

<aside class="sidebar">

    <div class="logo">
        Saborê
    </div>

    <div class="logo-sub">
        Painel Administrativo
    </div>

    <nav class="menu">

        <a href="index.php" class="ativo">
            Dashboard
        </a>

        <a href="produto.php">
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

        <a href="../index.php" target="_blank">
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

            <h1>Painel Administrativo</h1>

            <p>
                Gerencie produtos, categorias e pedidos do Saborê.
            </p>

        </div>

        <a
            href="../index.php"
            target="_blank"
            class="visualizar"
        >
            Visualizar Cardápio
        </a>

    </div>


    <!-- CARDS: PEDIDOS -->

    <h2 class="grupo-titulo">Pedidos</h2>

    <div class="cards">

        <div class="card">

            <span>
                Pedidos recebidos
            </span>

            <strong>
                <?= $totalPedidos ?>
            </strong>

        </div>

        <div class="card card-destaque">

            <span>
                Faturamento
            </span>

            <strong>
                R$ <?= number_format((float)$faturamentoTotal, 2, ',', '.') ?>
            </strong>

            <small>Soma de todos os pedidos registrados</small>

        </div>

        <div class="card">

            <span>
                Em preparação
            </span>

            <strong>
                <?= $pedidosPreparacao ?>
            </strong>

        </div>

        <div class="card">

            <span>
                Prontos
            </span>

            <strong>
                <?= $pedidosProntos ?>
            </strong>

        </div>

    </div>


    <!-- CARDS: CARDÁPIO -->

    <h2 class="grupo-titulo">Cardápio</h2>

    <div class="cards">

        <div class="card">

            <span>
                Produtos cadastrados
            </span>

            <strong>
                <?= $totalProdutos ?>
            </strong>

        </div>

        <div class="card">

            <span>
                Produtos ativos
            </span>

            <strong>
                <?= $produtosAtivos ?>
            </strong>

        </div>

        <div class="card">

            <span>
                Categorias
            </span>

            <strong>
                <?= $totalCategorias ?>
            </strong>

        </div>

    </div>


    <!-- PEDIDOS RECENTES -->

    <section class="secao secao-recentes">

        <div class="secao-topo">

            <h2>
                Pedidos recentes
            </h2>

            <a href="pedidos.php" class="ver-todos">
                Ver todos os pedidos
            </a>

        </div>

        <?php if (count($pedidosRecentes) === 0): ?>

            <div class="estado-vazio estado-vazio-compacto">

                <div class="estado-vazio-icone" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="20" r="1.4"/>
                        <circle cx="18" cy="20" r="1.4"/>
                        <path d="M2.5 3.5h2.6l2.4 11.2a1.6 1.6 0 0 0 1.6 1.3h8.6a1.6 1.6 0 0 0 1.6-1.2l1.7-7.3H6.2"/>
                    </svg>
                </div>

                <h3>Nenhum pedido recebido</h3>

                <p>Os pedidos mais recentes do cardápio aparecerão aqui.</p>

            </div>

        <?php else: ?>

            <div class="tabela-container">

                <table>

                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Cliente</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Data/hora</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($pedidosRecentes as $pedidoRecente): ?>

                        <tr>
                            <td class="numero-recente">#<?= (int)$pedidoRecente['id'] ?></td>

                            <td><?= htmlspecialchars($pedidoRecente['nome_cliente']) ?></td>

                            <td class="preco">
                                R$ <?= number_format((float)$pedidoRecente['total'], 2, ',', '.') ?>
                            </td>

                            <td>
                                <span class="status <?= classeStatusResumo($pedidoRecente['status']) ?>">
                                    <?= htmlspecialchars($pedidoRecente['status']) ?>
                                </span>
                            </td>

                            <td class="data-recente">
                                <?= date('d/m/Y H:i', strtotime($pedidoRecente['criado_em'])) ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>


    <!-- PRODUTOS -->

    <section class="secao">

        <div class="secao-topo">

            <h2>
                Produtos
            </h2>

            <a
                href="produto.php"
                class="novo"
            >
                + Novo Produto
            </a>

        </div>


        <?php if (count($produtos) === 0): ?>

            <div class="vazio">

                Nenhum produto cadastrado.

            </div>

        <?php else: ?>


        <div class="tabela-container">

            <table>

                <thead>

                    <tr>

                        <th>Produto</th>

                        <th>Categoria</th>

                        <th>Preço</th>

                        <th>Status</th>

                        <th>Ações</th>

                    </tr>

                </thead>


                <tbody>

                <?php foreach ($produtos as $produto): ?>

                    <tr>

                        <td>

                            <div class="produto-info">

                                <?php if (!empty($produto['imagem'])): ?>

                                    <img
                                        src="../<?= htmlspecialchars($produto['imagem']) ?>"
                                        class="produto-img"
                                        alt=""
                                    >

                                <?php else: ?>

                                    <div class="sem-imagem">
                                        Sem imagem
                                    </div>

                                <?php endif; ?>


                                <div>

                                    <div class="nome-produto">

                                        <?= htmlspecialchars($produto['nome']) ?>

                                    </div>

                                </div>

                            </div>

                        </td>


                        <td>

                            <?= htmlspecialchars($produto['categoria']) ?>

                        </td>


                        <td class="preco">

                            R$
                            <?= number_format(
                                $produto['preco'],
                                2,
                                ',',
                                '.'
                            ) ?>

                        </td>


                        <td>

                            <?php if ($produto['ativo']): ?>

                                <span class="status ativo-status">
                                    Ativo
                                </span>

                            <?php else: ?>

                                <span class="status inativo-status">
                                    Inativo
                                </span>

                            <?php endif; ?>

                        </td>


                        <td>

                            <div class="acoes">

                                <a
                                    href="produto.php?id=<?= $produto['id'] ?>"
                                    class="editar"
                                >
                                    Editar
                                </a>

                                <a
                                    href="excluir_produto.php?id=<?= $produto['id'] ?>"
                                    class="excluir"
                                    onclick="return confirm('Deseja realmente excluir este produto?')"
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

        <?php endif; ?>

    </section>

</main>

</body>

</html>
