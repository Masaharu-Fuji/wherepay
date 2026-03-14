<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## このバックエンドについて

このディレクトリは Laravel をベースにしたアプリケーションのバックエンドです。  
開発・検証時に最低限必要なコマンドや、DB 初期データ投入の手順をここにまとめます。

### 開発用コマンド

- アプリケーションコンテナ内でマイグレーションを実行

```bash
docker compose exec laravel.test php artisan migrate
```

- テーブルを作り直してシーディングまで一括実行

```bash
docker compose exec laravel.test php artisan migrate:fresh --seed
```

- 静的解析（PHPStan）の実行  
  コンテナ外（backend ディレクトリ）で実行します。

```bash
composer phpstan
```

## DB 構成とシーディング

### `t_items.room_id` の追加と関連

- テーブル: `t_items`
- 追加カラム: `room_id` (`unsignedBigInteger`, NOT NULL)
- 外部キー: `t_rooms.id`

マイグレーション:

```14:22:backend/database/migrations/2026_03_10_000009_add_room_id_to_t_items_table.php
    public function up(): void
    {
        Schema::table('t_items', function (Blueprint $table) {
            $table->unsignedBigInteger('room_id')->after('id');

            $table->foreign('room_id')
                ->references('id')
                ->on('t_rooms')
                ->onUpdate('no action')
                ->onDelete('no action');
        });
    }
```

### 関連モデルの概要

- `Room` (`t_rooms`)
  - `items()` で `hasMany(Item::class, 'room_id')`
- `Member` (`t_members`)
  - カラム `room_id`
  - `room()` で `belongsTo(Room::class, 'room_id')`
- `Item` (`t_items`)
  - `fillable` に `room_id` を含める

### ルームへのアクセス（query_key）

ルーム詳細（`/rooms/{id}`）および清算ページへは、URL に **query_key**（DB の `t_rooms.password_plan`）を付与しないとアクセスできません。

- **アクセス可能な URL の例**: `http://localhost/rooms/1?query_key=abc12def`
- query_key がない・誤っている場合は「アクセスキー入力」画面が表示され、正しいキーを入力するとルームに入れます。
- ルーム作成直後のリダイレクトや「URLをコピー」では、最初から query_key 付きの URL が使われます。

シーディングで作成したルームにブラウザでアクセスするには、各ルームの `password_plan` が必要です。**RoomSeeder 実行時にコンソールおよび `storage/logs/laravel.log` に各ルームの `password_plan` が出力される**ので、その値をコピーして `?query_key=...` として付けてください。

### シーディングの流れ（Room → Member → Item）

1. **RoomSeeder**

既存の `RoomFactory` を使って複数の部屋を作成します。作成した各ルームの **room_id / room_name / password_plan** を `Log::info()` でログに書き出し、さらに `php artisan db:seed` 実行時にはコンソールにも `password_plan` を表示します。ローカルでシード済みルームにアクセスする際の query_key として利用してください。

2. **MemberSeeder**

各 `Room` ごとにメンバーを 4 人ずつ作成し、`room_id` を固定します。

```13:20:backend/database/seeders/MemberSeeder.php
    public function run(): void
    {
        // 既存の Room に対してメンバーを作成
        Room::all()->each(function (Room $room) {
            Member::factory()
                ->count(4)
                ->create([
                    'room_id' => $room->id,
                ]);
        });
    }
```

3. **ItemSeeder**

各メンバーに対して、そのメンバーが所属している `room_id` をアイテムにも引き継いで作成します。  
`room_id` が NOT NULL のため、この指定が必須です。

```11:21:backend/database/seeders/ItemSeeder.php
    public function run(): void
    {
        // 既存メンバーごとにアイテムを作成
        Member::all()->each(function (Member $member) {
            Item::factory()
                ->count(5)
                ->create([
                    'room_id' => $member->room_id,
                    'payer_id' => $member->id,
                ]);
        });
    }
```

### よくあるエラーと対処

- `SQLSTATE[23502]: not null violation: 7 ERROR:  null value in column "room_id" of relation "t_items"`  
  - `ItemSeeder` で `room_id` を指定しているか確認する
  - `t_members` の `room_id` が `NULL` になっていないか確認する
  - 必要に応じて `migrate:fresh --seed` を実行してテーブル・初期データを作り直す

### データ整合性チェック用テスト

- **目的**: Seeder / Factory を変更した際に、**部屋とメンバーの整合性が 1 件でも崩れたら検知する** ためのテストです。
- **テストファイル**: `tests/Feature/DataIntegrityTest.php`
- **検証内容**:
  - `t_items.room_id` と、`payer_id` に紐づく `t_members.room_id` が常に一致していること
  - `t_item_participants.member_id` が、対応する `item_id` の属する `room_id` のメンバーであること
- **実行例**:

```bash
docker compose exec laravel.test php artisan test --filter=DataIntegrityTest
```

このテストが失敗した場合は、Seeder / Factory のロジックにより「別の部屋のメンバーが紐づいていないか」を確認してください。

### テスト実行時の DB と `psql` の結果の違いについて

- **`DataIntegrityTest` では `RefreshDatabase` トレイトを使用しており、テスト時は `.env.testing` で定義された「テスト用 DB」に対してマイグレーション + シーディング (`$this->seed()`) が実行されます。**
- 一方で、次のようにコンテナ内から `psql` で確認しているのは、通常の接続先（例: `laravel` データベース）です。

```bash
docker exec -it backend-pgsql-1 psql -U sail -d laravel -c "SELECT * FROM t_rooms;"
```

- そのため、**テスト実行後に上記コマンドで `t_rooms` を確認しても 0 件 `(0 rows)` になることがありますが、これは「テスト用 DB と本番/開発用 DB が別」であるためです。**
- 本番/開発用 DB 側にもシーディング結果を反映させたい場合は、テストではなく通常のコマンドで以下のように実行してください。

```bash
docker compose exec laravel.test php artisan migrate:fresh --seed
```

その後に `psql` で `t_rooms` を確認すると、`RoomSeeder` によるデータが投入されていることを確認できます。

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
