<?php
/**
 * Syntara v3.0 — Repositório de Avaliações (Evaluation)
 *
 * Avaliação = aluno avalia professor (nota + comentário).
 * Diferente de Feedback (professor avalia aluno).
 *
 * @package Syntara
 */



class Evaluation
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ─── CONSULTAS ───

    /** Busca avaliação por ID. */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT av.*, a.nome AS aluno_nome, p.nome AS professor_nome, c.nome AS curso_nome
             FROM avaliacoes av
             JOIN usuarios a ON av.aluno_id = a.id
             JOIN usuarios p ON av.professor_id = p.id
             JOIN cursos c ON av.curso_id = c.id
             WHERE av.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Busca avaliação específica de aluno+professor+curso. */
    public function findEvaluation(int $studentId, int $professorId, int $courseId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM avaliacoes WHERE aluno_id = ? AND professor_id = ? AND curso_id = ?"
        );
        $stmt->execute([$studentId, $professorId, $courseId]);
        return $stmt->fetch() ?: null;
    }

    /** Avaliações feitas por um aluno. */
    public function byStudent(int $studentId): array
    {
        $stmt = $this->db->prepare(
            "SELECT av.*, p.nome AS professor_nome, c.nome AS curso_nome
             FROM avaliacoes av
             JOIN usuarios p ON av.professor_id = p.id
             JOIN cursos c ON av.curso_id = c.id
             WHERE av.aluno_id = ?
             ORDER BY av.criado_em DESC"
        );
        $stmt->execute([$studentId]);
        return $stmt->fetchAll();
    }

    /** Avaliações recebidas por um professor. */
    public function receivedByProfessor(int $professorId): array
    {
        $stmt = $this->db->prepare(
            "SELECT av.*, a.nome AS aluno_nome, c.nome AS curso_nome
             FROM avaliacoes av
             JOIN usuarios a ON av.aluno_id = a.id
             JOIN cursos c ON av.curso_id = c.id
             WHERE av.professor_id = ?
             ORDER BY av.criado_em DESC"
        );
        $stmt->execute([$professorId]);
        return $stmt->fetchAll();
    }

    /** Conta avaliações de um aluno. */
    public function countByStudent(int $studentId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM avaliacoes WHERE aluno_id = ?");
        $stmt->execute([$studentId]);
        return (int) $stmt->fetchColumn();
    }

    /** Calcula média geral de um professor. */
    public function averageForProfessor(int $professorId): float
    {
        $stmt = $this->db->prepare(
            "SELECT AVG(nota) FROM avaliacoes WHERE professor_id = ?"
        );
        $stmt->execute([$professorId]);
        return round((float) ($stmt->fetchColumn() ?: 0), 1);
    }

    /** Calcula média por curso de um professor. */
    public function averageByCourse(int $professorId): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.id, c.nome, AVG(av.nota) AS media, COUNT(av.id) AS total
             FROM avaliacoes av
             JOIN cursos c ON av.curso_id = c.id
             WHERE av.professor_id = ?
             GROUP BY c.id, c.nome
             ORDER BY c.nome"
        );
        $stmt->execute([$professorId]);
        return $stmt->fetchAll();
    }

    /** Retorna professores+pos em um curso que o aluno ainda não avaliou. */
    public function pendingForStudent(int $studentId, int $courseId): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.professor_id, u.nome AS professor_nome
             FROM cursos c
             JOIN usuarios u ON c.professor_id = u.id
             WHERE c.id = ?
               AND c.professor_id NOT IN (
                   SELECT professor_id FROM avaliacoes WHERE aluno_id = ? AND curso_id = ?
               )"
        );
        $stmt->execute([$courseId, $studentId, $courseId]);
        return $stmt->fetchAll();
    }

    // ─── MUTAÇÕES ───

    /** Cria ou atualiza avaliação (UPSERT). Retorna ID. */
    public function save(
        int $studentId,
        int $professorId,
        int $courseId,
        int $nota,
        string $comentario
    ): int {
        $existing = $this->findEvaluation($studentId, $professorId, $courseId);

        if ($existing) {
            $stmt = $this->db->prepare(
                "UPDATE avaliacoes SET nota = ?, comentario = ? WHERE id = ?"
            );
            $stmt->execute([$nota, $comentario, $existing['id']]);
            return (int) $existing['id'];
        }

        $stmt = $this->db->prepare(
            "INSERT INTO avaliacoes (aluno_id, professor_id, curso_id, nota, comentario)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$studentId, $professorId, $courseId, $nota, $comentario]);
        return (int) $this->db->lastInsertId();
    }

    /** Exclui avaliação. */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM avaliacoes WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /** Exclui todas as avaliações de um aluno (usado ao deletar usuário). */
    public function deleteAllByStudent(int $studentId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM avaliacoes WHERE aluno_id = ?");
        return $stmt->execute([$studentId]);
    }
}
