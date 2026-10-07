<?php

require_once 'config/db.php';

$pedidoId = isset($_GET['pedido_id']) ? (int) $_GET['pedido_id'] : 0;
$telefone = trim($_GET['telefone'] ?? '');
$erro = '';
$pedido = null;
$itens = [];

function limparTelefone(string $telefone): string
{
    return preg_replace('/\D+/', '', $telefone);
}

function moedaPedido(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function statusPedidoCliente(string $status): string
{
    if (str_starts_with($status, 'Em prepara')) {
        return 'Em preparação';
    }

    return $status;
}

function etapaClasse(string $etapa, string $statusAtual, string $tipoEntrega): string
{
    $statusAtual = statusPedidoCliente($statusAtual);

    $ordem = ['Recebido', 'Em preparação', 'Pronto'];

    if ($tipoEntrega === 'entrega') {
        $ordem[] = 'Saiu para entrega';
    }

    $ordem[] = 'Finalizado';

    $indiceEtapa = array_search($etapa, $ordem, true);
    $indiceAtual = array_search($statusAtual, $ordem, true);

    if ($etapa === 'Saiu para entrega' && $statusAtual === 'Finalizado') {
        $indiceAtual = array_search('Finalizado', $ordem, true);
    }

    if ($indiceEtapa === false || $indiceAtual === false) {
        return '';
    }

    if ($indiceEtapa < $indiceAtual) {
        return 'concluida';
    }

    if ($indiceEtapa === $indiceAtual) {
        return 'atual';
    }

    return '';
}

if ($pedidoId > 0 || $telefone !== '') {
    if ($pedidoId <= 0 || $telefone === '') {
        $erro = 'Informe o número do pedido e o telefone usado na compra.';
    } else {
        $stmt = $pdo->prepare(
            "SELECT *
             FROM pedidos
             WHERE id = ?
             LIMIT 1"
        );

        $stmt->execute([$pedidoId]);
        $pedidoEncontrado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            !$pedidoEncontrado ||
            limparTelefone($pedidoEncontrado['telefone']) !== limparTelefone($telefone)
        ) {
            $erro = 'Pedido não encontrado. Confira o número do pedido e o telefone informado.';
        } else {
            $pedido = $pedidoEncontrado;

            $stmtItens = $pdo->prepare(
                "SELECT *
                 FROM itens_pedido
                 WHERE pedido_id = ?
                 ORDER BY id"
            );

            $stmtItens->execute([$pedidoId]);
            $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

$etapas = ['Recebido', 'Em preparação', 'Pronto'];

if (($pedido['tipo_entrega'] ?? '') === 'entrega') {
    $etapas[] = 'Saiu para entrega';
}

$etapas[] = 'Finalizado';

/* =========================
   DADOS PARA ATUALIZAÇÃO AUTOMÁTICA
========================= */

function rotuloEtapa(string $etapa): string
{
    return $etapa === 'Finalizado' ? 'Concluído' : $etapa;
}

function dadosPedidoCliente(array $pedido, array $itens, array $etapas): array
{
    return [
        'status' => statusPedidoCliente($pedido['status']),
        'finalizado' => $pedido['status'] === 'Finalizado',
        'etapas' => array_map(
            fn ($etapa) => [
                'rotulo' => rotuloEtapa($etapa),
                'classe' => etapaClasse($etapa, $pedido['status'], $pedido['tipo_entrega'])
            ],
            $etapas
        ),
        'itens' => array_map(
            fn ($item) => [
                'quantidade' => (int) $item['quantidade'],
                'nome' => $item['nome_produto'],
                'sabor' => $item['sabor'] ?? '',
                'subtotal' => moedaPedido((float) $item['subtotal'])
            ],
            $itens
        ),
        'total' => moedaPedido((float) $pedido['total'])
    ];
}

/* Resposta JSON: mesma busca e validação de pedido + telefone acima */
if (($_GET['formato'] ?? '') === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    if (!$pedido) {
        echo json_encode([
            'sucesso' => false,
            'mensagem' => $erro !== '' ? $erro : 'Pedido não encontrado.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(
        ['sucesso' => true] + dadosPedidoCliente($pedido, $itens, $etapas),
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acompanhar pedido - Saborê</title>
    <link rel="stylesheet" href="assets/css/storefront.css">
</head>
<body>

<header class="hero hero-acompanhamento">
    <div class="hero-conteudo">
        <div class="logo-sabore-wrap">
            <img src="assets/logo-sabore.jpg" alt="Saborê" class="logo-sabore">
        </div>
        <p>Acompanhe o andamento do seu pedido.</p>
    </div>
</header>

<main class="container acompanhamento-container">

    <section class="acompanhamento-card">
        <div class="acompanhamento-topo">
            <div>
                <h1>Acompanhar pedido</h1>
                <p>Informe o número do pedido e o WhatsApp usado na compra.</p>
            </div>

            <a href="index.php" class="link-voltar-cardapio">
                Voltar ao cardápio
            </a>
        </div>

        <form method="GET" class="form-acompanhamento">
            <div class="campo">
                <label for="pedido_id">Número do pedido</label>
                <input
                    type="number"
                    id="pedido_id"
                    name="pedido_id"
                    min="1"
                    value="<?= $pedidoId > 0 ? (int)$pedidoId : '' ?>"
                    required
                >
            </div>

            <div class="campo">
                <label for="telefone">WhatsApp</label>
                <input
                    type="tel"
                    id="telefone"
                    name="telefone"
                    value="<?= htmlspecialchars($telefone) ?>"
                    placeholder="(11) 99999-9999"
                    required
                >
            </div>

            <button type="submit" class="confirmar-pedido">
                Consultar pedido
            </button>
        </form>

        <?php if ($erro !== ''): ?>
            <div class="mensagem-pedido erro acompanhamento-msg">
                <?= htmlspecialchars($erro) ?>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($pedido): ?>
        <section class="pedido-cliente-card">
            <div class="pedido-cliente-header">
                <div>
                    <span class="pedido-etiqueta">Pedido #<?= (int)$pedido['id'] ?></span>
                    <h2><?= htmlspecialchars($pedido['nome_cliente']) ?></h2>
                    <?php if (!empty($pedido['criado_em'])): ?>
                        <p><?= date('d/m/Y H:i', strtotime($pedido['criado_em'])) ?></p>
                    <?php endif; ?>
                </div>

                <strong class="status-cliente" id="statusPedido" aria-live="polite">
                    <?= htmlspecialchars(statusPedidoCliente($pedido['status'])) ?>
                </strong>
            </div>

            <div class="pedido-cliente-grid">
                <div>
                    <h3>Dados do pedido</h3>

                    <div class="detalhe-pedido">
                        <span>Tipo</span>
                        <strong>
                            <?= $pedido['tipo_entrega'] === 'entrega'
                                ? 'Entrega'
                                : 'Retirada'
                            ?>
                        </strong>
                    </div>

                    <div class="detalhe-pedido">
                        <span>Total</span>
                        <strong id="totalPedido"><?= moedaPedido((float)$pedido['total']) ?></strong>
                    </div>
                </div>

                <div>
                    <h3>Itens</h3>

                    <div id="itensPedido">
                    <?php foreach ($itens as $item): ?>
                        <div class="item-acompanhamento">
                            <span>
                                <?= (int)$item['quantidade'] ?>x
                                <?= htmlspecialchars($item['nome_produto']) ?>
                                <?php if (!empty($item['sabor'])): ?>
                                    <small>Sabor: <?= htmlspecialchars($item['sabor']) ?></small>
                                <?php endif; ?>
                            </span>
                            <strong><?= moedaPedido((float)$item['subtotal']) ?></strong>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="linha-andamento" id="linhaAndamento">
                <?php foreach ($etapas as $etapa): ?>
                    <div class="etapa-pedido <?= etapaClasse($etapa, $pedido['status'], $pedido['tipo_entrega']) ?>">
                        <span></span>
                        <strong>
                            <?= $etapa === 'Finalizado'
                                ? 'Concluído'
                                : htmlspecialchars($etapa)
                            ?>
                        </strong>
                    </div>
                <?php endforeach; ?>
            </div>

            <p class="atualizacao-status" id="atualizacaoStatus" aria-live="polite"></p>
        </section>
    <?php endif; ?>

</main>

<footer>
    Saborê • Acompanhamento de pedidos
</footer>

<?php if ($pedido): ?>
<script>

/* =========================
   ATUALIZAÇÃO AUTOMÁTICA DO STATUS
========================= */

const INTERVALO_CONSULTA = 5000;
const INTERVALO_MAXIMO = 30000;
const TEMPO_LIMITE = 8000;

const urlStatus = 'acompanhar_pedido.php?' + new URLSearchParams(
    <?= json_encode([
        'pedido_id' => (int) $pedido['id'],
        'telefone' => $telefone,
        'formato' => 'json'
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
);

let estadoAtual = <?= json_encode(
    dadosPedidoCliente($pedido, $itens, $etapas),
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE
) ?>;

const statusPedido = document.getElementById('statusPedido');
const linhaAndamento = document.getElementById('linhaAndamento');
const itensPedido = document.getElementById('itensPedido');
const totalPedido = document.getElementById('totalPedido');
const atualizacaoStatus = document.getElementById('atualizacaoStatus');

let temporizador = null;
let consultaEmAndamento = false;
let falhasSeguidas = 0;
let consultaEncerrada = estadoAtual.finalizado;

function assinaturaStatus(dados) {
    return JSON.stringify([dados.status, dados.etapas]);
}

function assinaturaItens(dados) {
    return JSON.stringify([dados.itens, dados.total]);
}

function renderizarEtapas(etapas) {
    linhaAndamento.innerHTML = '';

    etapas.forEach(etapa => {
        const div = document.createElement('div');
        const rotulo = document.createElement('strong');

        div.className = ('etapa-pedido ' + etapa.classe).trim();
        rotulo.textContent = etapa.rotulo;

        div.appendChild(document.createElement('span'));
        div.appendChild(rotulo);
        linhaAndamento.appendChild(div);
    });
}

function renderizarItens(itens) {
    itensPedido.innerHTML = '';

    itens.forEach(item => {
        const linha = document.createElement('div');
        const descricao = document.createElement('span');
        const subtotal = document.createElement('strong');

        linha.className = 'item-acompanhamento';
        descricao.textContent = `${item.quantidade}x ${item.nome} `;

        if (item.sabor) {
            const sabor = document.createElement('small');
            sabor.textContent = `Sabor: ${item.sabor}`;
            descricao.appendChild(sabor);
        }

        subtotal.textContent = item.subtotal;

        linha.appendChild(descricao);
        linha.appendChild(subtotal);
        itensPedido.appendChild(linha);
    });
}

/* Atualiza somente o que mudou */
function aplicarDados(dados) {
    if (assinaturaStatus(dados) !== assinaturaStatus(estadoAtual)) {
        statusPedido.textContent = dados.status;
        renderizarEtapas(dados.etapas);

        statusPedido.classList.remove('status-atualizado');
        void statusPedido.offsetWidth;
        statusPedido.classList.add('status-atualizado');
    }

    if (assinaturaItens(dados) !== assinaturaItens(estadoAtual)) {
        renderizarItens(dados.itens);
        totalPedido.textContent = dados.total;
    }

    estadoAtual = dados;
}

function mostrarSituacao(texto, erro = false) {
    atualizacaoStatus.textContent = texto;
    atualizacaoStatus.classList.toggle('erro', erro);
}

function horaAtual() {
    return new Date().toLocaleTimeString('pt-BR');
}

function proximoIntervalo() {
    if (falhasSeguidas === 0) {
        return INTERVALO_CONSULTA;
    }

    return Math.min(
        INTERVALO_CONSULTA * 2 ** falhasSeguidas,
        INTERVALO_MAXIMO
    );
}

function agendarConsulta(espera) {
    clearTimeout(temporizador);

    if (consultaEncerrada || document.hidden) {
        return;
    }

    temporizador = setTimeout(consultarStatus, espera);
}

async function consultarStatus() {
    /* Nunca mais de uma requisição ao mesmo tempo */
    if (consultaEmAndamento || consultaEncerrada || document.hidden) {
        return;
    }

    consultaEmAndamento = true;

    const controle = new AbortController();
    const limite = setTimeout(() => controle.abort(), TEMPO_LIMITE);

    try {
        const resposta = await fetch(urlStatus, {
            cache: 'no-store',
            headers: { 'Accept': 'application/json' },
            signal: controle.signal
        });

        const dados = await resposta.json();

        if (!resposta.ok || !dados.sucesso) {
            throw new Error(dados.mensagem || 'Falha ao consultar o pedido.');
        }

        aplicarDados(dados);
        falhasSeguidas = 0;

        if (dados.finalizado) {
            consultaEncerrada = true;
            mostrarSituacao('Pedido concluído.');
        } else {
            mostrarSituacao(`Atualizado às ${horaAtual()}`);
        }
    } catch (erro) {
        falhasSeguidas++;
        mostrarSituacao('Sem conexão com o servidor. Tentando novamente…', true);
    } finally {
        clearTimeout(limite);
        consultaEmAndamento = false;
        agendarConsulta(proximoIntervalo());
    }
}

/* Pausa com a aba oculta e retoma ao voltar */
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        clearTimeout(temporizador);
    } else {
        consultarStatus();
    }
});

if (consultaEncerrada) {
    mostrarSituacao('Pedido concluído.');
} else {
    mostrarSituacao('O status é atualizado automaticamente.');
    agendarConsulta(INTERVALO_CONSULTA);
}

</script>
<?php endif; ?>

</body>
</html>
