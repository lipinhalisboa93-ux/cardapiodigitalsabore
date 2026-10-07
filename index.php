<?php

require_once 'config/db.php';

/* =========================
   CONFIGURAÇÕES DO ESTABELECIMENTO
========================= */

$config = $pdo->query(
    "SELECT * FROM configuracoes ORDER BY id ASC LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);

if (!$config) {
    $config = [
        'nome_estabelecimento' => 'Cardápio Digital',
        'descricao' => 'Escolha seus produtos favoritos',
        'whatsapp' => '',
        'endereco' => '',
        'horario' => '',
        'logo' => ''
    ];
}

/* =========================
   FILTROS
========================= */

$q = trim($_GET['q'] ?? '');

$cat = isset($_GET['cat'])
    ? (int) $_GET['cat']
    : 0;

/* =========================
   CATEGORIAS
========================= */

$categorias = $pdo->query(
    "SELECT * FROM categorias ORDER BY nome"
)->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   PRODUTOS
========================= */

$sql = "
    SELECT
        p.*,
        c.nome AS categoria
    FROM produtos p
    INNER JOIN categorias c
        ON c.id = p.categoria_id
    WHERE p.ativo = 1
";

$params = [];

/* FILTRO CATEGORIA */

if ($cat > 0) {
    $sql .= " AND p.categoria_id = ?";
    $params[] = $cat;
}

/* PESQUISA */

if ($q !== '') {

    $sql .= "
        AND (
            p.nome LIKE ?
            OR p.descricao LIKE ?
            OR c.nome LIKE ?
        )
    ";

    $busca = '%' . $q . '%';

    $params[] = $busca;
    $params[] = $busca;
    $params[] = $busca;
}

$sql .= "
    ORDER BY
        c.nome,
        p.nome
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title><?= htmlspecialchars($config['nome_estabelecimento']) ?></title>

<link rel="stylesheet" href="assets/css/storefront.css">

</head>

<body>

<!-- =========================
     CABEÇALHO
========================= -->

<header class="hero">
    <div class="hero-conteudo">
        <div class="logo-sabore-wrap"><img src="assets/logo-sabore.jpg" alt="Saborê" class="logo-sabore"></div>
        <p><?= htmlspecialchars($config['descricao']) ?></p>
        <?php if (!empty($config['horario'])): ?>
            <div class="hero-horario">Horário: <?= htmlspecialchars($config['horario']) ?></div>
        <?php endif; ?>
    </div>
</header>

<!-- =========================
     CONTEÚDO
========================= -->

<main class="container">

    <!-- PESQUISA -->

    <section class="pesquisa-area">

        <form
            method="GET"
            class="pesquisa-form"
        >

            <?php if ($cat > 0): ?>

                <input
                    type="hidden"
                    name="cat"
                    value="<?= $cat ?>"
                >

            <?php endif; ?>

            <input
                type="text"
                name="q"
                placeholder="O que você procura?"
                value="<?= htmlspecialchars($q) ?>"
            >

            <button type="submit">
                Pesquisar
            </button>

        </form>

        <!-- CATEGORIAS -->

        <div class="categorias">

            <a
                href="index.php<?= $q !== ''
                    ? '?q=' . urlencode($q)
                    : ''
                ?>"
                class="categoria <?= $cat === 0
                    ? 'ativa'
                    : ''
                ?>"
            >
                Todos
            </a>

            <?php foreach ($categorias as $categoria): ?>

                <a
                    href="?cat=<?= $categoria['id'] ?><?= $q !== ''
                        ? '&q=' . urlencode($q)
                        : ''
                    ?>"
                    class="categoria <?= $cat === (int)$categoria['id']
                        ? 'ativa'
                        : ''
                    ?>"
                >
                    <?= htmlspecialchars($categoria['nome']) ?>
                </a>

            <?php endforeach; ?>

        </div>

    </section>

    <!-- TÍTULO -->

    <section class="titulo-produtos">

        <div>

            <h2>

                <?php if ($q !== ''): ?>

                    Resultado da pesquisa

                <?php elseif ($cat > 0): ?>

                    Produtos

                <?php else: ?>

                    Nosso Cardápio

                <?php endif; ?>

            </h2>

            <p>

                <?= count($produtos) ?>

                produto<?= count($produtos) !== 1
                    ? 's'
                    : ''
                ?>

                <?= count($produtos) !== 1
                    ? 'disponíveis'
                    : 'disponível'
                ?>

            </p>

        </div>

        <button
            type="button"
            class="abrir-carrinho"
            id="abrirCarrinho"
        >
            Carrinho
            <span
                class="contador-carrinho"
                id="contadorCarrinho"
            >
                0
            </span>
        </button>

        <a
            href="acompanhar_pedido.php"
            class="acompanhar-pedido-link"
        >
            Acompanhar pedido
        </a>

    </section>

    <!-- PRODUTOS -->

    <section class="produtos">

        <?php if (count($produtos) === 0): ?>

            <div class="sem-resultados">

                <strong>
                    Nenhum produto encontrado
                </strong>

                Tente pesquisar outro produto
                ou escolher outra categoria.

            </div>

        <?php else: ?>

            <?php foreach ($produtos as $produto): ?>
                <?php
                    $categoriaNormalizada = mb_strtolower($produto['categoria'], 'UTF-8');
                    $nomeNormalizado = mb_strtolower($produto['nome'], 'UTF-8');
                    $sabores = [];

                    if (
                        $categoriaNormalizada === 'refrigerantes' ||
                        str_contains($nomeNormalizado, 'refrigerante')
                    ) {
                        $sabores = ['Coca-Cola', 'Fanta', 'Sprite'];
                    } elseif (
                        $categoriaNormalizada === 'sucos' ||
                        str_contains($nomeNormalizado, 'suco')
                    ) {
                        $sabores = ['Laranja', 'Maracujá', 'Uva', 'Limão'];
                    }
                ?>

                <article
                    class="produto-card"
                    data-imagem="<?= htmlspecialchars($produto['imagem'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    data-categoria="<?= htmlspecialchars($produto['categoria'], ENT_QUOTES, 'UTF-8') ?>"
                    data-descricao="<?= htmlspecialchars($produto['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    data-sabores="<?= htmlspecialchars(json_encode($sabores), ENT_QUOTES, 'UTF-8') ?>"
                >

                    <!-- FOTO -->

                    <div
                        class="foto abrir-detalhes"
                        role="button"
                        tabindex="0"
                        aria-label="Ver detalhes de <?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?>"
                    >

                        <?php if (!empty($produto['imagem'])): ?>

                            <img
                                src="<?= htmlspecialchars($produto['imagem']) ?>"
                                alt="<?= htmlspecialchars($produto['nome']) ?>"
                                loading="lazy"
                            >

                        <?php else: ?>

                            <div class="sem-foto">

                                Foto em breve

                            </div>

                        <?php endif; ?>

                    </div>

                    <!-- INFORMAÇÕES -->

                    <div class="produto-conteudo">

                        <div class="produto-categoria">

                            <?= htmlspecialchars(
                                $produto['categoria']
                            ) ?>

                        </div>

                        <div
                            class="produto-nome abrir-detalhes"
                            role="button"
                            tabindex="0"
                        >

                            <?= htmlspecialchars(
                                $produto['nome']
                            ) ?>

                        </div>

                        <div class="produto-descricao">

                            <?= htmlspecialchars(
                                $produto['descricao']
                            ) ?>

                        </div>

                        <?php if (count($sabores) > 0): ?>
                            <div class="sabores-produto">
                                <span>Escolha o sabor</span>

                                <div class="opcoes-sabor">
                                    <?php foreach ($sabores as $sabor): ?>
                                        <label>
                                            <input
                                                type="radio"
                                                name="sabor_<?= (int)$produto['id'] ?>"
                                                value="<?= htmlspecialchars($sabor) ?>"
                                            >
                                            <?= htmlspecialchars($sabor) ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="produto-rodape">

                            <div class="preco">

                                R$

                                <?= number_format(
                                    $produto['preco'],
                                    2,
                                    ',',
                                    '.'
                                ) ?>

                            </div>

                            <button
                                type="button"
                                class="btn-carrinho"
                                data-id="<?= (int)$produto['id'] ?>"
                                data-nome="<?= htmlspecialchars(
                                    $produto['nome'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                data-preco="<?= (float)$produto['preco'] ?>"
                                data-exige-sabor="<?= count($sabores) > 0 ? '1' : '0' ?>"
                            >
                                Adicionar
                            </button>

                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        <?php endif; ?>

    </section>

</main>

<!-- =========================
     FUNDO DO CARRINHO
========================= -->

<div
    class="fundo-carrinho"
    id="fundoCarrinho"
></div>

<!-- =========================
     CARRINHO
========================= -->

<aside
    class="carrinho-lateral"
    id="carrinhoLateral"
>

    <div class="carrinho-topo">

        <h2>
            Seu pedido
        </h2>

        <button
            type="button"
            class="fechar-carrinho"
            id="fecharCarrinho"
        >
            ×
        </button>

    </div>

    <div
        class="carrinho-itens"
        id="carrinhoItens"
    ></div>

    <div class="carrinho-rodape">

        <div class="carrinho-total">

            <span>Total</span>

            <span id="totalCarrinho">
                R$ 0,00
            </span>

        </div>

        <button
            type="button"
            class="finalizar-pedido"
            id="finalizarPedido"
        >
            Finalizar pedido
        </button>

    </div>

</aside>

<div
    class="aviso-carrinho"
    id="avisoCarrinho"
>
    Produto adicionado ao carrinho
</div>

<!-- DETALHES DO PRODUTO -->
<div
    class="modal-pedido modal-produto"
    id="modalProduto"
    role="dialog"
    aria-modal="true"
    aria-labelledby="detalheNome"
>
    <div class="modal-conteudo detalhe-produto">

        <button
            type="button"
            class="fechar-modal fechar-detalhe"
            id="fecharModalProduto"
            aria-label="Fechar detalhes"
        >
            ×
        </button>

        <div class="detalhe-foto" id="detalheFoto"></div>

        <div class="detalhe-info">

            <div class="produto-categoria" id="detalheCategoria"></div>

            <h2 class="produto-nome detalhe-nome" id="detalheNome"></h2>

            <p class="produto-descricao detalhe-descricao" id="detalheDescricao"></p>

            <div class="sabores-produto" id="detalheSabores" hidden>
                <span>Escolha o sabor</span>
                <div class="opcoes-sabor" id="detalheOpcoesSabor"></div>
            </div>

            <div class="detalhe-aviso" id="detalheAviso" role="alert"></div>

            <div class="detalhe-compra">

                <div class="quantidade detalhe-quantidade">
                    <button type="button" id="detalheMenos" aria-label="Diminuir quantidade">−</button>
                    <strong id="detalheQuantidade" aria-live="polite">1</strong>
                    <button type="button" id="detalheMais" aria-label="Aumentar quantidade">+</button>
                </div>

                <div class="detalhe-valores">
                    <div class="preco" id="detalhePreco">R$ 0,00</div>
                    <small id="detalhePrecoUnitario"></small>
                </div>

            </div>

            <button
                type="button"
                class="btn-carrinho detalhe-adicionar"
                id="detalheAdicionar"
            >
                Adicionar ao carrinho
            </button>

        </div>

    </div>
</div>

<!-- FINALIZAÇÃO DO PEDIDO -->
<div class="modal-pedido" id="modalPedido">
    <div class="modal-conteudo">
        <div class="modal-topo"><h2>Finalizar pedido</h2><button type="button" class="fechar-modal" id="fecharModalPedido">×</button></div>
        <form class="form-pedido" id="formPedido">
            <div class="campo"><label for="nomeCliente">Nome *</label><input id="nomeCliente" type="text" maxlength="150" required></div>
            <div class="campo"><label for="telefoneCliente">WhatsApp *</label><input id="telefoneCliente" type="tel" maxlength="30" required placeholder="(11) 99999-9999"></div>
            <div class="campo"><label>Como deseja receber? *</label><div class="opcoes-entrega"><label><input type="radio" name="tipoEntrega" value="retirada" checked> Retirar no estabelecimento</label><label><input type="radio" name="tipoEntrega" value="entrega"> Entrega</label></div></div>
            <div class="campo" id="campoEndereco" style="display:none"><label for="enderecoCliente">Endereço *</label><input id="enderecoCliente" type="text" maxlength="255"></div>
            <div class="campo"><label for="observacoesCliente">Observações</label><textarea id="observacoesCliente" rows="3" placeholder="Ex.: sem cebola, ponto da carne..."></textarea></div>
            <div class="resumo-final"><strong>Resumo do pedido</strong><div id="resumoPedido"></div><div class="resumo-linha resumo-total"><span>Total</span><span id="totalFinal">R$ 0,00</span></div></div>
            <button type="submit" class="confirmar-pedido" id="confirmarPedido">Confirmar pedido</button>
            <div class="mensagem-pedido" id="mensagemPedido"></div>
        </form>
    </div>
</div>

<!-- =========================
     RODAPÉ
========================= -->

<footer>
    Saborê • Hambúrgueres • Lanches • Porções • TCC 2026
</footer>

<!-- =========================
     JAVASCRIPT DO CARRINHO
========================= -->

<script>

const CHAVE_CARRINHO = 'cardapio_tcc_carrinho';

let carrinho = carregarCarrinho();

const carrinhoLateral =
    document.getElementById('carrinhoLateral');

const fundoCarrinho =
    document.getElementById('fundoCarrinho');

const carrinhoItens =
    document.getElementById('carrinhoItens');

const contadorCarrinho =
    document.getElementById('contadorCarrinho');

const totalCarrinho =
    document.getElementById('totalCarrinho');

const avisoCarrinho =
    document.getElementById('avisoCarrinho');


/* =========================
   SALVAR / CARREGAR
========================= */

function carregarCarrinho() {

    try {

        const dados =
            localStorage.getItem(CHAVE_CARRINHO);

        return dados
            ? JSON.parse(dados)
            : [];

    } catch (erro) {

        return [];

    }

}


function salvarCarrinho() {

    localStorage.setItem(
        CHAVE_CARRINHO,
        JSON.stringify(carrinho)
    );

}


/* =========================
   MOEDA
========================= */

function moeda(valor) {

    return Number(valor).toLocaleString(
        'pt-BR',
        {
            style: 'currency',
            currency: 'BRL'
        }
    );

}


/* =========================
   ABRIR / FECHAR
========================= */

function abrirCarrinho() {

    carrinhoLateral.classList.add('ativo');
    fundoCarrinho.classList.add('ativo');

}


function fecharCarrinho() {

    carrinhoLateral.classList.remove('ativo');
    fundoCarrinho.classList.remove('ativo');

}


document
    .getElementById('abrirCarrinho')
    .addEventListener(
        'click',
        abrirCarrinho
    );


document
    .getElementById('fecharCarrinho')
    .addEventListener(
        'click',
        fecharCarrinho
    );


fundoCarrinho.addEventListener(
    'click',
    fecharCarrinho
);


/* =========================
   ADICIONAR PRODUTO
========================= */

/*
 * Regra única do carrinho: usada pelo botão do card
 * e pelo modal de detalhes.
 */
function adicionarAoCarrinho(
    id,
    nome,
    preco,
    sabor,
    quantidade = 1
) {

    const existente =
        carrinho.find(
            item => item.id === id && item.sabor === sabor
        );

    if (existente) {

        existente.quantidade += quantidade;

    } else {

        carrinho.push({
            id: id,
            nome: nome,
            sabor: sabor,
            preco: preco,
            quantidade: quantidade
        });

    }

    salvarCarrinho();
    atualizarCarrinho();
    mostrarAviso();

}


document
    .querySelectorAll('.produto-card .btn-carrinho')
    .forEach(botao => {

        botao.addEventListener(
            'click',
            function () {

                const id =
                    Number(this.dataset.id);

                const nome =
                    this.dataset.nome;

                const preco =
                    Number(this.dataset.preco);

                const exigeSabor =
                    this.dataset.exigeSabor === '1';

                let sabor = '';

                if (exigeSabor) {
                    const selecionado =
                        document.querySelector(`input[name="sabor_${id}"]:checked`);

                    if (!selecionado) {
                        alert('Escolha o sabor antes de adicionar ao carrinho.');
                        return;
                    }

                    sabor = selecionado.value;
                }

                adicionarAoCarrinho(id, nome, preco, sabor, 1);

            }
        );

    });


/* =========================
   DETALHES DO PRODUTO
========================= */

const modalProduto = document.getElementById('modalProduto');
const detalheFoto = document.getElementById('detalheFoto');
const detalheCategoria = document.getElementById('detalheCategoria');
const detalheNome = document.getElementById('detalheNome');
const detalheDescricao = document.getElementById('detalheDescricao');
const detalheSabores = document.getElementById('detalheSabores');
const detalheOpcoesSabor = document.getElementById('detalheOpcoesSabor');
const detalheAviso = document.getElementById('detalheAviso');
const detalheQuantidade = document.getElementById('detalheQuantidade');
const detalhePreco = document.getElementById('detalhePreco');
const detalhePrecoUnitario = document.getElementById('detalhePrecoUnitario');

let produtoDetalhe = null;
let quantidadeDetalhe = 1;
let elementoAntesDoModal = null;

function abrirDetalhes(card) {

    const botao = card.querySelector('.btn-carrinho');

    let sabores = [];

    try {
        sabores = JSON.parse(card.dataset.sabores || '[]');
    } catch (erro) {
        sabores = [];
    }

    produtoDetalhe = {
        id: Number(botao.dataset.id),
        nome: botao.dataset.nome,
        preco: Number(botao.dataset.preco),
        sabores: sabores
    };

    quantidadeDetalhe = 1;

    /* Foto */
    detalheFoto.innerHTML = '';

    if (card.dataset.imagem) {
        const img = document.createElement('img');
        img.src = card.dataset.imagem;
        img.alt = produtoDetalhe.nome;
        detalheFoto.appendChild(img);
    } else {
        const semFoto = document.createElement('div');
        semFoto.className = 'sem-foto';
        semFoto.textContent = 'Foto em breve';
        detalheFoto.appendChild(semFoto);
    }

    /* Textos */
    detalheCategoria.textContent = card.dataset.categoria || '';
    detalheNome.textContent = produtoDetalhe.nome;
    detalheDescricao.textContent = card.dataset.descricao || '';
    detalheDescricao.hidden = !card.dataset.descricao;

    /* Sabores (já marca o sabor escolhido no card, se houver) */
    detalheOpcoesSabor.innerHTML = '';

    const saborNoCard =
        document.querySelector(`input[name="sabor_${produtoDetalhe.id}"]:checked`);

    sabores.forEach(sabor => {
        const label = document.createElement('label');
        const input = document.createElement('input');

        input.type = 'radio';
        input.name = 'sabor_detalhe';
        input.value = sabor;
        input.checked = saborNoCard !== null && saborNoCard.value === sabor;
        input.addEventListener('change', limparAvisoDetalhe);

        label.appendChild(input);
        label.appendChild(document.createTextNode(' ' + sabor));
        detalheOpcoesSabor.appendChild(label);
    });

    detalheSabores.hidden = sabores.length === 0;

    limparAvisoDetalhe();
    atualizarQuantidadeDetalhe();

    elementoAntesDoModal = document.activeElement;
    modalProduto.classList.add('ativo');
    document.body.classList.add('modal-aberto');
    document.getElementById('fecharModalProduto').focus();

}

function fecharDetalhes() {

    if (!modalProduto.classList.contains('ativo')) {
        return;
    }

    modalProduto.classList.remove('ativo');
    document.body.classList.remove('modal-aberto');

    if (elementoAntesDoModal) {
        elementoAntesDoModal.focus();
    }

}

function atualizarQuantidadeDetalhe() {

    detalheQuantidade.textContent = quantidadeDetalhe;
    detalhePreco.textContent = moeda(produtoDetalhe.preco * quantidadeDetalhe);
    detalhePrecoUnitario.textContent = quantidadeDetalhe > 1
        ? `${moeda(produtoDetalhe.preco)} cada`
        : '';

}

function limparAvisoDetalhe() {

    detalheAviso.textContent = '';
    detalheAviso.classList.remove('ativo');

}

/* Abrir ao clicar no card (exceto botão e opções de sabor) */
document.querySelectorAll('.produto-card').forEach(card => {

    card.addEventListener('click', e => {
        if (e.target.closest('button, input, label')) {
            return;
        }

        abrirDetalhes(card);
    });

});

/* Acesso pelo teclado na foto e no nome */
document.querySelectorAll('.abrir-detalhes').forEach(gatilho => {

    gatilho.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            abrirDetalhes(gatilho.closest('.produto-card'));
        }
    });

});

