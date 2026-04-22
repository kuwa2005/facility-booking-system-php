# Migration Issue Seed

GitHub API クライアントがない環境でも、Web UI からこの一覧を順に Issue 登録すれば移植計画を運用できます。

## 登録ルール

- タイトル先頭に `[Migration]` を付与
- ラベルは `type:migration` + `area:*` + `priority:*` + `status:ready`
- 本文には「元機能（Node版）」「受け入れ条件」を記載

## 初期登録推奨 Issue

1. `[Migration] 会員登録・ログイン・パスワード再設定を移植`
   - labels: `type:migration`, `area:auth`, `priority:p0`, `status:ready`
2. `[Migration] マイページ（プロフィール編集・パスワード変更）を移植`
   - labels: `type:migration`, `area:user`, `priority:p1`, `status:ready`
3. `[Migration] マイ予約一覧/詳細/キャンセルを移植`
   - labels: `type:migration`, `area:user`, `priority:p0`, `status:ready`
4. `[Migration] 部屋一覧/部屋詳細/空き状況画面を移植`
   - labels: `type:migration`, `area:public`, `priority:p1`, `status:ready`
5. `[Migration] 設備管理と設備料金計算を移植`
   - labels: `type:migration`, `area:staff`, `area:db`, `priority:p1`, `status:ready`
6. `[Migration] 休館日管理と予約不可判定を移植`
   - labels: `type:migration`, `area:staff`, `area:public`, `priority:p1`, `status:ready`
7. `[Migration] 職員ダッシュボード（件数・売上）を移植`
   - labels: `type:migration`, `area:staff`, `priority:p2`, `status:ready`
8. `[Migration] お知らせ機能を移植`
   - labels: `type:migration`, `area:staff`, `area:user`, `priority:p2`, `status:ready`
9. `[Migration] メッセージ機能（ユーザー↔職員）を移植`
   - labels: `type:migration`, `area:user`, `area:staff`, `priority:p2`, `status:ready`
10. `[Migration] レビュー機能を移植`
    - labels: `type:migration`, `area:user`, `area:public`, `priority:p3`, `status:ready`

## 不具合・要望運用

- 不具合は `Bug Report` テンプレートから起票（`type:bug`）
- 要望は `Feature Request` テンプレートから起票（`type:feature`）
- 受理後に `status:triage` -> `status:ready` -> `status:in-progress` と遷移
