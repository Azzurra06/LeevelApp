<?php
declare(strict_types=1);
// Génération de quiz par IA (API Claude). Appel HTTPS direct, sans dépendance externe.
// Seuls la matière, le titre du chapitre et la classe sont envoyés : aucune donnée personnelle de l'élève.

const AI_MODEL = 'claude-opus-5-5';
const AI_API_VERSION = '2026-10-21';

function ai_key_file(): string { return APP_ROOT . '/storage/ai_key.php'; }

/** Clé API : config/config.php ('anthropic_api_key') ou clé enregistrée par la tutrice (storage, inaccessible depuis le web). */
function ai_api_key(): ?string
{
    $k = (string)(config('anthropic_api_key') ?? '');
    if ($k === '' && is_file(ai_key_file())) $k = (string)(include ai_key_file());
    return $k !== '' ? $k : null;
}
function ai_available(): bool { return ai_api_key() !== null && function_exists('curl_init'); }

function ai_save_key(?string $key): void
{
    $dir = dirname(ai_key_file());
    if (!is_dir($dir)) mkdir($dir, 0750, true);
    if (!is_file($dir . '/.htaccess')) file_put_contents($dir . '/.htaccess', "Require all denied\nDeny from all\n");
    if ($key === null) { @unlink(ai_key_file()); return; }
    file_put_contents(ai_key_file(), "<?php\nreturn " . var_export($key, true) . ";\n", LOCK_EX);
    @chmod(ai_key_file(), 0600);
}

/** Libellé de niveau envoyé à l'IA, ex. « 3e (collège, cycle 4) ». */
function ai_level_label(?string $grade): string
{
    $label = CLASSES[$grade ?? ''] ?? ($grade ?: 'niveau non précisé');
    return $label . match (true) {
        in_array($grade, ['CP', 'CE1', 'CE2'], true) => ' (école élémentaire, cycle 2)',
        in_array($grade, ['CM1', 'CM2'], true) => ' (école élémentaire, cycle 3)',
        $grade === '6e' => ' (collège, cycle 3)',
        in_array($grade, ['5e', '4e', '3e'], true) => ' (collège, cycle 4)',
        in_array($grade, ['2nde', '1re', 'Tle'], true) => ' (lycée)',
        default => '',
    };
}

/**
 * Génère des questions de QCM pour un chapitre.
 * Renvoie ['questions' => [[question, options[], answer, explanation], ...]] ou ['error' => message lisible].
 */
