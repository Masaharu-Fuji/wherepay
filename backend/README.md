# backend

このディレクトリは、費用管理アプリの **Laravel バックエンド** です。

- ルートの README は「プロジェクト全体の説明・導入」を扱います: `../README.md`
- ここでは **backend 固有の実行・品質チェック・DB/シーディングの補足** をまとめます

---

## 目次

- [よく使うコマンド](#よく使うコマンド)
- [デプロイ（Fly.io）](#デプロイflyio)
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

## デプロイ（Fly.io）

本番は **Fly.io** です。手順、公式ドキュメントの読み方、月額 $5 未満で支払わない条件は、リポジトリ直下の [README「デプロイ」](../README.md#デプロイ) にあります。

- 設定: `fly.toml`、`Dockerfile`、`.fly/`
- DB: Volume `wherepay_data` の `/data/database.sqlite`
- リージョン: `nrt`（東京）。マシンは 1 台のままにする
- 公開と更新: このディレクトリで `fly deploy`
- 本番で `/data` が無いときは `.fly/scripts/migrate.sh` が起動を止める

`fly launch` は使いません。上書きを聞かれたら No と答えます。`render.yaml` は置かず、SQLite を Postgres などのマネージド DB に移すのも、明示の依頼があるまで行いません。

---

## DB とシーディング

### テーブル名

ドメインテーブルは Eloquent の既定名です。モデルの `$table` は指定していません。

| モデル | テーブル |
| --- | --- |
| `Room` | `rooms` |
| `Member` | `members` |
| `Item` | `items` |
| `ItemParticipant` | `item_participants` |
| `Location` | `locations` |
| `Settlement` | `settlements` |
| `Map` | `maps` |
| `ItemCategory` | `item_categories` |

作成時の `t_` / `m_` 名は、旧テーブル名のまま次の順で桁を変えてから改名します。適用済みの DB は `php artisan migrate` で追従できます。

1. `database/migrations/2026_11_20_000011_limit_item_memo_length.php`（`t_items.memo` を 255 文字）
2. `database/migrations/2026_11_20_000012_shorten_room_name_on_t_rooms_table.php`（`t_rooms.room_name` を 40 文字）
3. `database/migrations/2026_11_20_000013_rename_domain_tables_to_eloquent_convention.php`（Eloquent の既定名へ改名）

### `items.room_id` の追加と関連

`items.room_id`（NOT NULL）は `rooms.id` を参照します。追加自体は改名前のマイグレーションです。

- マイグレーション: `database/migrations/2026_03_10_000009_add_room_id_to_t_items_table.php`
- 目的: **Item が必ず Room に属する** ことを DB で保証する

### シーディングの流れ（Room → Member → Item）

- **RoomSeeder**: ルーム作成（`password_plan` をログ/コンソールに出力）
- **MemberSeeder**: ルームごとにメンバー作成（`room_id` を固定）
- **ItemSeeder**: メンバー所属の `room_id` をアイテムに引き継いで作成（`room_id` が NOT NULL のため必須）

### よくあるエラーと対処

- `SQLSTATE[23502]: not null violation ... column "room_id" ...`
  - `ItemSeeder` が `room_id` を指定しているか
  - `members.room_id` が `NULL` になっていないか
  - 必要に応じて `php artisan migrate:fresh --seed`

---

## ルームへのアクセス（query_key）

ルーム詳細（`/rooms/{id}`）および清算ページへは、URL に **query_key**（DB の `rooms.password_plan`）が必要です。

- 例: `http://localhost:8000/rooms/1?query_key=abc12def`
- query_key がない・誤っている場合は「アクセスキー入力」画面が表示されます

シーディングで作成したルームにアクセスしたい場合は、**RoomSeeder 実行時に出力される `password_plan`** を `?query_key=...` として付与してください（`storage/logs/laravel.log` にも出力されます）。

---

## データ整合性チェック用テスト

- **目的**: Seeder / Factory 変更時に、部屋・メンバー・アイテムの紐づきが崩れたら検知する
- **テストファイル**: `tests/Feature/DataIntegrityTest.php`
- **検証内容（例）**
  - `items.room_id` と、`payer_id` に紐づく `members.room_id` が一致する
  - `item_participants.member_id` が、対応 `item_id` と同一 `room_id` に属する

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
