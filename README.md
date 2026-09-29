# チーム＆個人タスクマネージャー (Task Matrix)

アイゼンハワーマトリクス（緊急度 × 重要度）と、**チーム開発手法（アジャイル / ウォーターフォール）の切り替え機能**を統合したモダンなフルスタックタスク管理アプリケーションです。  

プロジェクト全体のタスクをチーム手法（スプリント / WBS工程）で可視化・管理し、**「チームタスク → 個人の4象限マトリクス」へシームレスに取り込んで日々の実行に落とし込む**ことができます。

---

## ⚡ 技術スタック

| 分野 | 技術 | バージョン / 用途 |
| :--- | :--- | :--- |
| **フロントエンド** | **Nuxt 3** / **Vue 3** | SPA / SSR, TypeScript, Composition API |
| **スタイリング** | **Tailwind CSS** | ダークモード基調のモダン UI（カンバン / WBS / 2×2マトリクス） |
| **フロントエンドテスト** | **Vitest** + **@vue/test-utils** | Composable 単体テスト・状態管理テスト |
| **バックエンド API** | **Laravel 11** | PHP 8.3, RESTful API, Eloquent ORM |
| **バックエンドテスト** | **PHPUnit** / **Pest** | API Feature テスト（CRUD / フィルタリング / ブランチ） |
| **Web サーバー** | **Nginx** | リバースプロキシ (`:8080` 公開) |
| **データベース** | **MySQL 8.0** | データ永続化 (JST `+09:00` 設定済) |
| **インフラ** | **Docker** / **Docker Compose** | サーバーサイド完全コンテナ化 |

---

## 🔄 チームタスクから個人実行へのワークフロー

```mermaid
flowchart TD
    subgraph Team[🏢 チームプロジェクト管理]
        Methodology{開発手法の選択}
        Agile[🏃‍♂️ アジャイル / スクラム<br/>・スプリント管理<br/>・ストーリーポイント (pt)<br/>・カンバン: ToDo / 進行中 / レビュー / 完了]
        Waterfall[📊 ウォーターフォール<br/>・WBS 5工程: 要件定義/設計/開発/テスト/リリース<br/>・進捗率バー (%)<br/>・担当者アサイン]
        Methodology -->|切り替え| Agile
        Methodology -->|切り替え| Waterfall
    end

    subgraph Branch[⚡ ブレークダウン]
        Action[チームタスクの「⚡ 取り込む」をクリック<br/>・優先度（第1〜第4象限）を選択<br/>・個人用の締切を設定]
        Agile --> Action
        Waterfall --> Action
    end

    subgraph Personal[👤 個人の4象限マトリクス]
        Q1[第1象限: 緊急 × 重要<br/>すぐやる (DO)]
        Q2[第2象限: 非緊急 × 重要<br/>計画する (PLAN)]
        Q3[第3象限: 緊急 × 非重要<br/>委託する (DELEGATE)]
        Q4[第4象限: 非緊急 × 非重要<br/>削減する (ELIMINATE)]
        Action --> Q1
        Action --> Q2
        Action --> Q3
        Action --> Q4
    end

    classDef team fill:#1e1b4b,stroke:#6366f1,stroke-width:2px,color:#fff;
    classDef branch fill:#311042,stroke:#d946ef,stroke-width:2px,color:#fff;
    classDef matrix fill:#0f172a,stroke:#3b82f6,stroke-width:2px,color:#fff;
    class Agile,Waterfall team;
    class Action branch;
    class Q1,Q2,Q3,Q4 matrix;
```

---

## 🎯 画面構成と機能概要

### 1. 🏢 チームタスクビュー (Team Board)
- **🏃‍♂️ アジャイル（Agile / Scrum）**:
  - スプリント（Sprint 1 等）ごとにタスクを集計し、合計ストーリーポイント（pt）を表示。
  - 4列のカンバンボード（**未着手 / 進行中 / レビュー中 / 完了**）で直感的に進捗ステータスを更新可能。
  - 各カードに **「⚡ 取り込む」** ボタンを装備。
