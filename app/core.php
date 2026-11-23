<?php
declare(strict_types=1);

const AXES = [
    'comprehension' => 'Compréhension et maîtrise des notions',
    'methodo'       => 'Méthodologie et organisation',
    'motivation'    => 'Motivation et engagement',
    'autonomie'     => 'Autonomie',
    'evaluations'   => 'Préparation des évaluations',
];
const GOAL_STATUS = ['todo' => 'À commencer', 'in_progress' => 'En cours', 'acquired' => 'Acquis', 'rework' => 'À retravailler'];
const SESSION_STATUS = ['planned' => 'Prévue', 'done' => 'Réalisée', 'postponed' => 'Reportée', 'cancelled' => 'Annulée'];
const MODES = ['presentiel' => 'Présentiel', 'visio' => 'Visio'];
const FEELINGS = ['understood' => "J'ai compris", 'practice' => 'Je dois encore m\'entraîner', 'not_understood' => "Je n'ai pas compris"];
const CLASSES = ['CP' => 'CP', 'CE1' => 'CE1', 'CE2' => 'CE2', 'CM1' => 'CM1', 'CM2' => 'CM2', '6e' => '6e', '5e' => '5e', '4e' => '4e', '3e' => '3e',
    '2nde' => 'Seconde', '1re' => 'Première', 'Tle' => 'Terminale', 'Autre' => 'Autre'];
const STUDENT_INTERESTS = ['sport' => 'Sport', 'musique' => 'Musique', 'jeux' => 'Jeux vidéo', 'lecture' => 'Lecture / mangas', 'arts' => 'Dessin / arts', 'cinema' => 'Cinéma / séries',
    'nature' => 'Nature / animaux', 'sciences' => 'Sciences / technologie', 'numerique' => 'Informatique / numérique', 'cuisine' => 'Cuisine', 'mode' => 'Mode / beauté', 'voyages' => 'Voyages / langues', 'autre' => 'Autre'];
const MEMORY_TYPES = ['visual' => 'Voir (schémas, couleurs, images)', 'auditory' => 'Entendre (écouter, répéter à voix haute)', 'kinesthetic' => 'Faire (écrire, manipuler, bouger)', 'not_sure' => 'Je ne sais pas encore'];
const CHILD_PLACEHOLDER = 'Élève (à compléter)';
/** Autorisations que le parent accorde à l'élève : colonne => [libellé, précision]. */
const CHILD_PERMS = [
    'perm_edit_photo' => ['Changer sa photo de profil', ''],
    'perm_edit_contacts' => ['Modifier ses informations et ses coordonnées', ''],
    'perm_link_email' => ['Relier son adresse e-mail à son compte', 'pour recevoir les notifications et récupérer son mot de passe'],
    'perm_help_requests' => ['Signaler qu\'il bloque et demander de l\'aide', ''],
];
/** Enregistre les autorisations cochées (formulaire) pour un élève. */
function save_child_perms(int $sid): void
{
    $cols = array_keys(CHILD_PERMS);
    try { q('UPDATE students SET ' . implode('=?, ', $cols) . '=? WHERE id=?', array_merge(array_map(fn($c) => (int)!empty($_POST[$c]), $cols), [$sid])); }
    catch (PDOException) { q('UPDATE students SET perm_edit_contacts=?, perm_edit_photo=?, perm_help_requests=? WHERE id=?', [(int)!empty($_POST['perm_edit_contacts']), (int)!empty($_POST['perm_edit_photo']), (int)!empty($_POST['perm_help_requests']), $sid]); }
}
/** Prénom de l'enfant à afficher au parent : « votre enfant » tant qu'il n'est pas renseigné. */
function kid(?string $firstName, bool $capital = false): string
{
    $n = trim((string)$firstName);
    if ($n === '' || $n === CHILD_PLACEHOLDER) return $capital ? 'Votre enfant' : 'votre enfant';
    return $n;
}
/** Cycle d'une classe : primaire, college, lycee (null si inconnu). */
function grade_cycle(?string $g): ?string
{
    return match (true) {
        in_array($g, ['CP', 'CE1', 'CE2', 'CM1', 'CM2'], true) => 'primaire',
        in_array($g, ['6e', '5e', '4e', '3e'], true) => 'college',
        in_array($g, ['2nde', '1re', 'Tle'], true) => 'lycee',
        default => null,
    };
}
/** Liste déroulante des classes. */
function grade_select(string $name, ?string $cur, string $extra = ''): string
{
    $o = '';
    $known = isset(CLASSES[$cur ?? '']);
    foreach (CLASSES as $k => $l) $o .= '<option value="' . e($k) . '"' . ($cur === $k ? ' selected' : '') . '>' . e($l) . '</option>';
    if (!$known && $cur) $o = '<option value="' . e($cur) . '" selected>' . e($cur) . '</option>' . $o;
    return '<select name="' . e($name) . '" ' . $extra . '>' . $o . '</select>';
}
const ROLE_LABELS = ['tutor' => 'Tutrice', 'parent' => 'Parent', 'student' => 'Élève'];

