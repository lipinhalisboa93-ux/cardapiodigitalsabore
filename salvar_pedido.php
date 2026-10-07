<?php

header('Content-Type: application/json; charset=utf-8');

require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Método não permitido.'
    ]);

    exit;
}

$dados = json_decode(
    file_get_contents('php://input'),
    true
);

if (!$dados) {

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Dados do pedido inválidos.'
    ]);

    exit;
}

/* =========================
   DADOS DO CLIENTE
========================= */

$nome = trim(
    $dados['nome_cliente'] ?? ''
);

$telefone = trim(
    $dados['telefone'] ?? ''
);

$tipoEntrega =
    $dados['tipo_entrega'] ?? 'retirada';

$endereco = trim(
    $dados['endereco'] ?? ''
);

$observacoes = trim(
    $dados['observacoes'] ?? ''
);

$itens =
    $dados['itens'] ?? [];


/* =========================
   VALIDAÇÕES
========================= */

if ($nome === '') {

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Informe o nome do cliente.'
    ]);

    exit;
}


if ($telefone === '') {

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Informe o WhatsApp.'
    ]);

    exit;
}


if (
    !in_array(
        $tipoEntrega,
        ['retirada', 'entrega'],
        true
    )
) {

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Tipo de entrega inválido.'
    ]);

    exit;
}


if (
    $tipoEntrega === 'entrega'
    && $endereco === ''
) {

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Informe o endereço para entrega.'
    ]);

    exit;
}


if (
    !is_array($itens)
    || count($itens) === 0
) {

    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'O carrinho está vazio.'
    ]);

    exit;
}


/* =========================
   BUSCAR PRODUTOS NO BANCO
========================= */

try {

    $pdo->beginTransaction();

    $itensValidos = [];

    $total = 0;


    foreach ($itens as $item) {

        $produtoId =
            (int)($item['id'] ?? 0);

        $quantidade =
            (int)($item['quantidade'] ?? 0);

        $sabor =
            trim($item['sabor'] ?? '');


        if (
            $produtoId <= 0
            || $quantidade <= 0
        ) {

            throw new Exception(
                'Existe um item inválido no carrinho.'
            );

        }


        /*
         * IMPORTANTE:
         * O preço enviado pelo navegador NÃO é usado.
         * O preço verdadeiro é consultado novamente
         * no banco de dados.
         */

        $stmtProduto = $pdo->prepare(
            "
            SELECT
                p.id,
                p.nome,
                p.preco,
                c.nome AS categoria
            FROM produtos p
            INNER JOIN categorias c
                ON c.id = p.categoria_id
            WHERE p.id = ?
              AND p.ativo = 1
            LIMIT 1
            "
        );

        $stmtProduto->execute([
            $produtoId
        ]);

        $produto =
            $stmtProduto->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$produto) {

            throw new Exception(
                'Um dos produtos não está mais disponível.'
            );

        }

        $categoriaProduto = mb_strtolower($produto['categoria'], 'UTF-8');
        $nomeProduto = mb_strtolower($produto['nome'], 'UTF-8');
        $saboresPermitidos = [];

        if (
            $categoriaProduto === 'refrigerantes' ||
            str_contains($nomeProduto, 'refrigerante')
        ) {
            $saboresPermitidos = ['Coca-Cola', 'Fanta', 'Sprite'];
        } elseif (
            $categoriaProduto === 'sucos' ||
            str_contains($nomeProduto, 'suco')
        ) {
            $saboresPermitidos = ['Laranja', 'Maracujá', 'Uva', 'Limão'];
        }

        if (count($saboresPermitidos) > 0) {
            if ($sabor === '' || !in_array($sabor, $saboresPermitidos, true)) {
                throw new Exception(
                    'Escolha um sabor válido para ' . $produto['nome'] . '.'
                );
            }
        } else {
            $sabor = '';
        }


        $preco =
            (float)$produto['preco'];

        $subtotal =
            $preco * $quantidade;

        $total += $subtotal;


        $itensValidos[] = [

            'produto_id' =>
                (int)$produto['id'],

            'nome_produto' =>
                $produto['nome'],

            'sabor' =>
                $sabor !== '' ? $sabor : null,

            'preco_unitario' =>
                $preco,

            'quantidade' =>
                $quantidade,

            'subtotal' =>
                $subtotal

        ];

    }


    /* =========================
       CRIAR PEDIDO
    ========================= */

    $stmtPedido = $pdo->prepare(
        "
        INSERT INTO pedidos
        (
            nome_cliente,
            telefone,
            tipo_entrega,
            endereco,
            observacoes,
            total,
            status
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            'Recebido'
        )
        "
    );


    $stmtPedido->execute([

        $nome,
        $telefone,
        $tipoEntrega,

        $tipoEntrega === 'entrega'
            ? $endereco
            : null,

        $observacoes !== ''
            ? $observacoes
            : null,

        $total

    ]);


    $pedidoId =
        (int)$pdo->lastInsertId();


    /* =========================
       ITENS DO PEDIDO
    ========================= */

    $stmtItem = $pdo->prepare(
        "
        INSERT INTO itens_pedido
        (
            pedido_id,
            produto_id,
            nome_produto,
            sabor,
            preco_unitario,
            quantidade,
            subtotal
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)
        "
    );


    foreach (
        $itensValidos as $item
    ) {

        $stmtItem->execute([

            $pedidoId,

            $item['produto_id'],

            $item['nome_produto'],

            $item['sabor'],

            $item['preco_unitario'],

            $item['quantidade'],

            $item['subtotal']

        ]);

    }


    $pdo->commit();


    echo json_encode([

        'sucesso' => true,

        'pedido_id' =>
            $pedidoId,

        'total' =>
            number_format(
                $total,
                2,
                '.',
                ''
            ),

        'mensagem' =>
            'Pedido realizado com sucesso!'

    ]);


} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }


    http_response_code(500);


    echo json_encode([

        'sucesso' => false,

        'mensagem' =>
            'Não foi possível realizar o pedido: '
            . $e->getMessage()

    ]);

}
