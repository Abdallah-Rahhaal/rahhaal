<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function failJson(int $http, string $error, string $message = '', ?int $upstreamStatus = null): never {
    http_response_code($http);
    $out = ['error' => $error];
    if ($message !== '') $out['message'] = mb_substr($message, 0, 500);
    if ($upstreamStatus !== null) $out['status'] = $upstreamStatus;
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    failJson(405, 'method_not_allowed', 'هذه الواجهة تستقبل POST فقط.');
}

$input = json_decode(file_get_contents('php://input') ?: '{}', true);
$message = trim((string)($input['message'] ?? ''));
if ($message === '' || mb_strlen($message) > 3000) {
    failJson(400, 'invalid_message', 'الرسالة فارغة أو طويلة جدًا.');
}

$configFile = __DIR__ . '/openai-config.php';
if (!is_file($configFile)) {
    failJson(503, 'ai_not_configured', 'ملف api/openai-config.php غير موجود.');
}
$config = require $configFile;
$apiKey = trim((string)($config['api_key'] ?? ''));
$model = trim((string)($config['model'] ?? 'gpt-5.6-luna'));
if ($apiKey === '' || str_contains($apiKey, 'PUT_YOUR')) {
    failJson(503, 'ai_not_configured', 'مفتاح OpenAI غير مضبوط في openai-config.php.');
}
if (!function_exists('curl_init')) {
    failJson(500, 'curl_missing', 'امتداد PHP cURL غير متاح على الخادم.');
}

$language = (string)($input['language'] ?? 'ar');
$destinations = array_slice((array)($input['destinations'] ?? []), 0, 10);
$days = $input['days'] ?? null;
$rahhalData = array_slice((array)($input['rahhalData'] ?? []), 0, 80);

$siteContext = json_encode([
    'destinations_detected' => $destinations,
    'requested_days' => $days,
    'rahhal_cards' => $rahhalData,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($siteContext === false) $siteContext = '{}';
if (strlen($siteContext) > 45000) $siteContext = substr($siteContext, 0, 45000);

$instructions = <<<'TXT'
أنت «رحّال الذكي»، مساعد سفر عربي داخل موقع Rahhal.
أجب بلغة المستخدم، وبالعربية الفصحى السهلة افتراضيًا. كن عمليًا ومختصرًا ومنظمًا.
استخدم بيانات كروت رحّال المرسلة لك عندما يسأل المستخدم عن وجهة موجودة في الموقع، ولا تنسب للموقع بيانات غير موجودة فيه.
يمكنك الاستفادة من معرفتك العامة للتخطيط، لكن المعلومات المتغيرة مثل الأسعار الحالية والطقس اللحظي وساعات العمل والتأشيرات والتحذيرات الأمنية وتوفر الحجوزات تحتاج تحققًا حديثًا إذا لم يتوفر مصدر حي.
لا تختلق روابط أو أسعارًا أو مواعيد. لا تطلب من المستخدم مفتاح API أو بيانات سرية.
عند طلب برنامج رحلة، اقترح خطة يومية منطقية بحسب عدد الأيام وراعِ تقارب الأماكن إن أمكن.
TXT;

$userPrompt = "لغة الواجهة: {$language}\nسياق موقع رحّال (JSON): {$siteContext}\n\nسؤال المستخدم: {$message}";
$payload = [
    'model' => $model,
    'instructions' => $instructions,
    'input' => $userPrompt,
    'max_output_tokens' => 900,
];

$ch = curl_init('https://api.openai.com/v1/responses');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
]);
$raw = curl_exec($ch);
$status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($raw === false || $curlError !== '') {
    failJson(502, 'ai_connection_failed', $curlError !== '' ? $curlError : 'تعذر الاتصال بخادم OpenAI.');
}
$data = json_decode((string)$raw, true);
if ($status < 200 || $status >= 300) {
    $apiMessage = is_array($data) ? (string)($data['error']['message'] ?? '') : '';
    $apiType = is_array($data) ? (string)($data['error']['type'] ?? '') : '';
    $apiCode = is_array($data) ? (string)($data['error']['code'] ?? '') : '';
    $parts = array_values(array_filter([$apiMessage, $apiType, $apiCode]));
    failJson(502, 'openai_request_failed', implode(' | ', $parts) ?: 'رفض OpenAI الطلب دون رسالة تفصيلية.', $status);
}
if (!is_array($data)) {
    failJson(502, 'invalid_openai_response', 'وصل رد غير صالح من OpenAI.', $status);
}

$reply = trim((string)($data['output_text'] ?? ''));
if ($reply === '' && isset($data['output']) && is_array($data['output'])) {
    foreach ($data['output'] as $item) {
        if (!is_array($item)) continue;
        foreach ((array)($item['content'] ?? []) as $content) {
            if (is_array($content) && ($content['type'] ?? '') === 'output_text' && isset($content['text'])) {
                $reply .= (string)$content['text'];
            }
        }
    }
    $reply = trim($reply);
}
if ($reply === '') {
    failJson(502, 'empty_ai_response', 'اتصل الخادم بـ OpenAI بنجاح لكن لم يصل نص للإجابة.', $status);
}

echo json_encode(['reply' => $reply, 'model' => $model], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