document.getElementById('detalheMenos').addEventListener('click', () => {
    if (quantidadeDetalhe > 1) {
        quantidadeDetalhe--;
        atualizarQuantidadeDetalhe();
    }
});

document.getElementById('detalheMais').addEventListener('click', () => {
    quantidadeDetalhe++;
    atualizarQuantidadeDetalhe();
});

document.getElementById('detalheAdicionar').addEventListener('click', () => {

    let sabor = '';

    if (produtoDetalhe.sabores.length > 0) {
        const selecionado =
            detalheOpcoesSabor.querySelector('input[name="sabor_detalhe"]:checked');

        if (!selecionado) {
            detalheAviso.textContent = 'Escolha o sabor antes de adicionar ao carrinho.';
            detalheAviso.classList.add('ativo');
            return;
        }

        sabor = selecionado.value;
    }

    adicionarAoCarrinho(
        produtoDetalhe.id,
        produtoDetalhe.nome,
        produtoDetalhe.preco,
        sabor,
        quantidadeDetalhe
    );

    fecharDetalhes();

});

document.getElementById('fecharModalProduto').addEventListener('click', fecharDetalhes);

modalProduto.addEventListener('click', e => {
    if (e.target === modalProduto) fecharDetalhes();
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') fecharDetalhes();
});


