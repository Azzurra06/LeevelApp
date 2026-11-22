<?php
// Suppression d'un compte et des données associées (droit à l'effacement).

/** Supprime les fichiers déposés (sur le disque) pour un ensemble d'élèves ou d'un utilisateur. */
function purge_files(string $where, array $args): void
{
    try {
        foreach (all("SELECT stored FROM student_files WHERE $where", $args) as $f)
            if (preg_match('/^[a-f0-9]{24}\.[a-z]{3,4}$/', $f['stored'])) @unlink(files_dir() . '/' . $f['stored']);
        q("DELETE FROM student_files WHERE $where", $args);
    } catch (PDOException) {}
}

/** Supprime un utilisateur et ses données personnelles propres (messages, journal, photo…). */
function purge_user(int $uid): void
{
    remove_avatar($uid);
    purge_files('user_id=?', [$uid]);
    foreach (['DELETE FROM messages WHERE sender_id=?', 'DELETE FROM progress_entries WHERE user_id=?', 'DELETE FROM chapter_attempts WHERE user_id=?', 'DELETE FROM subject_blockers WHERE user_id=?'] as $sql) {
        try { q($sql, [$uid]); } catch (PDOException) {}
    }
    q('DELETE FROM users WHERE id=?', [$uid]); // cascade : appartenance aux familles, questionnaires remplis
}

/** Supprime une famille entière : dossiers élèves, séances, bilans, fichiers, et les comptes qui n'appartiennent à aucune autre famille. */
function purge_family(int $fid, int $exceptUid = 0): void
{
    $sids = array_column(all('SELECT id FROM students WHERE family_id=?', [$fid]), 'id');
    if ($sids) purge_files('student_id IN (' . implode(',', array_map('intval', $sids)) . ')', []);
    $uids = array_column(all('SELECT user_id FROM family_members WHERE family_id=? AND member_role<>"tutor"', [$fid]), 'user_id');
    q('DELETE FROM families WHERE id=?', [$fid]);
    foreach ($uids as $id) if ((int)$id !== $exceptUid && !one('SELECT 1 FROM family_members WHERE user_id=?', [$id])) purge_user((int)$id);
}

/** Ce qui sera supprimé, pour l'afficher avant confirmation. */
function deletion_scope(array $u): array
{
    if ($u['role'] === 'student') return ['solo' => true, 'families' => []];
    if ($u['role'] === 'parent') {
        $fams = all('SELECT f.id, f.name, (SELECT COUNT(*) FROM family_members m JOIN users x ON x.id=m.user_id WHERE m.family_id=f.id AND x.role="parent" AND x.id<>?) others
                     FROM families f JOIN family_members fm ON fm.family_id=f.id WHERE fm.user_id=?', [$u['id'], $u['id']]);
        return ['solo' => false, 'families' => $fams];
    }
    $otherTutors = (int)(one('SELECT COUNT(*) n FROM users WHERE role="tutor" AND id<>?', [$u['id']])['n'] ?? 0);
    return ['solo' => $otherTutors === 0, 'families' => []];
}

function delete_account(array $u, bool $withFamilies): void
{
    db()->beginTransaction();
    try {
        if ($u['role'] === 'student') {
            $st = one('SELECT id FROM students WHERE user_id=?', [$u['id']]);
            if ($st) {
                try { q('DELETE f FROM session_feedback f JOIN sessions s ON s.id=f.session_id WHERE s.student_id=?', [$st['id']]); } catch (PDOException) {}
                q('UPDATE students SET student_phone=NULL, student_email=NULL WHERE id=?', [$st['id']]);
            }
            purge_user((int)$u['id']);
        } elseif ($u['role'] === 'parent') {
            foreach (deletion_scope($u)['families'] as $f) {
                // sans autre parent, le dossier n'a plus de responsable : il est supprimé
                if ($withFamilies || (int)$f['others'] === 0) purge_family((int)$f['id'], (int)$u['id']);
            }
            purge_user((int)$u['id']);
        } else {
            if (deletion_scope($u)['solo'] && $withFamilies) foreach (all('SELECT id FROM families') as $f) purge_family((int)$f['id'], (int)$u['id']);
            purge_user((int)$u['id']);
        }
        db()->commit();
    } catch (Throwable $e) { db()->rollBack(); throw $e; }
    try { q('INSERT INTO audit_log (user_id, action, detail, ip) VALUES (NULL, "account_deleted", ?, ?)', ['role=' . $u['role'], client_ip()]); } catch (PDOException) {}
}
