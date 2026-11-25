<?php
declare(strict_types=1);
// Compétences transversales (méthodologie, organisation, confiance en soi…) travaillées à côté des matières scolaires.

/** Catalogue : clé => [libellé, axe d'analyse lié, emoji]. */
const SKILLS = [
    'methodologie' => ['Méthodologie', 'methodo', '🧭'],
    'organisation' => ['Organisation', 'methodo', '🗓️'],
    'memorisation' => ['Mémorisation', 'comprehension', '🧠'],
    'concentration' => ['Concentration', 'autonomie', '🎯'],
    'autonomie' => ['Autonomie', 'autonomie', '🚀'],
    'confiance' => ['Confiance en soi', 'motivation', '💪'],
    'motivation' => ['Motivation', 'motivation', '🔥'],
    'stress' => ['Gestion du stress', 'evaluations', '🌿'],
];
const SKILL_LEVELS = [1 => 'À construire', 2 => 'Fragile', 3 => 'En progrès', 4 => 'Solide', 5 => 'Acquis'];

function skills_available(): bool
{
    static $ok = null;
    if ($ok === null) { try { db()->query('SELECT 1 FROM student_skills LIMIT 1'); $ok = true; } catch (PDOException) { $ok = false; } }
    return $ok;
}
function skill_meta(array $sk): array
{
    $m = SKILLS[$sk['skill_key'] ?? ''] ?? null;
    return ['label' => $sk['name'], 'axis' => $m[1] ?? null, 'emoji' => $m[2] ?? '✨'];
}
/** Niveau de départ suggéré (1 à 5) d'après le score de besoin de l'analyse (0 = faible besoin, 100 = fort besoin). */
function skill_suggested_level(?array $an, ?string $axis): ?int
{
    $score = $axis ? ($an['scores'][$axis]['score'] ?? null) : null;
    return $score === null ? null : max(1, min(5, 5 - (int)round((float)$score / 25)));
}
/** Compétences d'un élève, avec leurs pratiques, objectifs liés et observations. */
function skills_of(int $sid, bool $withDetails = true): array
{
    if (!skills_available()) return [];
    $out = all('SELECT * FROM student_skills WHERE student_id=? ORDER BY position, id', [$sid]);
    if (!$withDetails) return $out;
    foreach ($out as &$sk) {
        $meta = skill_meta($sk); $sk += $meta;
        $sk['practices'] = all('SELECT * FROM skill_practices WHERE skill_id=? ORDER BY done DESC, id', [$sk['id']]);
        $sk['goals'] = $meta['axis'] ? all('SELECT * FROM goals WHERE student_id=? AND category=? ORDER BY FIELD(status,"in_progress","rework","todo","acquired"), id', [$sid, $meta['axis']]) : [];
        $sk['obs'] = all('SELECT * FROM tutor_observations WHERE student_id=? AND (skill_id=? ' . ($meta['axis'] ? 'OR (skill_id IS NULL AND axis=?)' : '') . ') ORDER BY id DESC LIMIT 10', $meta['axis'] ? [$sid, $sk['id'], $meta['axis']] : [$sid, $sk['id']]);
    }
    return $out;
}
/** Méthodes et supports proposés pour une compétence (repris de l'analyse pédagogique). */
function skill_practice_suggestions(array $sk): array
{
    $axis = $sk['axis'] ?? null; $tpl = $axis ? (axis_templates()[$axis] ?? null) : null;
    if (!$tpl) return [];
    $have = array_map(fn($p) => mb_strtolower($p['label']), $sk['practices'] ?? []);
    return array_values(array_filter(array_unique(array_merge($tpl['methods'], $tpl['supports'])), fn($x) => !in_array(mb_strtolower($x), $have, true)));
}
/** Barre de niveau « départ → actuel » (HTML). */
function skill_level_html(array $sk, bool $kid = false): string
{
    $start = $sk['start_level'] !== null ? (int)$sk['start_level'] : null; $lvl = $sk['level'] !== null ? (int)$sk['level'] : null;
    $h = '<div class="sk-level" title="' . e($lvl ? SKILL_LEVELS[$lvl] : 'Niveau à évaluer') . '">';
    for ($i = 1; $i <= 5; $i++) $h .= '<span class="sk-dot' . ($lvl && $i <= $lvl ? ' on' : '') . ($start && $i === $start ? ' start' : '') . '"></span>';
    $h .= '</div><span class="sk-level-txt">' . ($lvl ? e(SKILL_LEVELS[$lvl]) : '<span class="muted">À évaluer</span>')
        . ($start && $lvl && $lvl !== $start ? ' <small class="' . ($lvl > $start ? 'up' : 'down') . '">' . ($lvl > $start ? '↗' : '↘') . ' depuis « ' . e(mb_strtolower(SKILL_LEVELS[$start])) . ' »</small>' : '') . '</span>';
    return $h;
}