- **📊 ウォーターフォール（Waterfall / WBS）**:
  - 開発標準の 5 工程（**要件定義 → 基本・詳細設計 → 実装・開発 → テスト・検証 → リリース・移行**）ごとにタスクを分類。
  - 視覚的な **進捗率バー（0〜100%）** と担当者、期日を一元管理。

### 2. 👤 個人タスクビュー (My 4-Quadrant Matrix)
- アイゼンハワーマトリクス（緊急度 × 重要度）の 2×2 グリッド：
  - **第1象限（赤/Rose）**: すぐやる (DO) - 締切直前、緊急対応
  - **第2象限（青/Sky）**: 計画する (PLAN) - 中長期目標、価値創出
  - **第3象限（黄/Amber）**: 委託する (DELEGATE) - 割り込み対応
  - **第4象限（灰/Slate）**: 削減する (ELIMINATE) - 時間浪費、過剰タスク
- **チーム由来タスクの可視化**: チームから取り込んだタスクには `🏢 チーム由来: ○○` バッジが付き、元のチームタスクとの連動関係を維持。
- **内容編集モーダル**: タイトル、詳細メモ、象限、締切（JST日本時間）のインライン編集・解除に対応。

---

## 📁 ディレクトリ構成

```text
task-matrix/
├── .gitignore                         # Git 除外設定（node_modules, vendor, .env 等）
├── docker-compose.yml                 # サーバーサイドコンテナ構成 (app, web, db)
├── package.json                       # ルート開発用プロキシスクリプト (npm run dev 等)
├── README.md
├── docs/
│   └── google-calendar-sync-spec.md   # Google カレンダー連携 詳細設計書
├── docker/
│   ├── nginx/
│   │   └── default.conf               # Nginx 設定 (8080ポート、PHP-FPM プロキシ)
│   └── php/
│       └── Dockerfile                 # PHP 8.3-fpm + 拡張モジュール
├── backend/                           # Laravel 11 API
│   ├── .env.example
│   ├── artisan
│   ├── composer.json / composer.lock
│   ├── phpunit.xml                    # テスト用設定 (SQLite in-memory)
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── Controller.php     # ベースコントローラー
│   │   │   │   └── Api/
│   │   │   │       └── TaskController.php # CRUD & チーム取り込み API
│   │   │   ├── Requests/
│   │   │   │   └── TaskRequest.php    # バリデーション FormRequest
│   │   │   └── Resources/
│   │   │       └── TaskResource.php   # JST 日時整形 Resource
│   │   └── Models/
│   │       └── Task.php               # Eloquent モデル (teamTask リレーション)
│   ├── bootstrap/
│   │   ├── app.php                    # Laravel 11 ルーティング・ミドルウェア
│   │   └── providers.php
│   ├── config/
│   │   ├── app.php                    # タイムゾーン (Asia/Tokyo) 設定
│   │   └── cors.php                   # localhost:3000 許可設定
│   ├── database/migrations/
│   │   ├── 2024_01_01_000001_create_tasks_table.php
│   │   └── 2024_01_01_000002_add_team_and_methodology_to_tasks_table.php
│   ├── routes/
│   │   ├── api.php                    # /api/tasks & branch-to-personal
│   │   ├── web.php
│   │   └── console.php
│   └── tests/
│       ├── TestCase.php
│       └── Feature/
│           └── TaskApiTest.php        # API Feature テスト (8テスト)
└── frontend/                          # Nuxt 3 フロントエンド
    ├── app.vue
    ├── nuxt.config.ts                 # Tailwind CSS, runtimeConfig 設定
    ├── package.json / package-lock.json
    ├── vitest.config.ts               # Vitest 単体テスト設定
    ├── types/
    │   └── task.ts                    # TypeScript 型定義 (Task, Scope, Methodology 等)
    ├── composables/
    │   └── useTasks.ts                # API通信・手法グルーピング・状態管理
    ├── pages/
    │   └── index.vue                  # チーム (アジャイル/WBS) ⇄ 個人4象限 UI
    └── tests/
        └── composables/
            └── useTasks.spec.ts       # Composable 単体テスト (5テスト)
```

