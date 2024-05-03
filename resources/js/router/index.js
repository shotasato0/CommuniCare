import { createRouter, createWebHistory } from 'vue-router'; // vue-routerからcreateRouterとcreateWebHistoryをインポート
import { ExampleComponent, UnitListComponent, BulletinBoard } from '../components';

const routes = [ // ルーティングの設定を配列で定義
    {
        path: '/', // URLのパス
        name: 'Home', // ルートの名前
        component: ExampleComponent // このパスに対応するコンポーネント
    },
    {
        path: '/units',
        name: 'Units',
        component: UnitListComponent,
    },
    {
        path: '/units/:id',
        name: 'BulletinBoard',
        component: BulletinBoard,
        props: true,
    },
];

const router = createRouter({ // ルーターインスタンスを作成
    history: createWebHistory(), // ブラウザの履歴をWebヒストリーモードで使用
    routes // 上で定義したルート設定をルーターに適用
});

export default router; // 作成したルーターインスタンスをエクスポート