/* =========================
   ALTERAR QUANTIDADE
========================= */

function alterarQuantidade(
    indice,
    quantidade
) {

    const item =
        carrinho[indice];

    if (!item) {
        return;
    }

    item.quantidade += quantidade;

    if (item.quantidade <= 0) {

        carrinho.splice(indice, 1);

    }

    salvarCarrinho();
    atualizarCarrinho();

}


/* =========================
   REMOVER
========================= */

function removerProduto(indice) {

    carrinho.splice(indice, 1);

    salvarCarrinho();
    atualizarCarrinho();

}


/* =========================
   ATUALIZAR CARRINHO
========================= */

function atualizarCarrinho() {

    carrinhoItens.innerHTML = '';

    let quantidadeTotal = 0;
    let valorTotal = 0;

    carrinho.forEach((item, indice) => {

        quantidadeTotal +=
            item.quantidade;

        valorTotal +=
            item.preco * item.quantidade;

    });


    contadorCarrinho.textContent =
        quantidadeTotal;


    totalCarrinho.textContent =
        moeda(valorTotal);


    if (carrinho.length === 0) {

        carrinhoItens.innerHTML = `
            <div class="carrinho-vazio">

                Seu carrinho está vazio.

            </div>
        `;

        return;

    }


    carrinho.forEach((item, indice) => {

        const elemento =
            document.createElement('div');

        elemento.className =
            'item-carrinho';


        elemento.innerHTML = `

            <div class="item-carrinho-nome">
                ${escaparHTML(item.nome)}
            </div>

            ${item.sabor ? `<div class="item-carrinho-sabor">Sabor: ${escaparHTML(item.sabor)}</div>` : ''}

            <div class="item-carrinho-info">

                <div class="quantidade">

                    <button
                        type="button"
                        onclick="alterarQuantidade(${indice}, -1)"
                    >
                        −
                    </button>

                    <strong>
                        ${item.quantidade}
                    </strong>

                    <button
                        type="button"
                        onclick="alterarQuantidade(${indice}, 1)"
                    >
                        +
                    </button>

                </div>

                <strong>
                    ${moeda(
                        item.preco *
                        item.quantidade
                    )}
                </strong>

            </div>

            <button
                type="button"
                class="remover-item"
                onclick="removerProduto(${indice})"
            >
                Remover
            </button>

        `;


        carrinhoItens.appendChild(
            elemento
        );

    });

}