function config(?string $key = null): mixed
{
    static $cfg = null;
    if ($cfg === null) {
        $file = APP_ROOT . '/config/config.php';
        if (!is_file($file)) {
            http_response_code(500);
            exit('Configuration manquante : copiez config/config.example.php en config/config.php.');
        }
        $cfg = require $file;
    }
    return $key === null ? $cfg : ($cfg[$key] ?? null);
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = config('db');
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], $c['port'] ?? 3306, $c['name']);
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET time_zone = '" . (new DateTime())->format('P') . "'");
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}
function one(string $sql, array $p = []): ?array { $r = q($sql, $p)->fetch(); return $r ?: null; }
function all(string $sql, array $p = []): array { return q($sql, $p)->fetchAll(); }

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443);
}

function boot(): void
{
    date_default_timezone_set(config('timezone') ?: 'Europe/Paris');
    $prod = config('env') === 'prod';
    if ($prod && !is_https() && PHP_SAPI !== 'cli') {
        header('Location: https://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
        exit;
    }
    ini_set('display_errors', $prod ? '0' : '1');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; style-src 'self'; script-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'self'");
    if ($prod) header('Strict-Transport-Security: max-age=31536000');
    header('Cache-Control: no-store');
    session_name(config('session_name') ?: 'leevel_sid');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => $prod]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
    // Expiration d'inactivité : 2 h (30 min en passation)
    $idle = isset($_SESSION['passation']) ? 1800 : 7200;
    $expired = isset($_SESSION['last']) && time() - $_SESSION['last'] > $idle;
    if ($expired) { session_unset(); session_regenerate_id(true); }
    // Mode app (écran d'accueil) : l'appareil reste connecté
    if (PHP_SAPI !== 'cli') {
        if (!isset($_SESSION['uid']) && !isset($_SESSION['passation']) && !empty($_COOKIE[REMEMBER_COOKIE])) { if (remember_restore()) $expired = false; }
        elseif (isset($_SESSION['uid']) && is_app_mode() && empty($_COOKIE[REMEMBER_COOKIE])) remember_issue((int)$_SESSION['uid']);
    }
    if ($expired) flash('Session expirée, merci de vous reconnecter.');
    $_SESSION['last'] = time();
}

// ---------- Connexion persistante (mode app) ----------
const REMEMBER_COOKIE = 'leevel_rm';
const REMEMBER_DAYS = 180;
/** L'app est ouverte depuis l'écran d'accueil (cookie posé par app.js). */
function is_app_mode(): bool { return ($_COOKIE['leevel_app'] ?? '') === '1'; }
function remember_cookie(string $v, int $expires): void
{
    setcookie(REMEMBER_COOKIE, $v, ['expires' => $expires, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => config('env') === 'prod']);
    if ($v === '') unset($_COOKIE[REMEMBER_COOKIE]); else $_COOKIE[REMEMBER_COOKIE] = $v;
}
function remember_issue(int $uid): void
{
    try {
        $sel = bin2hex(random_bytes(12)); $val = bin2hex(random_bytes(32));
        q('INSERT INTO remember_tokens (user_id, selector, validator_hash, ua, expires_at) VALUES (?,?,?,?, NOW() + INTERVAL ' . REMEMBER_DAYS . ' DAY)',
          [$uid, $sel, hash('sha256', $val), mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 160)]);
        remember_cookie($sel . ':' . $val, time() + REMEMBER_DAYS * 86400);
    } catch (PDOException) {}
}
function remember_parse(): ?array
{
    $c = (string)($_COOKIE[REMEMBER_COOKIE] ?? '');
    return preg_match('/^([0-9a-f]{24}):([0-9a-f]{64})$/', $c, $m) ? [$m[1], $m[2]] : null;
}
/** Reconnecte l'appareil à partir de son jeton ; jeton glissant (prolongé à chaque usage). */
function remember_restore(): bool
{
    $p = remember_parse();
    try { $t = $p ? one('SELECT t.* FROM remember_tokens t JOIN users u ON u.id=t.user_id WHERE t.selector=? AND t.expires_at > NOW() AND u.is_active=1', [$p[0]]) : null; }
    catch (PDOException) { return false; }
    if (!$t || !hash_equals($t['validator_hash'], hash('sha256', $p[1]))) { remember_cookie('', time() - 3600); return false; }
    session_regenerate_id(true);
    $_SESSION = ['uid' => (int)$t['user_id'], 'last' => time(), 'accounts' => [(int)$t['user_id']]];
    q('UPDATE remember_tokens SET last_used_at=NOW(), expires_at=NOW() + INTERVAL ' . REMEMBER_DAYS . ' DAY WHERE id=?', [$t['id']]);
    remember_cookie((string)$_COOKIE[REMEMBER_COOKIE], time() + REMEMBER_DAYS * 86400);
    return true;
}
/** Oublie l'appareil courant (déconnexion, changement de compte). */
function remember_forget(): void
{
    if (empty($_COOKIE[REMEMBER_COOKIE])) return;
    if ($p = remember_parse()) { try { q('DELETE FROM remember_tokens WHERE selector=?', [$p[0]]); } catch (PDOException) {} }
    remember_cookie('', time() - 3600);
}
/** Déconnecte tous les appareils d'un utilisateur (changement de mot de passe, suppression). */
function remember_forget_user(int $uid): void
{
    try { q('DELETE FROM remember_tokens WHERE user_id=?', [$uid]); } catch (PDOException) {}
    remember_cookie('', time() - 3600);
}

