<?php

require_once 'auth.php';
require_once '../config/db.php';

/* =========================
   ALTERAR STATUS
========================= */

$mensagem = '';

$statusPermitidos = [
    'Recebido',
    'Em preparação',
    'Pronto',
    'Saiu para entrega',
    'Finalizado'
];

/* "Saiu para entrega" só faz sentido em pedidos com entrega */
function statusDisponivel(string $status, string $tipoEntrega): bool
{
    return $status !== 'Saiu para entrega' || $tipoEntrega === 'entrega';
}

function classeStatusPedido(string $status): string
{
    if ($status === 'Em preparação' || str_starts_with($status, 'Em prepara')) {
        return 'status-preparacao';
    }

    if ($status === 'Pronto') {
        return 'status-pronto';
    }

    if ($status === 'Saiu para entrega') {
        return 'status-entrega';
    }

    if ($status === 'Finalizado') {
        return 'status-finalizado';
    }

    return 'status-recebido';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $pedidoId = (int)($_POST['pedido_id'] ?? 0);
    $novoStatus = trim($_POST['status'] ?? '');

    $stmtTipo = $pdo->prepare(
        "SELECT tipo_entrega FROM pedidos WHERE id = ?"
    );

    $stmtTipo->execute([$pedidoId]);

    $tipoEntregaPedido = (string) $stmtTipo->fetchColumn();

    if (
        $pedidoId > 0 &&
        in_array($novoStatus, $statusPermitidos, true) &&
        statusDisponivel($novoStatus, $tipoEntregaPedido)
    ) {

        $stmt = $pdo->prepare(
            "UPDATE pedidos SET status = ? WHERE id = ?"
        );

        $stmt->execute([
            $novoStatus,
            $pedidoId
        ]);

        $mensagem = 'Status atualizado com sucesso!';
    }
}


/* =========================
   BUSCAR PEDIDOS
========================= */

$stmt = $pdo->query(
    "SELECT * FROM pedidos ORDER BY criado_em DESC, id DESC"
);

$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =========================
   BUSCAR ITENS
========================= */

$stmtItens = $pdo->prepare(
    "SELECT *
     FROM itens_pedido
     WHERE pedido_id = ?
     ORDER BY id"
);

?>
<!DOCTYPE html>

<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Pedidos - Saborê</title>

<style>
* { box-sizing: border-box; }

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: #f4f6f8;
    color: #252525;
}

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
    transition: .2s;
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