function ai_generate_quiz(string $subject, string $chapter, ?string $grade, int $count, string $focus = '', array $existing = []): array
{
    $key = ai_api_key();
    if (!$key) return ['error' => "La génération par IA n'est pas encore configurée (clé API manquante dans « Mon compte »)."];
    if (!function_exists('curl_init')) return ['error' => "Le serveur ne permet pas les appels externes (extension cURL absente)."];
    $count = max(1, min(10, $count));

    $system = "Tu es un professeur expérimenté de l'Éducation nationale française. Tu rédiges des quiz de révision (QCM) "
        . "pour des élèves accompagnés en soutien scolaire. Tes questions respectent le programme officiel du niveau indiqué, "
        . "vont du plus simple au plus exigeant, et vérifient la compréhension plutôt que le par-cœur. "
        . "Chaque question a une seule bonne réponse et des réponses fausses plausibles (erreurs fréquentes d'élèves), sans piège ni « toutes les réponses ». "
        . "L'explication, en une ou deux phrases, s'adresse à l'élève en le tutoyant, avec bienveillance, et dit pourquoi la bonne réponse est juste. "
        . "Écris en français correct, avec un vocabulaire adapté à l'âge. Pour les formules, utilise du texte simple (ex. x² , 3/4, √2), sans LaTeX.";
    $user = "Matière : $subject\nNiveau : " . ai_level_label($grade) . "\nChapitre : $chapter\n"
        . ($focus !== '' ? "Consigne de la tutrice : $focus\n" : '')
        . ($existing ? "Questions déjà présentes dans le quiz (ne pas les reprendre ni les reformuler) :\n- " . implode("\n- ", array_slice($existing, 0, 30)) . "\n" : '')
        . "\nRédige $count question" . ($count > 1 ? 's' : '') . " de QCM, chacune avec 4 réponses possibles.";

    $schema = [
        'type' => 'object',
        'properties' => [
            'questions' => ['type' => 'array', 'items' => [
                'type' => 'object',
                'properties' => [
                    'question' => ['type' => 'string'],
                    'options' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'answer_index' => ['type' => 'integer', 'description' => 'Index (à partir de 0) de la bonne réponse dans options'],
                    'explanation' => ['type' => 'string'],
                ],
                'required' => ['question', 'options', 'answer_index', 'explanation'],
                'additionalProperties' => false,
            ]],
        ],
        'required' => ['questions'],
        'additionalProperties' => false,
    ];
    $body = [
        'model' => AI_MODEL,
        'max_tokens' => 16000,
        'output_config' => ['effort' => 'medium', 'format' => ['type' => 'json_schema', 'schema' => $schema]],
        'fallbacks' => 'default', // en cas de refus d'un filtre de sécurité, l'API relance sur le modèle de repli recommandé
        'system' => $system,
        'messages' => [['role' => 'user', 'content' => $user]],
    ];

    @set_time_limit(180);
    $ch = curl_init(rtrim((string)(config('anthropic_base_url') ?: 'https://api.anthropic.com'), '/') . '/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 150,
        CURLOPT_HTTPHEADER => [
            'content-type: application/json',
            'x-api-key: ' . $key,
            'anthropic-version: ' . AI_API_VERSION,
            'anthropic-beta: server-side-fallback-2026-07-01',
        ],
        CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
    ]);
    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $netErr = curl_error($ch);
    curl_close($ch);

    if ($raw === false) return ['error' => "Impossible de joindre le service d'IA" . ($netErr ? " ($netErr)" : '') . '. Réessayez dans un instant.'];
    $res = json_decode((string)$raw, true);
    if ($status !== 200 || !is_array($res)) {
        $type = $res['error']['type'] ?? '';
        return ['error' => match (true) {
            $status === 401 || $type === 'authentication_error' => "La clé API est refusée : vérifiez-la dans « Mon compte ».",
            $status === 403 || $type === 'permission_error' => "La clé API n'a pas accès à ce modèle.",
            $status === 429 || $type === 'rate_limit_error' => "Trop de demandes en même temps : réessayez dans une minute.",
            $status === 529 || $type === 'overloaded_error' || $status >= 500 => "Le service d'IA est momentanément surchargé : réessayez dans un instant.",
            default => "La génération a échoué (" . ($status ?: 'réseau') . ($type ? ", $type" : '') . ").",
        }];
    }
    $stop = $res['stop_reason'] ?? '';
    if ($stop === 'refusal') return ['error' => "L'IA a refusé de traiter ce chapitre. Reformulez le titre ou ajoutez les questions à la main."];
    if ($stop === 'max_tokens') return ['error' => "La réponse de l'IA a été coupée. Demandez moins de questions."];

    $text = '';
    foreach ((array)($res['content'] ?? []) as $block) if (($block['type'] ?? '') === 'text') $text .= $block['text'];
    $data = json_decode($text, true);
    $out = [];
    foreach ((array)($data['questions'] ?? []) as $q) {
        $opts = array_values(array_filter(array_map(fn($o) => mb_substr(trim((string)$o), 0, 200), (array)($q['options'] ?? [])), fn($o) => $o !== ''));
        $ans = is_int($q['answer_index'] ?? null) ? $q['answer_index'] : -1;
        $question = mb_substr(trim((string)($q['question'] ?? '')), 0, 500);
        if ($question === '' || count($opts) < 2 || $ans < 0 || $ans >= count($opts)) continue;
        // 4 réponses maximum : on garde la bonne réponse si elle est au-delà
        if (count($opts) > 4) { $good = $opts[$ans]; $opts = array_slice(array_values(array_diff($opts, [$good])), 0, 3); array_splice($opts, min($ans, 3), 0, [$good]); $ans = array_search($good, $opts, true); }
        $out[] = ['question' => $question, 'options' => $opts, 'answer' => (int)$ans, 'explanation' => mb_substr(trim((string)($q['explanation'] ?? '')), 0, 500)];
    }
    if (!$out) return ['error' => "L'IA n'a pas renvoyé de question exploitable. Réessayez."];
    return ['questions' => $out];
}