// ---------- Helpers vue ----------
function e(mixed $v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $route = '', array $params = []): string
{
    $base = rtrim((string)config('base_url'), '/');
    $qs = $route !== '' ? array_merge(['r' => $route], $params) : $params;
    return $base . '/index.php' . ($qs ? '?' . http_build_query($qs) : '');
}
function asset(string $p): string
{
    $file = (is_dir(APP_ROOT . '/public') ? APP_ROOT . '/public' : APP_ROOT) . '/assets/' . $p;
    return rtrim((string)config('base_url'), '/') . '/assets/' . $p . (is_file($file) ? '?v=' . filemtime($file) : '');
}
function icon(string $n, string $cls = ''): string
{
    return '<svg class="ic ' . e($cls) . '" aria-hidden="true"><use href="' . e(asset('icons.svg')) . '#i-' . e($n) . '"></use></svg>';
}
function redirect(string $route, array $params = []): never { header('Location: ' . url($route, $params)); exit; }
function flash(?string $msg = null, string $type = 'ok'): ?array
{
    if ($msg !== null) { $_SESSION['flash'][] = [$type, $msg]; return null; }
    $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f;
}
function render(string $view, array $vars = [], string $layout = 'layout'): never
{
    extract($vars, EXTR_SKIP);
    ob_start();
    require APP_ROOT . "/app/views/$view.php";
    $content = ob_get_clean();
    require APP_ROOT . "/app/views/$layout.php";
    exit;
}
function fdate(?string $d, string $fmt = 'd/m/Y'): string { return $d ? date($fmt, strtotime($d)) : '-'; }
function fdatetime(?string $d): string
{
    if (!$d) return '-';
    $days = ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.'];
    $t = strtotime($d);
    return $days[(int)date('w', $t)] . ' ' . date('d/m', $t) . ' · ' . date('H\hi', $t);
}
/** Numéro de chaque séance d'un élève (ordre chronologique, séances annulées exclues) : [id => n]. */
function session_numbers(int $studentId): array
{
    static $cache = [];
    if (!isset($cache[$studentId])) {
        $ids = array_column(all('SELECT id FROM sessions WHERE student_id=? AND status<>"cancelled" ORDER BY starts_at, id', [$studentId]), 'id');
        $cache[$studentId] = $ids ? array_combine($ids, range(1, count($ids))) : [];
    }
    return $cache[$studentId];
}
/** Titre sans ancien préfixe « S1 - » (numéro de semaine). */
function session_base_title(string $t): string { return preg_replace('/^S\d+\s*[-—–]\s*/u', '', $t) ?? $t; }
/** Libellé affiché : « S3 - Méthodologie et organisation ». */
function session_label(array $se): string
{
    $n = session_numbers((int)$se['student_id'])[$se['id']] ?? null;
    $t = session_base_title((string)$se['title']);
    return $n ? "S$n - $t" : $t;
}
function fdur(int $min): string { return $min < 60 ? $min . ' min' : intdiv($min, 60) . 'h' . ($min % 60 ? sprintf('%02d', $min % 60) : ''); }

