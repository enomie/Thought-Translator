<?php
header('Content-Type: application/json; charset=utf-8');

// Paste your Groq API key here (https://console.groq.com/keys):
$apiKey = 'gsk_YOUR_GROQ_API_KEY_HERE';

$input = json_decode(file_get_contents('php://input'), true);
$rawText = trim($input['text'] ?? '');
$context = $input['context'] ?? 'poetry';
$language = $input['language'] ?? 'en';
if (!in_array($language, ['en', 'de'], true)) {
    $language = 'en';
}

if (empty($rawText)) {
    http_response_code(400);
    echo json_encode(['error' => 'Please enter some text.']);
    exit;
}

$prompts = [
    'text' => 'You are an expert editor. Rewrite the user\'s rough note into one or more short, clear paragraphs: concise and punchy, but still natural—how a thoughtful person would actually speak or write. Fix grammar and flow; remove filler and repetition; keep the original meaning and tone. No headings, labels, or meta commentary—only the polished text.',
    'poetry' => 'You are a poet. Turn the user\'s rough thoughts into vivid, atmospheric, expressive poetry. Reply with only the poem—no introduction.',
    'programming' => 'You are a senior software engineer. Turn the user\'s vague note into precise technical requirements, clean function designs, or pseudocode. Be concise and exact—no filler.',
    'templates' => 'You are a documentation and compliance template specialist. From the user\'s request, produce a ready-to-fill document skeleton—not the full content. Infer the right structure (e.g. ISO standard checklist, policy, SOP, user manual, work instruction, requirements spec, test plan, meeting agenda, project charter, app/software requirements document, risk register, audit checklist).

Format your output as a clear outline:
- Start with a one-line document title and optional metadata block (purpose, audience, version, owner) as empty placeholders if useful.
- Use numbered sections and subsections (1, 1.1, 1.2 …).
- For each section: a short heading plus 1–3 sentences describing what belongs there (topics, fields, checklist items to cover, or tables to include). Use [placeholder] hints where helpful.
- Match industry conventions when the user names a standard (e.g. ISO 27001) or document type; do not invent certified compliance—this is a structural template only.
- No filler essay text; no meta introduction—output only the template outline.',
    'legal' => 'You are an experienced legal writing assistant (not a licensed attorney). Turn the user\'s rough note into clear, structured legal-style prose: issue summary, relevant facts (as stated), parties and jurisdiction if mentioned, open questions, risks to flag for counsel, and suggested next steps (e.g. documents to gather, clauses to review, questions for a lawyer). Use precise, neutral language suitable for a memo or attorney briefing. Do not invent facts or cite specific statutes unless the user provided them. End with one short disclaimer that this is not legal advice and the user should consult a qualified lawyer in their jurisdiction. Reply in structured prose with no filler.',
    'graphics' => 'You are a senior graphic designer and art director for print and digital. Turn the user\'s rough idea into a clear design brief: purpose, audience, format (flyer, poster, brochure, business card, web page, app screen, interface, social asset, etc.), layout and hierarchy, typography, color palette, imagery style, and deliverable specs. This is layout and visual communication—not AI photo generation or photography prompts. Reply in structured, actionable prose with no filler.',
    'photo' => 'You are a Midjourney and Stable Diffusion prompt engineer. Turn the user\'s note into a detailed image-generation prompt: subject, style, mood, lighting, camera or lens, composition, and quality tags. Reply with only the prompt—no introduction.',
    'studio' => 'You are a commercial product photography specialist writing AI image prompts. From the user\'s note, produce one complete image-generation prompt for their subject/scene. You MUST always embed this studio setup exactly (adjust only the product/subject to match the user):

Studio setup & photography:
- Aspect ratio: Square (1:1).
- Background: Clean, seamless, neutral light gray to off-white studio backdrop.
- Lighting: Studio softbox lighting, strictly neutral 5500K white balance (no yellow, warm, or color cast).
- Shadows: Extremely soft, subtle contact shadows under the product and any separate pieces—no hard edges.
- Look: Clean, minimal, high-end commercial product photography.

Reply with only the final prompt—no introduction.'
];

$languageInstruction = $language === 'de'
    ? 'Write your entire output in German (Deutsch), including headings and disclaimers.'
    : 'Write your entire output in English, including headings and disclaimers.';

$systemPrompt = ($prompts[$context] ?? 'Rewrite the following raw text into clear, polished prose.')
    . "\n\n"
    . $languageInstruction;

$payload = [
    // llama-3.3-70b-versatile: retired for Free/Developer tiers since Aug 2026 (see Groq docs)
    'model' => 'openai/gpt-oss-120b',
    'messages' => [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $rawText]
    ],
    'temperature' => match ($context) {
        'poetry' => 0.8,
        'text' => 0.45,
        'templates' => 0.35,
        'photo', 'studio' => 0.5,
        default => 0.2,
    },
    'max_tokens' => $context === 'templates' ? 1536 : 512
];

$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_TIMEOUT => 15
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

if ($response === false || $httpCode !== 200) {
    http_response_code(500);
    $groqBody = json_decode($response ?: '', true);
    $groqMessage = $groqBody['error']['message'] ?? null;

    if ($curlError !== '') {
        $message = 'Could not connect to the Groq API.';
    } elseif ($groqMessage) {
        $message = $groqMessage;
    } elseif ($httpCode === 401) {
        $message = 'Invalid API key.';
    } else {
        $message = 'Groq API error (HTTP ' . $httpCode . ').';
    }

    echo json_encode(['error' => $message]);
    exit;
}

$data = json_decode($response, true);
$output = $data['choices'][0]['message']['content'] ?? 'No response received.';

echo json_encode(['result' => trim($output)]);
