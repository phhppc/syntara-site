<?php
/**
 * Syntara v3.0 — Repositório de Cursos
 *
 * @package Syntara
 */

class Course
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ─────────────────────────────
    // CONSULTAS
    // ─────────────────────────────

    /** Busca curso por ID com nome do professor. */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, u.nome AS professor_nome
             FROM cursos c
             JOIN usuarios u ON c.professor_id = u.id
             WHERE c.id = ?"
        );
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Lista cursos (todos ou por professor). */
    public function findAll(?int $professorId = null, int $limit = 100): array
    {
        if ($professorId !== null) {
            $stmt = $this->db->prepare(
                "SELECT c.*, u.nome AS professor_nome
                 FROM cursos c
                 JOIN usuarios u ON c.professor_id = u.id
                 WHERE c.professor_id = ?
                 ORDER BY c.nome
                 LIMIT ?"
            );
            $stmt->execute([$professorId, $limit]);
        } else {
            $stmt = $this->db->prepare(
                "SELECT c.*, u.nome AS professor_nome
                 FROM cursos c
                 JOIN usuarios u ON c.professor_id = u.id
                 WHERE c.ativo = 1
                 ORDER BY c.nome
                 LIMIT ?"
            );
            $stmt->execute([$limit]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Conta cursos ativos. */
    public function count(?int $professorId = null): int
    {
        if ($professorId !== null) {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM cursos WHERE professor_id = ? AND ativo = 1"
            );
            $stmt->execute([$professorId]);
        } else {
            $stmt = $this->db->query(
                "SELECT COUNT(*) FROM cursos WHERE ativo = 1"
            );
        }

        return (int) $stmt->fetchColumn();
    }

    /** Conta alunos matriculados em um curso. */
    public function studentCount(int $courseId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM matriculas
             WHERE curso_id = ? AND status = 'ativo'"
        );
        $stmt->execute([$courseId]);

        return (int) $stmt->fetchColumn();
    }

    /** Conta aulas de um curso. */
    public function lessonCount(int $courseId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM aulas WHERE curso_id = ?"
        );
        $stmt->execute([$courseId]);

        return (int) $stmt->fetchColumn();
    }

    /** Verifica se curso pertence ao professor. */
    public function belongsToProfessor(int $courseId, int $professorId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM cursos WHERE id = ? AND professor_id = ?"
        );
        $stmt->execute([$courseId, $professorId]);

        return (bool) $stmt->fetch();
    }

    // ─────────────────────────────
    // 🔥 NOVO: VERIFICAÇÃO DE ACESSO DO ALUNO
    // ─────────────────────────────

    /** Verifica se aluno está matriculado no curso */
    public function studentEnrolled(int $courseId, int $studentId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT id
             FROM matriculas
             WHERE curso_id = ?
             AND aluno_id = ?
             AND status = 'ativo'
             LIMIT 1"
        );

        $stmt->execute([$courseId, $studentId]);

        return (bool) $stmt->fetch();
    }

    /** Cursos disponíveis para aluno */
    public function availableForStudent(int $studentId): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, u.nome AS professor_nome
             FROM cursos c
             JOIN usuarios u ON c.professor_id = u.id
             WHERE c.ativo = 1
               AND c.id NOT IN (
                   SELECT curso_id
                   FROM matriculas
                   WHERE aluno_id = ? AND status = 'ativo'
               )
             ORDER BY c.nome"
        );

        $stmt->execute([$studentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Cursos matriculados do aluno */
    public function enrolledByStudent(int $studentId): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, m.status AS matricula_status, u.nome AS professor_nome
             FROM cursos c
             JOIN matriculas m ON c.id = m.curso_id
             JOIN usuarios u ON c.professor_id = u.id
             WHERE m.aluno_id = ?
             ORDER BY c.nome"
        );

        $stmt->execute([$studentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ─────────────────────────────
    // MUTAÇÕES
    // ─────────────────────────────

    public function create(string $nome, string $descricao, int $professorId): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO cursos (nome, descricao, professor_id)
             VALUES (?, ?, ?)"
        );

        $stmt->execute([$nome, $descricao, $professorId]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $nome, string $descricao): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE cursos
             SET nome = ?, descricao = ?
             WHERE id = ?"
        );

        return $stmt->execute([$nome, $descricao, $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM cursos WHERE id = ?"
        );

        return $stmt->execute([$id]);
    }
}