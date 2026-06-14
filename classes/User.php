<?php
/**
 * Syntara v3.0 — Repositório de Usuários
 *
 * Operações CRUD e consultas de usuários no banco de dados.
 *
 * @package Syntara
 */



class User
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ─── CONSULTAS ───

    /** Busca usuário por ID (sem hash de senha). Retorna array ou null. */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, nome, email, tipo, ativo, criado_em
             FROM usuarios WHERE id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Busca usuário por e-mail (inclui hash da senha). Retorna array ou null. */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    /** Lista todos os usuários, opcionalmente filtrado por tipo. */
    public function findAll(string $type = '', int $limit = 100): array
    {
        if ($type) {
            $stmt = $this->db->prepare(
                "SELECT id, nome, email, tipo, ativo, criado_em
                 FROM usuarios WHERE tipo = ? ORDER BY nome LIMIT ?"
            );
            $stmt->execute([$type, $limit]);
        } else {
            $stmt = $this->db->prepare(
                "SELECT id, nome, email, tipo, ativo, criado_em
                 FROM usuarios ORDER BY nome LIMIT ?"
            );
            $stmt->execute([$limit]);
        }
        return $stmt->fetchAll();
    }

    /** Conta total de usuários, opcionalmente filtrado por tipo. */
    public function countByType(string $type = ''): int
    {
        if ($type) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE tipo = ?");
            $stmt->execute([$type]);
        } else {
            $stmt = $this->db->query("SELECT COUNT(*) FROM usuarios");
        }
        return (int) $stmt->fetchColumn();
    }

    // ─── MUTAÇÕES ───

    /** Cria novo usuário. Retorna ID inserido. */
    public function create(string $nome, string $email, string $hash, string $tipo = 'aluno'): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$nome, $email, $hash, $tipo]);
        return (int) $this->db->lastInsertId();
    }

    /** Atualiza nome e e-mail do usuário. */
    public function updateProfile(int $id, string $nome, string $email): bool
    {
        $stmt = $this->db->prepare("UPDATE usuarios SET nome = ?, email = ? WHERE id = ?");
        return $stmt->execute([$nome, $email, $id]);
    }

    /** Atualiza senha do usuário (hash já deve estar computeado). */
    public function updatePassword(int $id, string $hash): bool
    {
        $stmt = $this->db->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
        return $stmt->execute([$hash, $id]);
    }

    /** Remove usuário permanentemente. */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ─── VALIDAÇÕES ───

    /** Verifica se e-mail já está em uso (excluindo um ID opcional). */
    public function emailExists(string $email, int $excludeId = 0): bool
    {
        $sql    = "SELECT COUNT(*) FROM usuarios WHERE email = ?";
        $params = [$email];

        if ($excludeId > 0) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }
}
