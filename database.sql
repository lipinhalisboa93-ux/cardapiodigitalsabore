CREATE DATABASE IF NOT EXISTS cardapio_digital CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cardapio_digital;

CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL
);

CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    nome VARCHAR(120) NOT NULL,
    descricao VARCHAR(255),
    preco DECIMAL(10,2) NOT NULL,
    imagem VARCHAR(255),
    ativo TINYINT(1) DEFAULT 1,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
);

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL
);

CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_cliente VARCHAR(150) NOT NULL,
    telefone VARCHAR(30) NOT NULL,
    tipo_entrega ENUM('retirada','entrega') NOT NULL DEFAULT 'retirada',
    endereco VARCHAR(255) DEFAULT NULL,
    observacoes TEXT DEFAULT NULL,
    total DECIMAL(10,2) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Recebido',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE itens_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    produto_id INT NOT NULL,
    nome_produto VARCHAR(150) NOT NULL,
    sabor VARCHAR(100) NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    quantidade INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
);

INSERT INTO categorias(nome) VALUES
('Lanches'),
('Porções'),
('Refrigerantes'),
('Sobremesas'),
('Sucos');

INSERT INTO produtos(categoria_id,nome,descricao,preco) VALUES
(1,'X-Burger','Pão, hambúrguer, queijo e molho da casa',22.90),
(1,'X-Salada','Pão, hambúrguer, queijo, alface e tomate',25.90),
(2,'Batata Frita','Porção de batata frita crocante',18.00),
(3,'Refrigerante','Lata 350 ml',6.00),
(4,'Pudim','Pudim tradicional',9.50),
(5,'Suco','Suco natural gelado',8.00);

-- Login inicial: admin@cardapio.local / admin123
INSERT INTO usuarios(nome,email,senha) VALUES
('Administrador','admin@cardapio.local','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
