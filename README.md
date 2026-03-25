# 旅行・グループ活動 費用管理アプリ

旅行やイベントなどのグループ活動で発生する支出を、**「誰が・何に・いくら使ったか」** で記録し、最後に自動で清算できる Web アプリです。  
支出データには位置情報も紐づけられるため、費用管理だけでなく活動ログとしても活用できます。

---

## 目次

- [アプリ概要](#アプリ概要)
- [主な価値](#主な価値)
- [技術スタック](#技術スタック)
- [開発環境セットアップ](#開発環境セットアップ)
- [実行方法](#実行方法)
- [現在の実装機能](#現在の実装機能)
- [ディレクトリ構成](#ディレクトリ構成)
- [利用上の注意](#利用上の注意)
- [今後の拡張アイデア](#今後の拡張アイデア)
- [ドキュメント目次](#ドキュメント目次)

---

## アプリ概要

このアプリケーションは、**旅行や一日イベントなどのグループ活動における「お金の管理」と「清算」を、位置情報と紐づけて行えるアプリ**です。

- 現地での立替をその場で登録
- 活動終了時に公平な支払い額を自動計算
- 支出履歴を場所と一緒に振り返り

計算やメモの手間を減らし、会計担当者だけに負担が偏らない運用を目指しています。

---

## 主な価値

- **その場でサッと支出登録**
  - 項目（交通費・食事・観光など）、金額、支払者、対象者をシンプルに入力
  - 位置情報を併せて保存し、支出の文脈を残せる
- **位置情報と一緒に思い出を可視化**
  - どこで何に使ったかを一覧で確認
  - 訪問順のルートを Google Maps で確認可能
- **最後はワンタップで清算**
  - 誰が誰にいくら支払うべきかを自動計算
  - おつり計算まで含めて現地で完結
- **インストール不要**
  - ブラウザのみで利用可能
  - スマホ / タブレット / PC から同一ルームに参加可能

---

## 技術スタック

- **バックエンド**
  - Laravel 12 (PHP 8.2+)
  - Twig (`rcrowe/twigbridge`)
- **フロントエンド**
  - Vite
  - JavaScript
  - Tailwind CSS
- **補助ツール**
  - PHPUnit (テスト)
  - PHPStan / Larastan (静的解析)
  - Twig CS Fixer / Prettier (整形)

`backend/` 配下に Laravel アプリを配置し、Vite によるアセットビルドを組み合わせる構成です。

---

## 開発環境セットアップ

### 前提

- PHP 8.2 以上
- Composer
- Node.js / npm
- （任意）Docker（Laravel Sail で動かす場合）

### 初期構築（推奨・ローカルで動かす）

リポジトリルートから以下を実行します。

```bash
cd backend
composer run setup
```

`composer run setup` では以下が自動実行されます。

- 依存パッケージのインストール（Composer / npm）
- `.env` 作成
- アプリケーションキー生成
- マイグレーション実行
- フロントエンドビルド

#### DB について（ローカル実行）

このプロジェクトはデフォルトで SQLite を想定しています（`DB_CONNECTION=sqlite`）。

- **SQLite で動かす場合**: `backend/database/database.sqlite` が必要です（存在しない場合は作成してください）。

```bash
cd backend
mkdir -p database
touch database/database.sqlite
```

- **PostgreSQL 等で動かす場合**: `.env` の `DB_CONNECTION` / `DB_HOST` / `DB_DATABASE` などを合わせてください。

### Docker（Laravel Sail）で動かす（任意）

Docker を使う場合は `backend/compose.yaml`（Sail）を利用できます（PostgreSQL コンテナが起動します）。

```bash
cd backend
composer install
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

---

## 実行方法

### 開発モード（推奨）

```bash
cd backend
composer run dev
```

起動内容:

- Laravel 開発サーバー
- キューリスナー
- ログ監視
- Vite 開発サーバー

補足:

- `php artisan serve` の既定ポートは **8000** です（環境により変更される場合があります）。
- Vite は既定で **5173** を使用します。

### テスト / 品質チェック

```bash
cd backend
composer run test
composer run phpstan
composer run twig:lint
composer run lint:lines
```

---

## 現在の実装機能

### 1) ルーム作成画面（`/`）

- グループ名入力によるルーム作成
- 入力バリデーションエラー表示

### 2) アクセスキー入力画面（`/rooms/{room}` ※キー不一致時）

- `query_key` を入力してルームへ入室
- ルーム選択（作成）画面へ戻る

### 3) ルーム詳細画面（`/rooms/{room}?query_key=...`）

- メンバー追加・メンバー一覧表示（人数表示つき）
- 品目追加（品目名、金額、支払者、対象者[複数選択]、位置情報取得）
- 品目一覧表示（品目名、支払者、対象者、金額、作成日時、1人あたり金額、位置情報ありバッジ）
- 品目削除
- ルーム URL 表示・コピー
- 品目一覧 CSV 出力
- 清算結果画面への遷移（条件: メンバー・品目が存在）
- 支払いランキング表示（開閉式）

### 4) 清算結果画面（`/rooms/{room}/settlement?query_key=...`）

- 清算サマリー表示（合計金額・メンバー別負担）
- 支払い計算結果表示（誰が誰にいくら払うか）
- おつり計算（メンバー別所持金入力、不足/おつり表示、清算完了判定）
- 清算完了メンバー一覧表示
- 全員完了時の「清算完了」表示
- 訪れた場所一覧表示（各地点の地図リンク、全体経路表示リンク）
- 清算結果 CSV 出力
- 清算を確定して保存（`Settlement` レコード作成）
- ルーム詳細へ戻る

---

## ディレクトリ構成

```text
app_portfolio/
├── README.md
├── backend/       # Laravel 本体
│   ├── app/
│   ├── resources/views/   # Twig テンプレート
│   ├── routes/
│   ├── database/
│   └── ...
└── doc/           # 画面遷移や画像ドキュメント
    ├── workflow.md
    └── image/
```

---

## 利用上の注意

- 位置情報を使う機能では、ブラウザ側で位置情報利用の許可が必要です。
- ルーム参加には `query_key` が必要です。URL 共有時は取り扱いに注意してください。
- 支出項目（品目）は、品目編集ページで **一部の編集** ができます（品目名・対象者・位置情報）。
- ただし **金額・支払者は編集できません**（必要に応じて削除して再登録で対応）。
- 「訪れた場所」の地図リンクは Google Maps（URL）を利用しています。

---

## 今後の拡張アイデア

- 履歴検索 / 集計ビュー（カテゴリ別・日別）

---

## ドキュメント目次

### 概要・機能一覧

- [README（このページ）](README.md)
- [アプリの進行](doc/workflow.md)
- [ER 図](doc/er.md)

### 画面別ガイド（workflow）

- [トップページ](doc/workflow.md#トップページ)
  - 画像: [top](doc/image/top.png)
- [セキュリティページ](doc/workflow.md#セキュリティページ)
  - 画像: [security](doc/image/security.png)
- [登録ページ](doc/workflow.md#登録ページ)
  - 画像: [room](doc/image/room.png)
- [清算ページ](doc/workflow.md#清算ページ)
  - 画像: [settlement](doc/image/settlement.png)
  - 詳細: [支払い清算](doc/workflow.md#支払い清算)
  - 詳細: [おつり計算](doc/workflow.md#おつり計算)
  - 詳細: [訪れた場所](doc/workflow.md#訪れた場所)
  - 画像: [おつり計算（calculator）](doc/image/calculator.png)
  - 画像: [訪れた場所（visited）](doc/image/visited.png)