---

## 🚀 クイックスタート手順

### 1. サーバーサイド (Docker) の起動

```bash
# プロジェクトルートに移動
cd task-matrix

# コンテナのビルドとバックグラウンド起動
docker-compose up -d --build
```

初回セットアップ（Laravel の依存解決・キー生成・マイグレーション）：

```bash
# Composer パッケージのインストール
docker-compose exec app composer install --no-security-blocking

# 環境変数のコピーとアプリケーションキーの発行
docker-compose exec app cp -n .env.example .env
docker-compose exec app php artisan key:generate

# データベースマイグレーションの実行
docker-compose exec app php artisan migrate
```

- **API 動作確認 URL**: `http://localhost:8080/api/tasks`

---

### 2. フロントエンド (Nuxt 3) の起動

ルートディレクトリ直下から直接起動できます：

```bash
# ルートディレクトリから起動
npm run dev

# または frontend ディレクトリに移動して起動
cd frontend
npm install  # 初回のみ
npm run dev
```

- **ブラウザでアクセス**: `http://localhost:3000`

---

## 📡 RESTful API 仕様

| メソッド | エンドポイント | 説明 | パラメータ / ボディ |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/tasks` | タスク一覧取得 | `?task_scope=team/personal`<br>`?methodology=agile/waterfall/matrix`<br>`?priority_type=...`<br>`?status=todo/in_progress/review/done` |
| `POST` | `/api/tasks` | 新規タスク作成 | `{ task_scope, methodology, title, description?, assigned_to?, priority_type?, status?, agile_sprint?, agile_story_points?, waterfall_phase?, progress_rate?, due_date? }` |
| `GET` | `/api/tasks/{id}` | タスク詳細取得 | - |
| `PUT` | `/api/tasks/{id}` | タスク内容・進捗更新 | 更新したいフィールドオブジェクト |
| `DELETE` | `/api/tasks/{id}` | タスク削除 | - |
| `POST` | `/api/tasks/{id}/branch-to-personal` | **チームタスクを個人の4象限に取り込む** | `{ priority_type, title?, description?, due_date? }` |

---

## 🧪 テストの実行方法

### 1. バックエンド API テスト (Laravel / PHPUnit)
```bash
docker-compose exec app php artisan test
```
- 全件取得、象限フィルター、完了フラグ絞込、CRUD 処理、バリデーションエラー等の 8 テスト・47 アサーションがすべて自動実行されます。

### 2. フロントエンド単体テスト (Nuxt 3 / Vitest)
```bash
# ルートから実行
npm run test

# または frontend ディレクトリで実行
cd frontend
npm run test
```
- チーム/個人のタスク分離、アジャイルカンバン列分類、ウォーターフォールフェーズ分類、個人タスクへの取り込み（`branchToPersonal`）等の 5 テストが実行されます。

---

## 🧭 将来の拡張性

### 1. Google Calendar API 連携
- [docs/google-calendar-sync-spec.md](docs/google-calendar-sync-spec.md) に詳細設計書を配備済み。
- 4象限カラーと Google Calendar の `colorId`（1〜11）を一致させた非同期キュー同期アーキテクチャを定義しています。

### 2. AWS 本番環境展開
- **バックエンド**: `docker/php/Dockerfile` をマルチステージビルド化して AWS ECR にプッシュし、AWS App Runner または ECS Fargate で稼働。DB は Amazon RDS (Aurora MySQL) へ接続。
- **フロントエンド**: AWS Amplify または S3 + CloudFront / Vercel に静的・SSR ホスティングし、環境変数 `NUXT_PUBLIC_API_BASE` で API 接続先を指定。