// ---------- Onglet « Outils » de l'élève (personnalisable par la tutrice) ----------
const TOOLS_DEFAULT_METHODS = [
    ['icon' => 'list-checks', 'title' => 'Ma checklist devoirs', 'steps' => ['Je lis toute la consigne', 'Je souligne les mots importants', 'Je fais', 'Je vérifie ma réponse', 'Je coche dans mon agenda']],
    ['icon' => 'flag', 'title' => 'Préparer un contrôle (J-3)', 'steps' => ['J-3 : je relis et je fais ma fiche', 'J-2 : je me teste sans regarder', 'J-1 : je revois seulement ce qui bloque', 'Le jour J : je respire, je lis tout le sujet avant de commencer']],
    ['icon' => 'brain', 'title' => 'Se tester plutôt que relire', 'steps' => ['Je cache ma leçon', "J'écris tout ce dont je me souviens", 'Je compare avec la leçon', 'Je recommence le lendemain sur ce qui manquait']],
];
const TOOLS_DEFAULT_LINKS = [
    ['name' => 'Lumni', 'url' => 'https://www.lumni.fr/', 'desc' => 'Vidéos et jeux gratuits pour toutes les matières, du service public.'],
    ['name' => 'Khan Academy', 'url' => 'https://fr.khanacademy.org/', 'desc' => 'Cours et exercices de maths et de sciences, à ton rythme.'],
];
/** Contenu de l'onglet Outils : la version de la tutrice si elle existe, sinon les valeurs par défaut. */
function student_tools(array $s, array $reco): array
{
    $saved = json_decode((string)($s['tools_json'] ?? ''), true) ?: [];
    $out = [
        'tools' => array_map(fn($t) => ['icon' => $t['icon'] ?? 'lightbulb', 'title' => $t['title'], 'desc' => $t['desc'] ?? ''], array_slice($reco['tools'] ?? [], 0, 4)),
        'methods' => TOOLS_DEFAULT_METHODS,
        'links' => TOOLS_DEFAULT_LINKS,
    ];
    $custom = [];
    foreach (array_keys($out) as $k) if (isset($saved[$k]) && is_array($saved[$k])) { $out[$k] = $saved[$k]; $custom[$k] = true; }
    $out['custom'] = $custom;
    return $out;
}
/** Texte éditable (une entrée par ligne / bloc) <-> listes. */
function tools_to_text(string $kind, array $items): string
{
    return match ($kind) {
        'tools' => implode("\n", array_map(fn($t) => $t['title'] . ($t['desc'] !== '' ? ' : ' . $t['desc'] : ''), $items)),
        'methods' => implode("\n\n", array_map(fn($m) => $m['title'] . "\n" . implode("\n", array_map(fn($x) => '- ' . $x, $m['steps'])), $items)),
        'links' => implode("\n", array_map(fn($l) => $l['name'] . ' | ' . $l['url'] . ($l['desc'] !== '' ? ' | ' . $l['desc'] : ''), $items)),
        default => '',
    };
}
function tools_from_text(string $kind, string $txt): array
{
    $txt = str_replace("\r", '', mb_substr($txt, 0, 8000)); $out = [];
    if ($kind === 'tools') {
        foreach (explode("\n", $txt) as $l) { $l = trim($l); if ($l === '') continue;
            [$t, $d] = array_pad(array_map('trim', preg_split('/\s+:\s+|\s*:\s+(?=\S)/u', $l, 2)), 2, '');
            $out[] = ['icon' => 'lightbulb', 'title' => mb_substr($t, 0, 80), 'desc' => mb_substr($d, 0, 300)]; }
    } elseif ($kind === 'methods') {
        foreach (preg_split('/\n\s*\n/', trim($txt)) as $blk) { $lines = array_values(array_filter(array_map('trim', explode("\n", $blk)), fn($x) => $x !== '')); if (!$lines) continue;
            $out[] = ['icon' => 'list-checks', 'title' => mb_substr(ltrim($lines[0], '-• '), 0, 80), 'steps' => array_map(fn($x) => mb_substr(ltrim($x, '-• '), 0, 200), array_slice($lines, 1, 12))]; }
    } elseif ($kind === 'links') {
        foreach (explode("\n", $txt) as $l) { $l = trim($l); if ($l === '') continue;
            [$n, $u, $d] = array_pad(array_map('trim', explode('|', $l, 3)), 3, '');
            if (!preg_match('~^https?://~i', $u) && preg_match('~^[a-z0-9.-]+\.[a-z]{2,}~i', $u)) $u = 'https://' . $u;
            if (!filter_var($u, FILTER_VALIDATE_URL) || !preg_match('~^https?://~i', $u)) continue;
            $out[] = ['name' => mb_substr($n ?: parse_url($u, PHP_URL_HOST), 0, 80), 'url' => mb_substr($u, 0, 300), 'desc' => mb_substr($d, 0, 200)]; }
    }
    return array_slice($out, 0, 20);
}


