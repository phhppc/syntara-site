<?php
/**
 * Syntara v3.0 — Repositório de Aulas
 *
 * @package Syntara
 */


class Lesson
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ─── CONSULTAS ───

    /** Busca aula por ID com nome do curso. */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, c.nome AS curso_nome, c.id AS curso_id
             FROM aulas a
             JOIN cursos c ON a.curso_id = c.id
             WHERE a.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Lista aulas de um curso, ordenadas por data. */
    public function findByCourse(int $courseId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM aulas WHERE curso_id = ? ORDER BY data_aula DESC"
        );
        $stmt->execute([$courseId]);
        return $stmt->fetchAll();
    }

    /** Lista aulas de vários cursos (para dashboard do aluno). */
    public function findByCourses(array $courseIds): array
    {
        if (empty($courseIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
        $stmt = $this->db->prepare(
            "SELECT a.*, c.nome AS curso_nome
             FROM aulas a
             JOIN cursos c ON a.curso_id = c.id
             WHERE a.curso_id IN ($placeholders)
             ORDER BY c.nome, a.data_aula DESC"
        );
        $stmt->execute($courseIds);
        return $stmt->fetchAll();
    }

    /** Conta aulas de um curso (ou todas). */
    public function countByCourse(?int $courseId = null): int
    {
        if ($courseId !== null) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM aulas WHERE curso_id = ?");
            $stmt->execute([$courseId]);
        } else {
            $stmt = $this->db->query("SELECT COUNT(*) FROM aulas");
        }
        return (int) $stmt->fetchColumn();
    }

    // ─── MUTAÇÕES ───

    /** Cria nova aula. Retorna ID. */
    public function create(int $courseId, string $titulo, string $conteudo, string $dataAula = ''): int
    {
        $dataAula = $dataAula ?: date('Y-m-d');
        $stmt = $this->db->prepare(
            "INSERT INTO aulas (curso_id, titulo, conteudo, data_aula) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$courseId, $titulo, $conteudo, $dataAula]);
        return (int) $this->db->lastInsertId();
    }

    /** Atualiza aula. */
    public function update(int $id, string $titulo, string $conteudo, string $dataAula): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE aulas SET titulo = ?, conteudo = ?, data_aula = ? WHERE id = ?"
        );
        return $stmt->execute([$titulo, $conteudo, $dataAula, $id]);
    }

    /** Exclui aula. */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM aulas WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
