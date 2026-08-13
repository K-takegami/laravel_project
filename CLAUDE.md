# CLAUDE.md

このファイルは、このリポジトリでコードを扱う際に Claude Code (claude.ai/code) が参照するガイダンスです。

## プロジェクトの状態

これは標準のLaravel 13スケルトン（`laravel/laravel`）です。PHP 8.3を使用し、フレームワークのデフォルト以外に独自のドメインコードはまだ追加されていません。ルートは1つ（`/` → `welcome` ビュー）、デフォルトの `User` モデル／マイグレーション／ファクトリ、そして2つのサンプルテストのみが存在します。アーキテクチャの規約はまだ確立されていないものとして扱ってください。最初の実機能を追加する際は、従うべき既存パターンがないため、Laravelの標準的な規約（`app/Models`、`app/Http/Controllers`、`app/Providers` など）に従ってください。

`.env` のアプリ名は `Raizbird` です。デフォルトのDB接続はMySQL（`DB_DATABASE=laravel`）ですが、テストはインメモリのSQLiteに対して実行されます（下記参照）。

## コマンド

すべてのコマンドはリポジトリのルートから実行してください。

### セットアップ
```bash
composer install
cp .env.example .env   # .env が存在しない場合
php artisan key:generate
php artisan migrate
npm install
```

### 開発サーバー
```bash
composer dev
```
`php artisan serve`、`php artisan queue:listen`、`php artisan pail`（ログビューア）、`npm run dev`（Vite）を同時に実行します。

個別に実行する場合:
```bash
php artisan serve
npm run dev          # Vite開発サーバー
npm run build         # Vite本番ビルド
```

### テスト
```bash
composer test
# または直接:
php artisan test
```
特定のテストファイル／メソッドのみ実行する場合:
```bash
php artisan test tests/Feature/ExampleTest.php
php artisan test --filter=test_method_name
```
テストはPHPUnit（`phpunit.xml`）を使用し、インメモリSQLite（`DB_CONNECTION=sqlite`、`DB_DATABASE=:memory:`）、配列キャッシュ／セッション、同期キューに対して実行されます。これらは `.env.testing` ではなく `phpunit.xml` 内の `<php>` 環境変数の上書き設定によってすべて構成されています。

### Lint
```bash
vendor/bin/pint        # Laravel Pint（PHPコードスタイル、PSR-12ベース）
vendor/bin/pint --test # チェックのみ、変更は行わない
```

## アーキテクチャに関する補足

- 標準的なLaravelのディレクトリ構成。PSR-4オートロードのルート: `App\` → `app/`、`Database\Factories\` → `database/factories/`、`Database\Seeders\` → `database/seeders/`、`Tests\` → `tests/`。
- ルーティング: `routes/web.php`（HTTP）と `routes/console.php`（Artisanクロージャ）。`routes/api.php` はまだ存在しません。
- フロントエンドビルドはVite + Tailwind CSS v4（`vite.config.js`、`resources/`）。React/VueなどのJSフレームワークは組み込まれていません。
- DBマイグレーションは現時点でフレームワークのデフォルト（`users`、`cache`、`jobs` テーブル）のみをカバーしています。

### フロントエンドアセット

CSS / JS のソースは `resources/css/` と `resources/js/` の下に `admin/`、`user/`、`common/` で分割。Blade テンプレートは `resources/views/`。

## 命名規則

| 対象 | 規則 |
|------|------|
| PHP クラス名 | PascalCase |
| PHP メソッド名 | camelCase |
| PHP 変数名 | snake_case |
| JS クラス名 | PascalCase |
| JS 関数名 | camelCase |
| JS 変数名 | snake_case |
| CSS クラス名 | kebab-case |

## コードレビューコメントの接頭辞

- `[must]` — 必須修正（セキュリティ・バグ・重大な設計問題）
- `[recommend]` — 推奨修正（パフォーマンス・可読性の大幅改善）
- `[nits]` — 軽微な指摘（コードスタイル・タイポ等）

## Claude Code への必須ルール

- 明示的に指示がない限り、ファイルを変更しないこと。
- 明確に依頼されない限り、既存コードのリファクタリングをしないこと。
- 大規模な改善よりも、最小限・局所的な変更を優先すること。
- コードの綺麗さよりも、安定性と既存の動作を優先すること。

## 変更提案の要件

コードを変更する前に必ず以下を説明すること:
- 何を変更するか
- なぜ変更が必要か
- 潜在的なリスクや副作用

明示的な承認を得てから作業を進めること。

## 変更禁止エリア

以下のエリアは明示的な指示がない限り変更禁止:
- 認証ミドルウェア（`app/Http/Middleware/`）および認証ガード
- 二要素認証ロジック
- 本番環境設定

## セキュリティルール

- シークレット・API キー・認証情報を要求・出力しないこと。
- 個人情報をログ出力・print しないこと。
- 開発環境でも本番相当の制約を前提として作業すること。

## コストへの配慮

- 回答は簡潔に保つこと。
- 不必要なコードブロックの繰り返しを避けること。
- 可能であればフル実装よりも説明を優先すること。
