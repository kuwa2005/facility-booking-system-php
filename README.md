# facility-booking-system-php

`facility-booking-system` (Node.js/TypeScript) を、共有レンタルサーバーで動作可能な PHP/MySQL 構成へ移植するプロジェクトです。

## 開発方針

- まずは **業務で必要な機能を優先** して移植する（MVP -> 実運用）
- Node 版の画面構成・業務フロー・データモデルを可能な限り踏襲する
- 開発期間中は、開発/本番で同一DBを利用し、切替時にDB分離する
- 不具合・要望・移植タスクはすべて GitHub Issue で管理する
- 1 Issue = 1 目的の小さい単位で実装し、PR/コミットと紐づける

## 現在の実装範囲（PHP版）

- 予約申請フォーム（代表者・連絡先・利用枠・延長・空調）
- 料金の基本計算（入場料倍率を含む）
- 管理ログイン
- 管理画面（申請一覧、状態更新、支払状態更新）
- 初期化スクリプトによるテーブル作成

## 移植ロードマップ

以下は元システム（Node版）から優先的に移植する機能・画面の一覧です。  
各項目は Issue 化し、ラベルで管理します（`type:*`, `area:*`, `priority:*`）。

### フェーズ1: 基本機能の完成

- [ ] ユーザー会員登録/ログイン/パスワード再設定（画面: `auth/*`）
- [ ] マイページ（プロフィール編集、パスワード変更）
- [ ] マイ予約一覧/詳細/キャンセル
- [ ] 部屋一覧/部屋詳細/空き状況確認
- [ ] 休館日考慮の予約不可判定

### フェーズ2: 施設運用機能

- [ ] 設備マスタ管理（追加/編集/停止）
- [ ] 設備選択UIと設備料金計算
- [ ] 職員ダッシュボード（件数・売上サマリ）
- [ ] 職員向け予約詳細管理（メモ、支払、キャンセル）
- [ ] 部屋別・期間別レポート

### フェーズ3: 拡張機能

- [ ] お知らせ機能
- [ ] メッセージ機能（ユーザー↔職員）
- [ ] レビュー機能
- [ ] 代理予約機能
- [ ] 監査ログ/ユーザー行動ログ

## 元システムの主要画面（移植対象）

- 公開: `public/index`, `public/rooms`, `public/room-detail`, `public/availability`, `public/contact`
- 認証: `auth/register`, `auth/login`, `auth/forgot-password`
- ユーザー: `user/mypage`, `user/reservations`, `user/reservation-detail`, `user/messages`, `user/favorites`
- 職員: `staff/dashboard`, `staff/reservations`, `staff/rooms`, `staff/equipment`, `staff/usages`, `staff/settings`

## Issue 運用ルール

- 新機能: `type:feature`
- 不具合: `type:bug`
- 移植タスク: `type:migration`
- ドキュメント: `type:docs`
- 緊急度: `priority:p0` ~ `priority:p3`
- 対象領域: `area:public` / `area:user` / `area:staff` / `area:db` / `area:auth` など

Issue テンプレートは `.github/ISSUE_TEMPLATE/` に配置しています。  
ラベル定義は `.github/labels.yml` で管理し、GitHub Actions で同期します。