/* =========================
   SEGURANÇA DO TEXTO
========================= */

function escaparHTML(texto) {

    const div =
        document.createElement('div');

    div.textContent = texto;

    return div.innerHTML;

}


/* =========================
   AVISO
========================= */

let tempoAviso;

function mostrarAviso() {

    avisoCarrinho.classList.add(
        'ativo'
    );

    clearTimeout(tempoAviso);

    tempoAviso =
        setTimeout(
            () => {

                avisoCarrinho
                    .classList
                    .remove('ativo');

            },
            1800
        );

}


/* =========================
   FINALIZAR PEDIDO
========================= */

const modalPedido = document.getElementById('modalPedido');
const formPedido = document.getElementById('formPedido');
const campoEndereco = document.getElementById('campoEndereco');
const enderecoCliente = document.getElementById('enderecoCliente');
const resumoPedido = document.getElementById('resumoPedido');
const totalFinal = document.getElementById('totalFinal');
const mensagemPedido = document.getElementById('mensagemPedido');
const confirmarPedido = document.getElementById('confirmarPedido');

function abrirFinalizacao() {
    if (carrinho.length === 0) { alert('Adicione pelo menos um produto ao carrinho.'); return; }
    resumoPedido.innerHTML = '';
    let total = 0;
    carrinho.forEach(item => {
        total += item.preco * item.quantidade;
        const linha = document.createElement('div');
        linha.className = 'resumo-linha';
        linha.innerHTML = `<span>${item.quantidade}x ${escaparHTML(item.nome)}${item.sabor ? `<br><small>Sabor: ${escaparHTML(item.sabor)}</small>` : ''}</span><strong>${moeda(item.preco * item.quantidade)}</strong>`;
        resumoPedido.appendChild(linha);
    });
    totalFinal.textContent = moeda(total);
    mensagemPedido.className = 'mensagem-pedido';
    mensagemPedido.textContent = '';
    fecharCarrinho();
    modalPedido.classList.add('ativo');
}