// ---------- CSRF ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function check_csrf(): void
{
    $t = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($t) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $t)) {
        http_response_code(403);
        exit('Requête refusée (jeton de sécurité invalide). Rechargez la page.');
    }
}

// ---------- Audit ----------
function client_ip(): string { return substr((string)($_SERVER['REMOTE_ADDR'] ?? 'cli'), 0, 45); }
function audit(string $action, ?string $detail = null, ?int $familyId = null): void
{
    q('INSERT INTO audit_log (user_id, family_id, action, detail, ip) VALUES (?,?,?,?,?)',
        [current_user()['id'] ?? null, $familyId, $action, $detail ? mb_substr($detail, 0, 255) : null, client_ip()]);
}

// ---------- Authentification ----------
const MAX_FAIL_USER = 5, MAX_FAIL_IP = 20, FAIL_WINDOW_MIN = 15;

function login_blocked(string $username): bool
{
    $since = date('Y-m-d H:i:s', time() - FAIL_WINDOW_MIN * 60);
    $u = (int)one('SELECT COUNT(*) n FROM login_attempts WHERE username=? AND attempted_at > ?', [$username, $since])['n'];
    $i = (int)one('SELECT COUNT(*) n FROM login_attempts WHERE ip=? AND attempted_at > ?', [client_ip(), $since])['n'];
    return $u >= MAX_FAIL_USER || $i >= MAX_FAIL_IP;
}

function attempt_login(string $username, string $password): bool|string
{
    $username = mb_strtolower(trim($username));
    if ($username === '' || $password === '') return 'Identifiant et mot de passe requis.';
    if (login_blocked($username)) return 'Trop de tentatives. Réessayez dans ' . FAIL_WINDOW_MIN . ' minutes.';
    $u = one('SELECT * FROM users WHERE username=? AND is_active=1', [$username]);
    // Vérification même si l'utilisateur n'existe pas (temps constant approximatif)
    $hash = $u['password_hash'] ?? password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
    if (!password_verify($password, $hash) || !$u) {
        q('INSERT INTO login_attempts (ip, username) VALUES (?,?)', [client_ip(), $username]);
        q('INSERT INTO audit_log (action, detail, ip) VALUES (?,?,?)', ['login_failed', $username, client_ip()]);
        return 'Identifiant ou mot de passe incorrect.';
    }
    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
        q('UPDATE users SET password_hash=? WHERE id=?', [password_hash($password, PASSWORD_DEFAULT), $u['id']]);
    }
    q('DELETE FROM login_attempts WHERE username=?', [$username]);
    remember_forget();
    session_regenerate_id(true);
    // comptes déjà connectés dans ce navigateur : conservés pour passer de l'un à l'autre sans se reconnecter
    $accounts = array_values(array_unique(array_map('intval', array_merge($_SESSION['accounts'] ?? [], isset($_SESSION['uid']) && !in_passation() ? [(int)$_SESSION['uid']] : []))));
    $accounts = array_values(array_unique(array_merge($accounts, [(int)$u['id']])));
    $_SESSION = ['uid' => (int)$u['id'], 'last' => time(), 'accounts' => array_slice($accounts, -5)];
    q('UPDATE users SET last_login_at=NOW() WHERE id=?', [$u['id']]);
    audit('login');
    return true;
}

/** Autres comptes connectés dans ce navigateur (hors compte courant). */
function linked_accounts(): array
{
    $cur = (int)($_SESSION['uid'] ?? 0);
    $ids = array_values(array_filter(array_map('intval', $_SESSION['accounts'] ?? []), fn($i) => $i !== $cur));
    if (!$ids || in_passation()) return [];
    try { return all('SELECT id, username, display_name, role, avatar FROM users WHERE is_active=1 AND id IN (' . implode(',', $ids) . ') ORDER BY FIELD(id,' . implode(',', $ids) . ')'); }
    catch (PDOException) { return []; }
}
/** Passe sur un autre compte déjà connecté dans ce navigateur. */
function switch_account(int $uid): bool
{
    if (in_passation() || !in_array($uid, array_map('intval', $_SESSION['accounts'] ?? []), true)) return false;
    if (!one('SELECT 1 FROM users WHERE id=? AND is_active=1', [$uid])) return false;
    remember_forget();
    session_regenerate_id(true);
    $keep = ['accounts' => $_SESSION['accounts']];
    $_SESSION = ['uid' => $uid, 'last' => time()] + $keep;
    return true;
}
function logout(): void
{
    remember_forget();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 42000, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Lax']);
    }
    session_destroy();
    session_start();
    session_regenerate_id(true);
}

