# backend

このディレクトリは、費用管理アプリの **Laravel バックエンド** です。

- ルートの README は「プロジェクト全体の説明・導入」を扱います: `../README.md`
- ここでは **backend 固有の実行・品質チェック・DB/シーディングの補足** をまとめます

---

## 目次

- [よく使うコマンド](#よく使うコマンド)
- [DB とシーディング](#db-とシーディング)
- [ルームへのアクセス（query_key）](#ルームへのアクセスquery_key)
- [データ整合性チェック用テスト](#データ整合性チェック用テスト)
- [テスト用 DB と確認用 DB の違い](#テスト用-db-と確認用-db-の違い)

---

## よく使うコマンド

### セットアップ / 起動（ローカル）

```bash
composer run setup
composer run dev
```

`composer run dev` は以下をまとめて起動します。

- Laravel 開発サーバー（`php artisan serve`）
- Queue（`php artisan queue:listen`）
- ログ監視（`php artisan pail`）
- Vite（`npm run dev`）

### マイグレーション / シーディング

```bash
php artisan migrate
php artisan migrate:fresh --seed
```

（Docker/Sail 利用時は `./vendor/bin/sail artisan ...` で実行してください）

### テスト / 品質チェック

```bash
composer run test
composer run phpstan
composer run twig:lint
composer run lint:lines
```

Twig の自動整形:

```bash
composer run twig:fix
```

---

## DB とシーディング

### `t_items.room_id` の追加と関連

`t_items` に `room_id`（NOT NULL）を追加し、`t_rooms.id` を参照します。

- マイグレーション: `database/migrations/2026_03_10_000009_add_room_id_to_t_items_table.php`
- 目的: **Item が必ず Room に属する** ことを DB で保証する

### シーディングの流れ（Room → Member → Item）

- **RoomSeeder**: ルーム作成（`password_plan` をログ/コンソールに出力）
- **MemberSeeder**: ルームごとにメンバー作成（`room_id` を固定）
- **ItemSeeder**: メンバー所属の `room_id` をアイテムに引き継いで作成（`room_id` が NOT NULL のため必須）

### よくあるエラーと対処

- `SQLSTATE[23502]: not null violation ... column "room_id" ...`
  - `ItemSeeder` が `room_id` を指定しているか
  - `t_members.room_id` が `NULL` になっていないか
  - 必要に応じて `php artisan migrate:fresh --seed`

---

## ルームへのアクセス（query_key）

ルーム詳細（`/rooms/{id}`）および清算ページへは、URL に **query_key**（DB の `t_rooms.password_plan`）が必要です。

- 例: `http://localhost:8000/rooms/1?query_key=abc12def`
- query_key がない・誤っている場合は「アクセスキー入力」画面が表示されます

シーディングで作成したルームにアクセスしたい場合は、**RoomSeeder 実行時に出力される `password_plan`** を `?query_key=...` として付与してください（`storage/logs/laravel.log` にも出力されます）。

---

## データ整合性チェック用テスト

- **目的**: Seeder / Factory 変更時に、部屋・メンバー・アイテムの紐づきが崩れたら検知する
- **テストファイル**: `tests/Feature/DataIntegrityTest.php`
- **検証内容（例）**
  - `t_items.room_id` と、`payer_id` に紐づく `t_members.room_id` が一致する
  - `t_item_participants.member_id` が、対応 `item_id` と同一 `room_id` に属する

実行例:

```bash
docker compose exec laravel.test php artisan test --filter=DataIntegrityTest
```

---

## テスト用 DB と確認用 DB の違い

`DataIntegrityTest` は `RefreshDatabase` を使い、テスト時は `.env.testing` の接続先でマイグレーション + シーディング（`$this->seed()`）が行われます。  
一方、`psql` などで確認しているのは通常の接続先（開発用 DB）であるため、**テスト実行後に開発用 DB を見ても 0 件に見える**ことがあります。

開発用 DB 側にもシーディング結果を反映したい場合:

```bash
docker compose exec laravel.test php artisan migrate:fresh --seed
```