function fecharFinalizacao() { modalPedido.classList.remove('ativo'); }

document.getElementById('finalizarPedido').addEventListener('click', abrirFinalizacao);
document.getElementById('fecharModalPedido').addEventListener('click', fecharFinalizacao);
modalPedido.addEventListener('click', e => { if (e.target === modalPedido) fecharFinalizacao(); });

document.querySelectorAll('input[name="tipoEntrega"]').forEach(radio => {
    radio.addEventListener('change', () => {
        const entrega = document.querySelector('input[name="tipoEntrega"]:checked').value === 'entrega';
        campoEndereco.style.display = entrega ? 'block' : 'none';
        enderecoCliente.required = entrega;
        if (!entrega) enderecoCliente.value = '';
    });
});

formPedido.addEventListener('submit', async e => {
    e.preventDefault();
    if (carrinho.length === 0) return;
    const dados = {
        nome_cliente: document.getElementById('nomeCliente').value.trim(),
        telefone: document.getElementById('telefoneCliente').value.trim(),
        tipo_entrega: document.querySelector('input[name="tipoEntrega"]:checked').value,
        endereco: enderecoCliente.value.trim(),
        observacoes: document.getElementById('observacoesCliente').value.trim(),
        itens: carrinho.map(item => ({ id: item.id, quantidade: item.quantidade, sabor: item.sabor || '' }))
    };
    confirmarPedido.disabled = true;
    confirmarPedido.textContent = 'Enviando pedido...';
    mensagemPedido.className = 'mensagem-pedido';
    try {
        const resposta = await fetch('salvar_pedido.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(dados) });
        const resultado = await resposta.json();
        if (!resposta.ok || !resultado.sucesso) throw new Error(resultado.mensagem || 'Não foi possível realizar o pedido.');
        mensagemPedido.className = 'mensagem-pedido sucesso';
        mensagemPedido.innerHTML = `
            <strong>Pedido realizado com sucesso!</strong><br>
            Pedido nº ${resultado.pedido_id}<br>
            Total: ${moeda(resultado.total)}<br>
            <a class="link-acompanhar-sucesso" href="acompanhar_pedido.php?pedido_id=${encodeURIComponent(resultado.pedido_id)}">
                Acompanhar pedido
            </a>
        `;
        carrinho = [];
        salvarCarrinho();
        atualizarCarrinho();
        formPedido.reset();
        campoEndereco.style.display = 'none';
        enderecoCliente.required = false;
    } catch (erro) {
        mensagemPedido.className = 'mensagem-pedido erro';
        mensagemPedido.textContent = erro.message;
    } finally {
        confirmarPedido.disabled = false;
        confirmarPedido.textContent = 'Confirmar pedido';
    }
});

/* INICIALIZA */

atualizarCarrinho();

</script>

</body>

</html>
