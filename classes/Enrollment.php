<?php
/**
 * Syntara v3.0 — Repositório de Matrículas (Enrollment)
 *
 * Gerencia matrículas de alunos em cursos.
 *
 * @package Syntara
 */



class Enrollment
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ─── CONSULTAS ───

    /** Verifica se aluno está matriculado (e ativo) em um curso. */
    public function isActive(int $studentId, int $courseId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM matriculas WHERE aluno_id = ? AND curso_id = ? AND status = 'ativo'"
        );
        $stmt->execute([$studentId, $courseId]);
        return (bool) $stmt->fetch();
    }

    /** Busca matrícula específica. */
    public function find(int $studentId, int $courseId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM matriculas WHERE aluno_id = ? AND curso_id = ?"
        );
        $stmt->execute([$studentId, $courseId]);
        return $stmt->fetch() ?: null;
    }

    /** Lista IDs de cursos em que o aluno está matriculado (ativo). */
    public function activeCourseIds(int $studentId): array
    {
        $stmt = $this->db->prepare(
            "SELECT curso_id FROM matriculas WHERE aluno_id = ? AND status = 'ativo'"
        );
        $stmt->execute([$studentId]);
        return array_column($stmt->fetchAll(), 'curso_id');
    }

    /** Lista alunos matriculados em um curso. */
    public function studentsInCourse(int $courseId, string $status = 'ativo'): array
    {
        $stmt = $this->db->prepare(
            "SELECT u.id, u.nome, u.email, m.criado_em AS matriculado_em
             FROM usuarios u
             JOIN matriculas m ON u.id = m.aluno_id
             WHERE m.curso_id = ? AND m.status = ?
             ORDER BY u.nome"
        );
        $stmt->execute([$courseId, $status]);
        return $stmt->fetchAll();
    }

    /** Conta alunos matriculados em um curso. */
    public function countStudents(int $courseId, string $status = 'ativo'): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM matriculas WHERE curso_id = ? AND status = ?"
        );
        $stmt->execute([$courseId, $status]);
        return (int) $stmt->fetchColumn();
    }

    /** Conta cursos em que o aluno está matriculado. */
    public function countByStudent(int $studentId, string $status = 'ativo'): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM matriculas WHERE aluno_id = ? AND status = ?"
        );
        $stmt->execute([$studentId, $status]);
        return (int) $stmt->fetchColumn();
    }

    // ─── MUTAÇÕES ───

    /** Matricula aluno em curso. Retorna ID ou 0 se já matriculado. */
    public function enroll(int $studentId, int $courseId): int
    {
        if ($this->isActive($studentId, $courseId)) {
            return 0;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO matriculas (aluno_id, curso_id, status) VALUES (?, ?, 'ativo')"
        );
        $stmt->execute([$studentId, $courseId]);
        return (int) $this->db->lastInsertId();
    }

    /** Cancela matrícula (muda status para inativo). */
    public function cancel(int $studentId, int $courseId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE matriculas SET status = 'inativo' WHERE aluno_id = ? AND curso_id = ?"
        );
        return $stmt->execute([$studentId, $courseId]);
    }

    /** Remove matrícula permanentemente. */
    public function delete(int $studentId, int $courseId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM matriculas WHERE aluno_id = ? AND curso_id = ?"
        );
        return $stmt->execute([$studentId, $courseId]);
    }

    /** Remove todas as matrículas de um aluno (usado ao deletar usuário). */
    public function deleteAllByStudent(int $studentId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM matriculas WHERE aluno_id = ?");
        return $stmt->execute([$studentId]);
    }
}