/** Utilisateur courant (connexion normale OU passation). */
function current_user(): ?array
{
    static $cache = [];
    $uid = $_SESSION['uid'] ?? ($_SESSION['passation']['user_id'] ?? null);
    if (!$uid) return null;
    if (!isset($cache[$uid])) {
        try { $cache[$uid] = one('SELECT id, username, display_name, role, phone, email, avatar FROM users WHERE id=? AND is_active=1', [$uid]); }
        catch (PDOException) { $cache[$uid] = one('SELECT id, username, display_name, role FROM users WHERE id=? AND is_active=1', [$uid]); }
    }
    return $cache[$uid];
}
function in_passation(): bool { return isset($_SESSION['passation']) && !isset($_SESSION['uid']); }

function require_login(?string $role = null): array
{
    if (in_passation()) { http_response_code(403); exit('Accès limité au questionnaire en cours.'); }
    $u = current_user();
    if (!$u) redirect('login');
    if ($role && $u['role'] !== $role) { http_response_code(403); render('error', ['msg' => "Vous n'avez pas accès à cette page."]); }
    return $u;
}

// ---------- Contrôle d'accès aux dossiers ----------
/** Retourne l'élève si l'utilisateur appartient à sa famille ; sinon 403. Un élève n'accède qu'à lui-même. */
function student_for(array $user, int $studentId): array
{
    $s = one('SELECT s.*, f.name family_name, f.guardian_consent_at, f.is_demo FROM students s
              JOIN families f ON f.id=s.family_id
              JOIN family_members fm ON fm.family_id=s.family_id AND fm.user_id=?
              WHERE s.id=?', [$user['id'], $studentId]);
    if (!$s || ($user['role'] === 'student' && (int)$s['user_id'] !== (int)$user['id'])) {
        http_response_code(403);
        audit('access_denied', "student=$studentId");
        render('error', ['msg' => "Ce dossier n'est pas accessible avec votre compte."]);
    }
    return $s;
}
/** Élèves accessibles pour un utilisateur. */
function students_of(array $user): array
{
    $extra = $user['role'] === 'student' ? ' AND s.user_id = ' . (int)$user['id'] : '';
    return all("SELECT s.*, f.name family_name, f.is_demo, f.guardian_consent_at FROM students s
                JOIN families f ON f.id=s.family_id
                JOIN family_members fm ON fm.family_id=s.family_id AND fm.user_id=?
                WHERE 1=1 $extra ORDER BY f.is_demo, s.first_name", [$user['id']]);
}
/** Élève demandé (?sid=) ou premier accessible. */
function pick_student(array $user): ?array
{
    $sid = (int)($_GET['sid'] ?? $_POST['sid'] ?? 0);
    if ($sid) { $s = student_for($user, $sid); $_SESSION['current_sid'] = (int)$s['id']; return $s; }
    $list = students_of($user);
    $remembered = (int)($_SESSION['current_sid'] ?? 0);
    foreach ($list as $x) if ((int)$x['id'] === $remembered) return student_for($user, $remembered);
    return $list[0] ?? null;
}
function family_users(int $familyId): array
{
    return all('SELECT u.id, u.username, u.display_name, u.role, u.is_active, u.last_login_at FROM users u
                JOIN family_members fm ON fm.user_id=u.id WHERE fm.family_id=? ORDER BY FIELD(u.role,"tutor","parent","student")', [$familyId]);
}

function post_str(string $k, int $max = 2000): string { return mb_substr(trim((string)($_POST[$k] ?? '')), 0, $max); }
function post_int(string $k): int { return (int)($_POST[$k] ?? 0); }
function post_enum(string $k, array $allowed, string $default): string { $v = (string)($_POST[$k] ?? ''); return array_key_exists($v, $allowed) ? $v : $default; }

function validate_password(string $p): ?string
{
    if (mb_strlen($p) < 10) return 'Le mot de passe doit contenir au moins 10 caractères.';
    if (!preg_match('/[A-Za-z]/', $p) || !preg_match('/\d/', $p)) return 'Le mot de passe doit contenir des lettres et des chiffres.';
    return null;
}
function validate_username(string $u): ?string
{
    return preg_match('/^[a-z0-9._-]{3,60}$/', $u) ? null : "Identifiant : 3 à 60 caractères (minuscules, chiffres, . _ -).";
}