.sair a:hover { background: #34373a; }

.main {
    margin-left: 250px;
    padding: 32px;
}

.cabecalho {
    margin-bottom: 25px;
}

.cabecalho h1 {
    margin: 0 0 6px;
    font-size: 29px;
}

.cabecalho p {
    margin: 0;
    color: #777;
}

.mensagem {
    background: #dff5e5;
    color: #167333;
    border: 1px solid #b9e7c5;
    padding: 13px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.pedido {
    background: white;
    border-radius: 10px;
    border: 1px solid #e4e4e4;
    margin-bottom: 22px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
}

.pedido-topo {
    padding: 18px 20px;
    background: #fafafa;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
}

.numero-pedido {
    font-size: 19px;
    font-weight: bold;
}

.data-pedido {
    color: #777;
    font-size: 13px;
    margin-top: 4px;
}

.status {
    padding: 7px 11px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    background: #fff0e5;
    color: #d65400;
}

.pedido-corpo {
    padding: 20px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
}

.bloco h3 {
    margin: 0 0 12px;
    font-size: 16px;
}

.info {
    margin-bottom: 8px;
    color: #555;
    line-height: 1.5;
}

.info strong { color: #222; }

.item {
    padding: 10px 0;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    gap: 15px;
}

.item:last-child { border-bottom: none; }

.total {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 2px solid #eee;
    display: flex;
    justify-content: space-between;
    font-size: 19px;
    font-weight: bold;
}

.alterar-status {
    margin-top: 20px;
    padding-top: 18px;
    border-top: 1px solid #eee;
}

.alterar-status form {
    display: flex;
    gap: 10px;
}

.alterar-status select {
    flex: 1;
    padding: 11px;
    border: 1px solid #ddd;
    border-radius: 7px;
    font-size: 14px;
}

.alterar-status button {
    border: none;
    background: #202020;
    color: white;
    padding: 0 18px;
    border-radius: 7px;
    font-weight: bold;
    cursor: pointer;
}

.alterar-status button:hover { background: #333; }

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

    .menu a { white-space: nowrap; }

    .sair {
        position: static;
        margin-top: 15px;
    }

    .main {
        margin-left: 0;
        padding: 20px;
    }

    .pedido-topo { align-items: flex-start; }
    .pedido-corpo { grid-template-columns: 1fr; }
    .alterar-status form { flex-direction: column; }
    .alterar-status button { padding: 12px; }
}
</style>

<link rel="stylesheet" href="../assets/css/admin.css">

</head>

<body>

<aside class="sidebar">
    <div class="logo">Saborê</div>
    <div class="logo-sub">Painel Administrativo</div>

    <nav class="menu">
        <a href="index.php">Dashboard</a>
        <a href="produto.php">Produtos</a>
        <a href="categorias.php">Categorias</a>
        <a href="configuracoes.php">Configurações</a>
        <a href="pedidos.php" class="ativo">Pedidos</a>
        <a href="../index.php" target="_blank">Ver Cardápio</a>
    </nav>

    <div class="sair">
        <a href="logout.php">Sair</a>
    </div>
</aside>

<main class="main">

    <div class="cabecalho">
        <h1>Pedidos</h1>
        <p>Acompanhe os pedidos realizados pelo cardápio digital.</p>
    </div>



    <?php if ($mensagem !== ''): ?>

        <div class="mensagem">

            <?= htmlspecialchars($mensagem) ?>

        </div>

    <?php endif; ?>


    <?php if (count($pedidos) === 0): ?>

        <div class="estado-vazio">

            <div class="estado-vazio-icone" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="20" r="1.4"/>
                    <circle cx="18" cy="20" r="1.4"/>
                    <path d="M2.5 3.5h2.6l2.4 11.2a1.6 1.6 0 0 0 1.6 1.3h8.6a1.6 1.6 0 0 0 1.6-1.2l1.7-7.3H6.2"/>
                </svg>
            </div>

            <h2>Nenhum pedido recebido</h2>

            <p>
                Quando um cliente finalizar uma compra pelo cardápio digital,
                o pedido aparecerá aqui para você acompanhar e atualizar o status.
            </p>

        </div>

    <?php else: ?>


        <?php foreach ($pedidos as $pedido): ?>

            <?php

            $stmtItens->execute([
                $pedido['id']
            ]);

            $itens =
                $stmtItens->fetchAll(
                    PDO::FETCH_ASSOC
                );

            ?>


            <section class="pedido">


                <div class="pedido-topo">

                    <div>

                        <div class="numero-pedido">

                            Pedido #<?= (int)$pedido['id'] ?>

                        </div>

                        <div class="data-pedido">

                            <?= date(
                                'd/m/Y H:i',
                                strtotime($pedido['criado_em'])
                            ) ?>

                        </div>

                    </div>


                    <span class="status <?= classeStatusPedido($pedido['status']) ?>">

                        <?= htmlspecialchars($pedido['status']) ?>

                    </span>

                </div>


                <div class="pedido-corpo">


                    <!-- CLIENTE -->

                    <div class="bloco">

                        <h3>
                            Dados do cliente
                        </h3>


                        <div class="info">

                            <strong>Nome:</strong>

                            <?= htmlspecialchars(
                                $pedido['nome_cliente']
                            ) ?>

                        </div>


                        <div class="info">

                            <strong>WhatsApp:</strong>

                            <?= htmlspecialchars(
                                $pedido['telefone']
                            ) ?>

                        </div>


                        <div class="info">

                            <strong>Recebimento:</strong>

                            <?= $pedido['tipo_entrega'] === 'entrega'
                                ? 'Entrega'
                                : 'Retirada no estabelecimento'
                            ?>

                        </div>


                        <?php if (
                            $pedido['tipo_entrega'] === 'entrega'
                            && !empty($pedido['endereco'])
                        ): ?>

                            <div class="info">

                                <strong>Endereço:</strong>

                                <?= htmlspecialchars(
                                    $pedido['endereco']
                                ) ?>

                            </div>

                        <?php endif; ?>


                        <?php if (
                            !empty($pedido['observacoes'])
                        ): ?>

                            <div class="info">

                                <strong>Observações:</strong>

                                <?= nl2br(
                                    htmlspecialchars(
                                        $pedido['observacoes']
                                    )
                                ) ?>

                            </div>

                        <?php endif; ?>


                    </div>


                    <!-- ITENS -->

                    <div class="bloco">

                        <h3>
                            Itens do pedido
                        </h3>


                        <?php foreach ($itens as $item): ?>

                            <div class="item">

                                <div>

                                    <?= (int)$item['quantidade'] ?>x

                                    <?= htmlspecialchars(
                                        $item['nome_produto']
                                    ) ?>

                                    <?php if (!empty($item['sabor'])): ?>
                                        <div class="sabor-item-pedido">
                                            Sabor: <?= htmlspecialchars($item['sabor']) ?>
                                        </div>
                                    <?php endif; ?>

                                </div>

                                <strong>

                                    R$
                                    <?= number_format(
                                        $item['subtotal'],
                                        2,
                                        ',',
                                        '.'
                                    ) ?>

                                </strong>

                            </div>

                        <?php endforeach; ?>


                        <div class="total">

                            <span>
                                Total
                            </span>

                            <span>

                                R$
                                <?= number_format(
                                    $pedido['total'],
                                    2,
                                    ',',
                                    '.'
                                ) ?>

                            </span>

                        </div>


                        <div class="alterar-status">

                            <form method="POST">

                                <input
                                    type="hidden"
                                    name="pedido_id"
                                    value="<?= (int)$pedido['id'] ?>"
                                >


                                <select name="status">

                                    <?php foreach (
                                        $statusPermitidos
                                        as $status
                                    ): ?>

                                        <?php if (!statusDisponivel($status, $pedido['tipo_entrega'])) continue; ?>

                                        <option
                                            value="<?= htmlspecialchars($status) ?>"
                                            <?= $pedido['status'] === $status
                                                ? 'selected'
                                                : ''
                                            ?>
                                        >

                                            <?= htmlspecialchars($status) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>


                                <button type="submit">
                                    Atualizar status
                                </button>

                            </form>

                        </div>


                    </div>


                </div>

            </section>


        <?php endforeach; ?>


    <?php endif; ?>


</main>


</body>

</html>