// ---------- Banque d'objectifs SMART (spécifiques, mesurables, atteignables, réalistes, temporellement définis) ----------
const GOAL_BANK = [
    'organisation' => ['list-checks', 'Organisation et gestion du temps', 'methodo', [
        ['Planifier ma semaine', 'Chaque lundi, je prends 5 minutes pour noter mes contrôles, devoirs importants et moments de révision.', 28],
        ['Anticiper un contrôle', 'Je commence à réviser au moins 3 jours avant un contrôle, pendant 10 minutes minimum.', 21],
        ['Réviser régulièrement', 'Je fais 3 petites séances de révision de 10 minutes sur des jours différents.', 7],
        ['Respecter un temps de travail', 'Je réalise 2 séances de 20 minutes de travail sans téléphone ni jeux vidéo.', 7],
        ['Choisir mes priorités', 'Avant 3 séances de travail, je choisis les 2 tâches les plus importantes à faire.', 14],
        ['Estimer mon temps', 'Pour 3 exercices, j\'estime le temps nécessaire puis je compare avec le temps réellement passé.', 14],
    ]],
    'methodes' => ['brain', 'Méthodes de travail et mémorisation', 'methodo', [
        ['Réviser sans regarder mon cours', 'À 3 reprises, je ferme mon cours et j\'essaie de restituer 3 informations de mémoire.', 14],
        ['Tester mes connaissances', 'Je réalise 2 quiz de 5 questions sans regarder mes fiches.', 14],
        ['Revoir mes erreurs', 'Je reprends 3 erreurs d\'anciens exercices et j\'essaie de les corriger.', 14],
        ['Identifier mes lacunes', 'Je note 3 notions que je maîtrise et 2 que je dois retravailler.', 7],
        ['Expliquer une leçon', 'J\'explique une notion avec mes propres mots pendant 2 minutes, sans lire mon cours.', 7],
        ['Réviser à plusieurs reprises', 'Je revois une même notion 3 fois dans la semaine, sur 3 jours différents.', 7],
    ]],
    'comprehension' => ['book-open', 'Compréhension et application', 'comprehension', [
        ['Comprendre les consignes', 'Sur 3 exercices, je souligne les mots importants et reformule ce qu\'on me demande.', 14],
        ['Utiliser une méthode', 'J\'applique toutes les étapes de la méthode L.I.R.E. sur 2 problèmes.', 14],
        ['Choisir ma démarche', 'Avant de résoudre 3 exercices, j\'explique quelle méthode je vais utiliser et pourquoi.', 14],
        ['Vérifier ma compréhension', 'Après 2 leçons, je donne un exemple concret pour montrer que j\'ai compris.', 14],
        ['Décomposer un problème', 'Pour 2 exercices difficiles, je découpe le travail en au moins 3 petites étapes.', 14],
        ['Identifier mon blocage', 'Quand je ne comprends pas, je note précisément ce qui me bloque dans 3 exercices.', 14],
    ]],
    'autonomie' => ['rocket', 'Autonomie et prise d\'initiative', 'autonomie', [
        ['Commencer seul', 'Je commence mes devoirs sans qu\'un adulte me le rappelle, au moins 3 fois dans la semaine.', 7],
        ['Chercher avant de demander', 'Sur 3 exercices, je cherche seul pendant 5 minutes avant de demander de l\'aide.', 14],
        ['Terminer un exercice seul', 'Je réalise 2 exercices de A à Z sans aide, en utilisant mes outils si nécessaire.', 14],
        ['Préparer mon matériel', 'Avant 3 séances, je prépare seul tout ce dont j\'ai besoin pour travailler.', 14],
        ['Utiliser mes ressources', 'Je cherche une réponse dans mon cours, mes fiches ou une vidéo avant de demander de l\'aide, à 2 reprises.', 14],
    ]],
    'perseverance' => ['flag', 'Persévérance et motivation', 'motivation', [
        ['Ne pas abandonner tout de suite', 'Face à 3 difficultés, j\'essaie une deuxième méthode avant d\'arrêter.', 14],
        ['Reprendre après un blocage', 'Quand je bloque, je fais une pause de 2 minutes puis je réessaie, au moins 2 fois.', 14],
        ['Faire un exercice difficile', 'Je termine 1 exercice que je trouve difficile, même si j\'ai besoin d\'indices.', 7],
        ['Travailler une matière difficile', 'Je consacre 2 fois 15 minutes à une matière que j\'aime moins.', 7],
        ['Reconnaître mes progrès', 'En fin de semaine, je note 2 choses que je réussis mieux qu\'avant.', 7],
    ]],
    'stress' => ['heart', 'Gestion du stress et des évaluations', 'evaluations', [
        ['M\'entraîner en temps limité', 'Je réalise 2 exercices avec un temps défini à l\'avance, sans le dépasser de plus de 2 minutes.', 14],
        ['Apprendre à me calmer', 'Avant 3 exercices ou évaluations, je prends 1 minute pour respirer lentement.', 14],
        ['Garder du temps pour relire', 'Pendant un contrôle blanc, je réserve les 5 dernières minutes à la relecture.', 14],
        ['Utiliser une méthode de relecture', 'Sur 3 exercices, je vérifie la consigne, ma réponse et les éventuelles erreurs de calcul.', 14],
        ['Gérer une question difficile', 'Pendant 1 contrôle blanc, si je bloque plus de 3 minutes, je passe à la suite et j\'y reviens ensuite.', 14],
    ]],
    'confiance' => ['smile', 'Confiance en soi et communication', 'motivation', [
        ['Oser poser des questions', 'Je pose au moins 2 questions précises lorsque je ne comprends pas quelque chose.', 14],
        ['Faire mon bilan', 'En fin de semaine, je cite 1 réussite, 1 difficulté et 1 chose à améliorer la semaine suivante.', 7],
    ]],
];
function week_monday(?string $d = null): string { return date('Y-m-d', strtotime('monday this week', strtotime($d ?? 'today'))); }
function week_sunday(?string $d = null): string { return date('Y-m-d', strtotime(week_monday($d) . ' +6 days')); }
/** « Semaine du lun. 23/11 au dim. 29/11 » */
function week_label(?string $d = null): string { return 'du lun. ' . date('d/m', strtotime(week_monday($d))) . ' au dim. ' . date('d/m', strtotime(week_sunday($d))); }
/** Ajoute un objectif de la banque à la liste de l'élève (sans doublon). */
function goal_bank_add(int $studentId, int $by, string $key): string
{
    [$cat, $i] = array_pad(explode(':', $key, 2), 2, '');
    $g = GOAL_BANK[$cat][3][(int)$i] ?? null;
    if (!$g || !ctype_digit((string)$i)) return '!Objectif introuvable.';
    if (one('SELECT 1 FROM goals WHERE student_id=? AND LOWER(title)=LOWER(?)', [$studentId, $g[0]])) return '!Cet objectif est déjà dans la liste.';
    // objectif hebdomadaire : du lundi au dimanche de la semaine en cours (status « En cours »)
    q('INSERT INTO goals (student_id, title, description, category, target_date, status, created_by) VALUES (?,?,?,?,?,?,?)',
        [$studentId, $g[0], $g[1], GOAL_BANK[$cat][2], week_sunday(), 'in_progress', $by]);
    return 'Objectif de la semaine ajouté : ' . $g[0];
}
