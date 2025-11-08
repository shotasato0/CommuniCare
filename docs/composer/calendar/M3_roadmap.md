# 📅 M3: カレンダー UI 実装ロードマップ

## 📋 概要

M3 フェーズでは、Vue.js 3 + Inertia.js を使用したカレンダー UI を実装します。M1/M2 で実装したスケジュール API を活用し、視覚的で使いやすいカレンダーインターフェースを提供します。

## 🎯 目標

-   **月間カレンダー表示**: メインのカレンダー表示機能
-   **週間カレンダー表示**: 詳細なスケジュール管理
-   **日間カレンダー表示**: 当日の詳細タイムライン
-   **スケジュール操作**: 作成・更新・削除の UI 実装
-   **レスポンシブデザイン**: モバイル・タブレット対応
-   **リアルタイム更新**: Inertia.js によるデータ同期

## 🏗️ 技術スタック

### フロントエンド

-   **Vue.js 3.4+**: Composition API を使用
-   **Inertia.js 2.0+**: サーバーサイドとの連携
-   **Tailwind CSS 3.2+**: スタイリング
-   **dayjs**: 日付操作（既存プロジェクトで使用中）
-   **vuedraggable**: ドラッグ&ドロップ機能（既存プロジェクトで使用中）

### カレンダーライブラリ候補

-   **vue-calendar-heatmap**: シンプルなカレンダー表示
-   **@fullcalendar/vue3**: 高機能なカレンダーライブラリ（推奨）
-   **vue-calendar**: 軽量なカレンダーコンポーネント

**推奨**: `@fullcalendar/vue3` - 月間・週間・日間ビュー、ドラッグ&ドロップ、イベント編集などの機能が豊富

## 📁 ディレクトリ構造

```
resources/js/
├── Pages/
│   └── Calendar/
│       ├── Index.vue              # メインカレンダーページ（月間ビュー）
│       ├── WeekView.vue           # 週間ビュー
│       └── DayView.vue            # 日間ビュー
├── Components/
│   └── Calendar/
│       ├── CalendarToolbar.vue    # カレンダーツールバー（月移動、ビュー切り替え）
│       ├── ScheduleEvent.vue     # スケジュールイベント表示コンポーネント
│       ├── ScheduleForm.vue       # スケジュール作成・編集フォーム
│       ├── ScheduleModal.vue      # スケジュール詳細モーダル
│       ├── MonthStats.vue         # 月間統計情報
│       └── ResidentSidebar.vue    # 左サイドバー（利用者リスト、ドラッグ&ドロップ対応）
└── Utils/
    └── calendarUtils.js           # カレンダー関連ユーティリティ関数
```

## 🚀 実装フェーズ

### Phase 1: 基盤実装（M3-1）

#### 1.1 依存関係の追加

```bash
npm install @fullcalendar/vue3 @fullcalendar/core @fullcalendar/daygrid @fullcalendar/timegrid @fullcalendar/interaction
```

#### 1.2 バックエンド実装

-   **CalendarController::index()**: カレンダーページ用のデータ取得

    -   現在の月のスケジュール一覧
    -   利用者一覧
    -   スケジュール種別一覧
    -   月間統計情報

-   **ルート追加**: `routes/tenant.php`
    ```php
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    ```

#### 1.3 フロントエンド実装

-   **Calendar/Index.vue**: メインカレンダーページ

    -   FullCalendar の月間ビュー表示
    -   スケジュールイベントの表示
    -   日本語化対応

-   **ナビゲーションメニューへのリンク追加**: `resources/js/Layouts/AuthenticatedLayout.vue`
    -   Primary Navigation Menu 内にカレンダーリンクを追加
    -   利用者（Residents）リンクと管理者（Dashboard）リンクの間に配置
    -   デスクトップ表示（`hidden lg:flex`）とモバイル表示（`lg:hidden`）の両方に対応
    -   アクティブ状態のハイライト表示（`route().current('calendar.*')`を使用）
    -   日本語化対応（`{{ $t("Calendar") }}`を使用）

