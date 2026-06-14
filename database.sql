CREATE DATABASE IF NOT EXISTS syntara_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE syntara_db;

CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT, nome VARCHAR(100) NOT NULL, email VARCHAR(255) NOT NULL,
    senha VARCHAR(255) NOT NULL, tipo ENUM('admin','professor','aluno') NOT NULL DEFAULT 'aluno',
    ativo TINYINT(1) NOT NULL DEFAULT 1, criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_usuarios_email (email), INDEX idx_usuarios_tipo (tipo), INDEX idx_usuarios_ativo (ativo),
    INDEX idx_usuarios_tipo_ativo (tipo, ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cursos (
    id INT PRIMARY KEY AUTO_INCREMENT, nome VARCHAR(200) NOT NULL, descricao TEXT, professor_id INT NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1, criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_cursos_professor (professor_id), INDEX idx_cursos_ativo (ativo),
    INDEX idx_cursos_professor_ativo (professor_id, ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE matriculas (
    id INT PRIMARY KEY AUTO_INCREMENT, aluno_id INT NOT NULL, curso_id INT NOT NULL,
    status ENUM('ativo','inativo','concluido') NOT NULL DEFAULT 'ativo',
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_matricula (aluno_id, curso_id),
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_matriculas_aluno (aluno_id), INDEX idx_matriculas_curso (curso_id), INDEX idx_matriculas_status (status),
    INDEX idx_matriculas_aluno_status (aluno_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE aulas (
    id INT PRIMARY KEY AUTO_INCREMENT, curso_id INT NOT NULL, titulo VARCHAR(200) NOT NULL, conteudo LONGTEXT,
    data_aula DATE NOT NULL DEFAULT (CURRENT_DATE), criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_aulas_curso (curso_id), INDEX idx_aulas_data (data_aula), INDEX idx_aulas_curso_data (curso_id, data_aula)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE avaliacoes (
    id INT PRIMARY KEY AUTO_INCREMENT, aluno_id INT NOT NULL, professor_id INT NOT NULL, curso_id INT NOT NULL,
    nota TINYINT NOT NULL, comentario TEXT, criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_avaliacao (aluno_id, professor_id, curso_id), CONSTRAINT chk_avaliacao_nota CHECK (nota BETWEEN 1 AND 5),
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_avaliacoes_professor (professor_id), INDEX idx_avaliacoes_curso (curso_id),
    INDEX idx_avaliacoes_professor_curso (professor_id, curso_id), INDEX idx_avaliacoes_aluno (aluno_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE feedbacks (
    id INT PRIMARY KEY AUTO_INCREMENT, aluno_id INT NOT NULL, professor_id INT NOT NULL, curso_id INT NOT NULL,
    tipo ENUM('elogio','sugestao','reclamacao') NOT NULL DEFAULT 'elogio', nota TINYINT NOT NULL DEFAULT 5,
    mensagem TEXT NOT NULL, criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_feedback (aluno_id, professor_id, curso_id), CONSTRAINT chk_feedback_nota CHECK (nota BETWEEN 1 AND 5),
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_feedbacks_aluno (aluno_id), INDEX idx_feedbacks_professor (professor_id),
    INDEX idx_feedbacks_curso (curso_id), INDEX idx_feedbacks_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE denuncias (
    id INT PRIMARY KEY AUTO_INCREMENT, codigo VARCHAR(12) NOT NULL, curso_id INT DEFAULT NULL, tipo VARCHAR(50) NOT NULL,
    descricao TEXT NOT NULL, status ENUM('pendente','em_analise','resolvida') NOT NULL DEFAULT 'pendente',
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_denuncia_codigo (codigo),
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_denuncias_status (status), INDEX idx_denuncias_codigo (codigo), INDEX idx_denuncias_tipo (tipo),
    INDEX idx_denuncias_status_criado (status, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT, user_id INT NOT NULL, token VARCHAR(64) NOT NULL, expira_em DATETIME NOT NULL,
    usado TINYINT(1) NOT NULL DEFAULT 0, criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_reset_token (token),
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_reset_user (user_id), INDEX idx_reset_usado (usado), INDEX idx_reset_expira (expira_em),
    INDEX idx_reset_user_usado (user_id, usado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE comunicados (
    id INT PRIMARY KEY AUTO_INCREMENT, professor_id INT NOT NULL, curso_id INT DEFAULT NULL, titulo VARCHAR(200) NOT NULL,
    mensagem TEXT NOT NULL, data_evento DATE NOT NULL, tipo ENUM('comunicado','aviso','evento') NOT NULL DEFAULT 'comunicado',
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_comunicados_professor (professor_id), INDEX idx_comunicados_curso (curso_id),
    INDEX idx_comunicados_data (data_evento), INDEX idx_comunicados_tipo (tipo),
    INDEX idx_comunicados_data_tipo (data_evento, tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO usuarios (nome, email, senha, tipo) VALUES
('Administrador', 'admin@syntara.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Prof. Carlos Silva', 'carlos@syntara.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'professor'),
('Ana Souza', 'ana@student.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'aluno');

INSERT INTO cursos (nome, descricao, professor_id, ativo) VALUES
('Matemática Básica', 'Curso introdutório de matemática', 2, 1),
('Física I', 'Mecânica clássica e termodinâmica', 2, 1);

INSERT INTO aulas (curso_id, titulo, conteudo, data_aula) VALUES
(1, 'Introdução à Álgebra', 'Conteúdo sobre álgebra básica: operações, expressões e equações simples.', '2026-05-20'),
(1, 'Equações do 1º Grau', 'Resolvendo equações de primeiro grau com uma incógnita.', '2026-05-22'),
(2, 'Cinemática', 'Movimento uniforme e uniformemente variado. Velocidade, aceleração e equações.', '2026-05-21');

INSERT INTO matriculas (aluno_id, curso_id, status) VALUES (3, 1, 'ativo'), (3, 2, 'ativo');
