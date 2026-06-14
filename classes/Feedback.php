<?php
/**
 * Syntara v3.0 — Repositório de Feedbacks
 *
 * Feedback = avaliação qualitativa de professor para aluno.
 * Diferente de Evaluation (aluno avalia professor).
 *
 * @package Syntara
 */



class Feedback
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ─── CONSULTAS ───

    /** Busca feedback por ID com nomes relacionados. */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT f.*, a.nome AS aluno_nome, p.nome AS professor_nome, c.nome AS curso_nome
             FROM feedbacks f
             JOIN usuarios a ON f.aluno_id = a.id
             JOIN usuarios p ON f.professor_id = p.id
             JOIN cursos c ON f.curso_id = c.id
             WHERE f.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Feedbacks recebidos por um aluno. */
    public function receivedByStudent(int $studentId): array
    {
        $stmt = $this->db->prepare(
            "SELECT f.*, p.nome AS professor_nome, c.nome AS curso_nome
             FROM feedbacks f
             JOIN usuarios p ON f.professor_id = p.id
             JOIN cursos c ON f.curso_id = c.id
             WHERE f.aluno_id = ?
             ORDER BY f.criado_em DESC"
        );
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }

    /** Feedbacks enviados por um professor. */
    public function givenByProfessor(int $professorId): array
    {
        $stmt = $this->db->prepare(
            "SELECT f.*, a.nome AS aluno_nome, c.nome AS curso_nome
             FROM feedbacks f
             JOIN usuarios a ON f.aluno_id = a.id
             JOIN cursos c ON f.curso_id = c.id
             WHERE f.professor_id = ?
             ORDER BY f.criado_em DESC"
        );
        $stmt->execute([$professorId]);
        return $stmt->fetchAll();
    }

    /** Conta feedbacks de um aluno. */
    public function countByStudent(int $studentId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM feedbacks WHERE aluno_id = ?");
        $stmt->execute([$studentId]);
        return (int) $stmt->fetchColumn();
    }

    /** Média das notas de feedbacks de um aluno. */
    public function avgByStudent(int $studentId): float
    {
        $stmt = $this->db->prepare("SELECT AVG(nota) FROM feedbacks WHERE aluno_id = ?");
        $stmt->execute([$studentId]);
        return (float) ($stmt->fetchColumn() ?? 0.0);
    }

    // ─── MUTAÇÕES ───

    /** Cria novo feedback. Retorna ID. */
    public function create(
        int $studentId,
        int $professorId,
        int $courseId,
        string $tipo,
        int $nota,
        string $mensagem
    ): int {
        $stmt = $this->db->prepare(
            "INSERT INTO feedbacks (aluno_id, professor_id, curso_id, tipo, nota, mensagem)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$studentId, $professorId, $courseId, $tipo, $nota, $mensagem]);
        return (int) $this->db->lastInsertId();
    }

    /** Exclui feedback. */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM feedbacks WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ─── VALIDAÇÕES ───

    /** Verifica se já existe feedback deste professor para aluno+curso. */
    public function exists(int $studentId, int $professorId, int $courseId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM feedbacks WHERE aluno_id = ? AND professor_id = ? AND curso_id = ?"
        );
        $stmt->execute([$studentId, $professorId, $courseId]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