### Phase 2: スケジュール操作 UI（M3-2）

#### 2.1 スケジュール作成 UI

-   **ScheduleForm.vue**: スケジュール作成フォーム

    -   日付選択
    -   利用者選択
    -   スケジュール種別選択
    -   開始時刻・終了時刻入力
    -   メモ入力

-   **カレンダーからの作成**: 日付クリックでフォーム表示

#### 2.2 スケジュール編集・削除 UI

-   **ScheduleModal.vue**: スケジュール詳細モーダル

    -   スケジュール情報表示
    -   編集ボタン
    -   削除ボタン

-   **イベントクリック**: スケジュールクリックでモーダル表示

### Phase 3: 週間・日間ビュー（M3-3）

#### 3.1 週間ビュー実装

-   **WeekView.vue**: 週間カレンダー表示
-   **CalendarController::week()**: 週間ビュー用データ取得
-   **ルート追加**: `/calendar/week`

#### 3.2 日間ビュー実装

-   **DayView.vue**: 日間カレンダー表示
-   **CalendarController::day()**: 日間ビュー用データ取得
-   **ルート追加**: `/calendar/day/{date}`

### Phase 4: 高度な機能（M3-4）

#### 4.1 ドラッグ&ドロップ

-   **左サイドバーに縦並びに表示した利用者名のみドラッグ&ドロップ可能**
    -   利用者名をカレンダーの日付セルにドラッグ&ドロップすることで、その利用者のスケジュールを素早く作成
    -   ドロップ時にスケジュール作成フォームを自動表示（日付と利用者が自動入力）
-   **スケジュールなどその他の要素はドラッグ&ドロップ不可**
    -   カレンダー上のスケジュールイベントはドラッグ&ドロップできない
    -   スケジュールの移動・変更は編集フォームから行う

#### 4.2 フィルタリング機能

-   利用者でフィルタリング
-   スケジュール種別でフィルタリング

#### 4.3 統計情報表示

-   **MonthStats.vue**: 月間統計情報
    -   総スケジュール数
    -   種別別のスケジュール数
    -   利用者別のスケジュール数

## 📊 データ構造

### CalendarController::index() のレスポンス

```php
return Inertia::render('Calendar/Index', [
    'schedules' => $schedules->map(function ($schedule) {
        return [
            'id' => $schedule->id,
            'title' => $schedule->resident->name . ' - ' . $schedule->scheduleType->name,
            'start' => $schedule->calendarDate->date->format('Y-m-d') . 'T' . $schedule->start_time . ':00',
            'end' => $schedule->calendarDate->date->format('Y-m-d') . 'T' . $schedule->end_time . ':00',
            'backgroundColor' => $schedule->scheduleType->color_code,
            'borderColor' => $schedule->scheduleType->color_code,
            'extendedProps' => [
                'resident_id' => $schedule->resident_id,
                'resident_name' => $schedule->resident->name,
                'schedule_type_id' => $schedule->schedule_type_id,
                'schedule_type_name' => $schedule->scheduleType->name,
                'memo' => $schedule->memo,
            ],
        ];
    }),
    'residents' => $residents,
    'scheduleTypes' => $scheduleTypes,
    'monthStats' => [
        'total' => $schedules->count(),
        'by_type' => $scheduleTypes->mapWithKeys(function ($type) use ($schedules) {
            return [$type->id => $schedules->where('schedule_type_id', $type->id)->count()];
        }),
    ],
    'currentDate' => $request->input('date', now()->format('Y-m-d')),
]);
```

## 🎨 UI/UX 設計

### カラーパレット

-   スケジュール種別ごとに色分け（`schedule_types.color_code`を使用）
-   Tailwind CSS のカラースキームに統合

### レスポンシブデザイン

-   **デスクトップ**: フル機能のカレンダー表示
-   **タブレット**: 週間ビューを推奨
-   **モバイル**: 日間ビューを推奨

### アクセシビリティ

