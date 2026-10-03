<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    protected string $provider;
    protected string $geminiApiKey;
    protected string $geminiModel;
    protected string $geminiApiUrl;
    protected int $cacheTtl;

    public function __construct()
    {
        $this->provider = config('ai.provider', 'gemini');
        $this->geminiApiKey = config('ai.gemini.api_key', '');
        $this->geminiModel = config('ai.gemini.model', 'gemini-1.5-flash');
        $this->geminiApiUrl = config('ai.gemini.api_url', 'https://generativelanguage.googleapis.com/v1beta/models');
        $this->cacheTtl = config('ai.cache_ttl', 60);
    }

    /**
     * AI トリアージ: タスクの緊急度・重要度・4象限を判定
     */
    public function triage(string $title, ?string $description = null, ?string $dueDate = null, ?string $scope = 'personal'): array
    {
        $cacheKey = 'ai_triage_' . md5($title . '|' . ($description ?? '') . '|' . ($dueDate ?? '') . '|' . $scope);

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheTtl), function () use ($title, $description, $dueDate, $scope) {
            if (!empty($this->geminiApiKey)) {
                try {
                    $result = $this->callGeminiForTriage($title, $description, $dueDate, $scope);
                    if ($result) {
                        $result['is_mock'] = false;
                        return $result;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Gemini API call failed, falling back to mock: ' . $e->getMessage());
                }
            }

            // フォールバック / モックロジック
            return $this->mockTriage($title, $description, $dueDate, $scope);
        });
    }

    /**
     * AI タスク分解: 大きなタスクを実行可能なサブタスクに分解
     */
    public function breakdown(string $title, ?string $description = null, ?string $methodology = null): array
    {
        $cacheKey = 'ai_breakdown_' . md5($title . '|' . ($description ?? '') . '|' . ($methodology ?? ''));

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheTtl), function () use ($title, $description, $methodology) {
            if (!empty($this->geminiApiKey)) {
                try {
                    $result = $this->callGeminiForBreakdown($title, $description, $methodology);
                    if ($result) {
                        $result['is_mock'] = false;
                        return $result;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Gemini API breakdown failed, falling back to mock: ' . $e->getMessage());
                }
            }

            return $this->mockBreakdown($title, $description, $methodology);
        });
    }

    /**
     * AI コーチ: 象限バランスとタスク状況から改善アドバイスを生成
     */
    public function coach(array $stats): array
    {
        $cacheKey = 'ai_coach_' . md5(json_encode($stats));

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($stats) {
            if (!empty($this->geminiApiKey)) {
                try {
                    $result = $this->callGeminiForCoach($stats);
                    if ($result) {
                        $result['is_mock'] = false;
                        return $result;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Gemini API coach failed, falling back to mock: ' . $e->getMessage());
                }
            }

            return $this->mockCoach($stats);
        });
    }

    // ==========================================
    // Gemini API 呼び出し
    // ==========================================

    protected function callGeminiForTriage(string $title, ?string $description, ?string $dueDate, ?string $scope): ?array
    {
        $url = "{$this->geminiApiUrl}/{$this->geminiModel}:generateContent?key={$this->geminiApiKey}";

        $systemPrompt = "あなたはアイゼンハワーマトリクス（時間管理・優先度判定）とアジャイル開発のエキスパートAIです。
与えられたタスクについて、緊急度(1-5)と重要度(1-5)を評価し、最適な第1〜第4象限を判定してください。
象限の定義:
- 'urgent_important': 第1象限 (緊急度・重要度ともに高い: 締切直前、重大なトラブル対応など)
- 'not_urgent_important': 第2象限 (重要度が高いが緊急度はまだ低い: 中長期の価値創出、設計、学習、事前準備など)
- 'urgent_not_important': 第3象限 (緊急度は高いが重要度は低い: 雑務、急な連絡対応、他者に任せられること)
- 'not_urgent_not_important': 第4象限 (緊急度・重要度ともに低い: 時間の浪費、過剰な仕様など)

必ず指定されたJSONフォーマットで出力してください。";

        $userPrompt = "タスク名: {$title}\n詳細: " . ($description ?? 'なし') . "\n期日: " . ($dueDate ?? '未設定') . "\n対象スコープ: {$scope}";

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $systemPrompt . "\n\n" . $userPrompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'priority_type' => ['type' => 'STRING', 'enum' => ['urgent_important', 'not_urgent_important', 'urgent_not_important', 'not_urgent_not_important']],
                        'priority_label' => ['type' => 'STRING', 'enum' => ['DO', 'PLAN', 'DELEGATE', 'ELIMINATE']],
                        'urgency_score' => ['type' => 'INTEGER'],
                        'importance_score' => ['type' => 'INTEGER'],
                        'estimated_minutes' => ['type' => 'INTEGER'],
                        'reason' => ['type' => 'STRING'],
                        'action_advice' => ['type' => 'STRING'],
                    ],
                    'required' => ['priority_type', 'priority_label', 'urgency_score', 'importance_score', 'estimated_minutes', 'reason', 'action_advice']
                ]
            ]
        ];

        $response = Http::timeout(10)->post($url, $payload);

        if (!$response->successful()) {
            Log::error('Gemini API Error: ' . $response->body());
            return null;
        }

        $body = $response->json();
        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!$text) return null;

        return json_decode($text, true);
    }

    protected function callGeminiForBreakdown(string $title, ?string $description, ?string $methodology): ?array
    {
        $url = "{$this->geminiApiUrl}/{$this->geminiModel}:generateContent?key={$this->geminiApiKey}";

        $systemPrompt = "あなたは敏腕テックリード兼スクラムマスターです。
与えられたタスクを、エンジニアがすぐに着手できる15〜60分程度の具体的なサブタスク（3〜5件）に分解してください。
各サブタスクにはアイゼンハワーマトリクスの priority_type ('urgent_important', 'not_urgent_important', 'urgent_not_important', 'not_urgent_not_important') を割り振ってください。";

        $userPrompt = "親タスク名: {$title}\n詳細: " . ($description ?? 'なし') . "\n手法: " . ($methodology ?? 'agile');

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $systemPrompt . "\n\n" . $userPrompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'breakdown_summary' => ['type' => 'STRING'],
                        'subtasks' => [
                            'type' => 'ARRAY',
                            'items' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'title' => ['type' => 'STRING'],
                                    'priority_type' => ['type' => 'STRING', 'enum' => ['urgent_important', 'not_urgent_important', 'urgent_not_important', 'not_urgent_not_important']],
                                    'priority_label' => ['type' => 'STRING', 'enum' => ['DO', 'PLAN', 'DELEGATE', 'ELIMINATE']],
                                    'estimated_minutes' => ['type' => 'INTEGER'],
                                    'description' => ['type' => 'STRING']
                                ],
                                'required' => ['title', 'priority_type', 'priority_label', 'estimated_minutes', 'description']
                            ]
                        ]
                    ],
                    'required' => ['breakdown_summary', 'subtasks']
                ]
            ]
        ];

        $response = Http::timeout(10)->post($url, $payload);
        if (!$response->successful()) return null;

        $body = $response->json();
        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!$text) return null;

        return json_decode($text, true);
    }

    protected function callGeminiForCoach(array $stats): ?array
    {
        $url = "{$this->geminiApiUrl}/{$this->geminiModel}:generateContent?key={$this->geminiApiKey}";

        $systemPrompt = "あなたはアイゼンハワーマトリクスを用いた生産性向上の専門コーチです。
ユーザーのタスク配分比率（第1象限〜第4象限）を分析し、特に『第2象限（緊急ではないが重要な投資）』の活動を最大化するための建設的で熱意あるアドバイスを提示してください。";

        $userPrompt = "タスク統計データ:\n" . json_encode($stats, JSON_UNESCAPED_UNICODE);

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $systemPrompt . "\n\n" . $userPrompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'headline' => ['type' => 'STRING'],
                        'quadrant_health_score' => ['type' => 'INTEGER'],
                        'insights' => ['type' => 'STRING'],
                        'recommended_action' => ['type' => 'STRING']
                    ],
                    'required' => ['headline', 'quadrant_health_score', 'insights', 'recommended_action']
                ]
            ]
        ];

        $response = Http::timeout(10)->post($url, $payload);
        if (!$response->successful()) return null;

        $body = $response->json();
        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!$text) return null;

        return json_decode($text, true);
    }

    // ==========================================
    // インテリジェント・モック生成（APIキー未設定/オフライン時）
    // ==========================================

    protected function mockTriage(string $title, ?string $description, ?string $dueDate, ?string $scope): array
    {
        // タイトルと詳細メモを合算して解析対象にする
        $targetText = mb_strtolower($title . ' ' . ($description ?? ''));
        $urgency = 2;
        $importance = 3;
        $priorityType = 'not_urgent_important';
        $priorityLabel = 'PLAN';
        $estimatedMinutes = 45;

        // 期日による緊急度の算出
        if ($dueDate) {
            $dueTimestamp = strtotime($dueDate);
            $hoursUntilDue = ($dueTimestamp - time()) / 3600;
            if ($hoursUntilDue <= 24 && $hoursUntilDue > 0) {
                $urgency = 5;
            } elseif ($hoursUntilDue <= 72) {
                $urgency = 4;
            } elseif ($hoursUntilDue <= 168) {
                $urgency = 3;
            }
        }

        // キーワード解析
        // 第1象限 (DO): 緊急・重要（インシデント、本番障害、重大バグ、至急対応など）
        if (preg_match('/(インシデント|本番|障害|至急|緊急|バグ|トラブル|炎上|エラー|ダウン|落ちた|停止|事故|クレーム|脆弱性|漏洩|即時|fix|bug|incident|critical|down|urgent|error)/u', $targetText)) {
            $urgency = max(4, $urgency);
            $importance = 5;
            $priorityType = 'urgent_important';
            $priorityLabel = 'DO';
            $estimatedMinutes = 30;
            $reason = "「{$title}」に含まれる本番・インシデント・緊急キーワードに基づき、業務への影響度が極めて高い第1象限（DO / すぐやる）と判定しました。";
            $advice = "他の予定を中断し、影響範囲の特定と暫定対応を最優先で着手してください。";
        }
        // 第3象限 (DELEGATE): 緊急・非重要（雑務、定例連絡、調整、申請、返信など）
        elseif (preg_match('/(議事録|返信|メール|連絡|調整|申請|リマインド|経費|手配|買い出し|案内|共有|依頼|問い合わせ)/u', $targetText)) {
            $urgency = max(4, $urgency);
            $importance = 2;
            $priorityType = 'urgent_not_important';
            $priorityLabel = 'DELEGATE';
            $estimatedMinutes = 15;
            $reason = "迅速な対応が求められる一方、コア業務の直接成果ではないため、第3象限（DELEGATE / 委任・迅速処理）と判定しました。";
            $advice = "テンプレートの活用や他者への委任、またはスキマ時間にまとめて一括処理するのが効果的です。";
        }
        // 第4象限 (ELIMINATE): 非緊急・非重要（時間の浪費、整理、漠然とした閲覧など）
        elseif (preg_match('/(雑談|片付け|掃除|閲覧|後回し|保留|暇つぶし|なんとなく|不要)/u', $targetText)) {
            $urgency = 1;
            $importance = 1;
            $priorityType = 'not_urgent_not_important';
            $priorityLabel = 'ELIMINATE';
            $estimatedMinutes = 15;
            $reason = "緊急度・重要度ともに現時点では低く、重要なタスクを圧迫するリスクがあるため第4象限（ELIMINATE / 削減・保留）と判定しました。";
            $advice = "本当に今やるべきか再検討し、不要であればタスクから削除または保留リストに移すことを推奨します。";
        }
        // 第2象限 (PLAN): 非緊急・重要（中長期設計、リファクタ、学習、仕組み化など）
        elseif (preg_match('/(設計|リファクタ|学習|勉強|アーキテクチャ|中長期|ロードマップ|改善|ドキュメント|自動化|テスト|ci\/cd|レビュー|戦略|新規)/u', $targetText)) {
            $importance = 5;
            $urgency = min($urgency, 2);
            $priorityType = 'not_urgent_important';
            $priorityLabel = 'PLAN';
            $estimatedMinutes = 60;
            $reason = "将来の技術的負債解消や組織・スキルの成長に直結する高価値タスクのため、第2象限（PLAN / 計画する）に分類しました。";
            $advice = "突発的な作業に時間を奪われる前に、カレンダー上に専用の集中時間を確保（タイムブロッキング）して着実に進めましょう。";
        }
        // デフォルト判定 (期日や文字数から推論)
        else {
            if ($urgency >= 4) {
                $priorityType = 'urgent_important';
                $priorityLabel = 'DO';
                $reason = "期日が迫っているため、最優先で実行すべき第1象限（DO）と判定しました。";
                $advice = "締切に遅れないよう、着手を急いでください。";
            } else {
                // 一般的な作業
                $priorityType = 'not_urgent_important';
                $priorityLabel = 'PLAN';
                $reason = "タスク内容と期日のバランスを総合的に評価し、第2象限（PLAN）として最適化しました。";
                $advice = "作業を細分化し、まずは最初の小さな1歩（15分）に集中して着手することをおすすめします。";
            }
        }

        return [
            'priority_type' => $priorityType,
            'priority_label' => $priorityLabel,
            'urgency_score' => $urgency,
            'importance_score' => $importance,
            'estimated_minutes' => $estimatedMinutes,
            'reason' => $reason,
            'action_advice' => $advice,
            'is_mock' => true,
        ];
    }

    protected function mockBreakdown(string $title, ?string $description, ?string $methodology): array
    {
        return [
            'breakdown_summary' => "「{$title}」を達成するために、設計から実装、検証までの3段階の具体的アクションに細分化しました。",
            'subtasks' => [
                [
                    'title' => "【設計・要件整理】{$title}のスコープ定義",
                    'priority_type' => 'not_urgent_important',
                    'priority_label' => 'PLAN',
                    'estimated_minutes' => 30,
                    'description' => '入出力インターフェースや制約条件を箇条書きで定義し、関係者と合意を形成する。'
                ],
                [
                    'title' => "【コア実装】{$title}の主要ロジック構築",
                    'priority_type' => 'urgent_important',
                    'priority_label' => 'DO',
                    'estimated_minutes' => 45,
                    'description' => '最小限の動作する実装（MVP）をコーディングし、基本ルートの動作を確認する。'
                ],
                [
                    'title' => "【品質検証】{$title}の単体テスト＆動作確認",
                    'priority_type' => 'not_urgent_important',
                    'priority_label' => 'PLAN',
                    'estimated_minutes' => 30,
                    'description' => 'エッジケースや異常系のテストコードを作成し、リグレッションがないことを検証する。'
                ]
            ],
            'is_mock' => true,
        ];
    }

    protected function mockCoach(array $stats): array
    {
        $doCount = $stats['urgent_important'] ?? ($stats['do'] ?? 0);
        $planCount = $stats['not_urgent_important'] ?? ($stats['plan'] ?? 0);
        $total = max(1, ($stats['total'] ?? 1));
        $planRatio = round(($planCount / $total) * 100);

        if ($planRatio >= 40) {
            $score = 90;
            $headline = "理想的な第2象限フォーカスです！🚀";
            $insights = "重要かつ非緊急な未来への投資タスク（第2象限）が全体の{$planRatio}%を占めており、長期的成長につながる極めて健全な状態です。";
            $action = "このペースを維持し、突発的な割り込みタスク（第3象限）をうまくガードしましょう。";
        } elseif ($doCount > $planCount) {
            $score = 65;
            $headline = "緊急対応（第1象限）の比率が高まっています⚠️";
            $insights = "目先の締切やトラブル対応に追われがちです。この状態が続くと燃え尽きのリスクがあります。";
            $action = "今週は毎日朝一番の30分を『第2象限タスク（事前設計や仕組み化）』に強制的に配分してみてください。";
        } else {
            $score = 75;
            $headline = "バランスの取れたタスク配分です👍";
            $insights = "優先順位が整理されています。完了したタスクの知見をドキュメント化するとさらに効果的です。";
            $action = "保留中のタスクがあれば、思い切って第4象限として削除し、認知負荷を下げましょう。";
        }

        return [
            'headline' => $headline,
            'quadrant_health_score' => $score,
            'insights' => $insights,
            'recommended_action' => $action,
            'is_mock' => true,
        ];
    }
}