-   キーボードナビゲーション対応
-   スクリーンリーダー対応
-   ARIA 属性の適切な使用

## ✅ 実装チェックリスト

### M3-1: 基盤実装

-   [ ] FullCalendar のインストール
-   [ ] CalendarController::index()の実装
-   [ ] ルート追加
-   [ ] Calendar/Index.vue の実装
-   [ ] 月間ビューの表示
-   [ ] スケジュールイベントの表示
-   [ ] 日本語化対応
-   [ ] ナビゲーションメニューへのカレンダーリンク追加（利用者リンクと管理者リンクの間）

### M3-2: スケジュール操作 UI

-   [ ] ScheduleForm.vue の実装
-   [ ] ScheduleModal.vue の実装
-   [ ] スケジュール作成機能
-   [ ] スケジュール編集機能
-   [ ] スケジュール削除機能
-   [ ] エラーハンドリング

### M3-3: 週間・日間ビュー

-   [ ] WeekView.vue の実装
-   [ ] DayView.vue の実装
-   [ ] CalendarController::week()の実装
-   [ ] CalendarController::day()の実装
-   [ ] ビュー切り替え機能

### M3-4: 高度な機能

-   [ ] 左サイドバーに利用者リストを縦並び表示
-   [ ] 利用者名のドラッグ&ドロップ機能実装
-   [ ] カレンダー日付セルへのドロップ処理
-   [ ] ドロップ時のスケジュール作成フォーム自動表示
-   [ ] フィルタリング機能
-   [ ] 統計情報表示
-   [ ] パフォーマンス最適化

## 🔄 API 連携

### 既存 API エンドポイントの活用

-   `GET /calendar/schedules`: スケジュール一覧取得（既存）
-   `POST /calendar/schedule`: スケジュール作成（既存）
-   `PUT /calendar/schedule/{id}`: スケジュール更新（既存）
-   `DELETE /calendar/schedule/{id}`: スケジュール削除（既存）

### 新規エンドポイント（必要に応じて）

-   `GET /calendar/residents`: 利用者一覧取得
-   `GET /calendar/schedule-types`: スケジュール種別一覧取得

## 🧪 テスト方針

### フロントエンドテスト

-   カレンダーコンポーネントの表示テスト
-   スケジュール操作のテスト
-   レスポンシブデザインのテスト

### E2E テスト（将来）

-   スケジュール作成フローのテスト
-   スケジュール編集フローのテスト
-   スケジュール削除フローのテスト

## 📝 注意事項

1. **マルチテナント対応**: すべてのデータ取得でテナント境界チェックを実施
2. **パフォーマンス**: 大量のスケジュールデータを効率的に表示
3. **UX**: 直感的で使いやすいインターフェースを提供
4. **既存デザインとの整合性**: Tailwind CSS と既存コンポーネントスタイルに合わせる

## 🚦 実装順序

1. **M3-1**: 基盤実装（カレンダー表示）
2. **M3-2**: スケジュール操作 UI（作成・編集・削除）
3. **M3-3**: 週間・日間ビュー
4. **M3-4**: 高度な機能（利用者名のドラッグ&ドロップ、フィルタリング）

各フェーズごとにコミット・プッシュ・PR 作成を実施し、段階的に実装を進めます。

## 📌 ドラッグ&ドロップ機能の詳細仕様

### 実装方針

-   **ドラッグ可能な要素**: 左サイドバーに縦並びに表示した利用者名のみ
-   **ドロップ先**: カレンダーの日付セル
-   **動作**: 利用者名を日付セルにドロップすると、スケジュール作成フォームが自動表示され、日付と利用者が自動入力される
-   **ドラッグ不可な要素**: カレンダー上のスケジュールイベント、その他のUI要素

### 技術実装

-   **vuedraggable**: 既存プロジェクトで使用中のライブラリを活用
-   **左サイドバー**: `ResidentSidebar.vue`コンポーネントで実装
-   **ドロップゾーン**: FullCalendarの日付セルをドロップゾーンとして設定
