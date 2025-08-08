import { ref } from 'vue';

/**
 * SSR対応のデバイス判定Composable
 * 画面幅に基づいてモバイル/デスクトップを判定する
 * 
 * @param {number} breakpoint - モバイル判定の境界値（px、デフォルト: 1024）
 * @returns {Object} { isMobile: Ref<boolean> }
 */
export function useDeviceDetection(breakpoint = 1024) {
    // 初期値は false（デスクトップ前提）。SSR（サーバーサイド）時にウィンドウオブジェクトが存在しないため、常にデスクトップとして扱われます。
    // そのため、SSR環境ではモバイルデバイスでもデスクトップ用の表示・挙動となる可能性があります。クライアント側で再判定されるまで正しいデバイス判定はできません。
    const isMobile = ref(false);
    
    // ブラウザ環境でのみ初期化
    if (typeof window !== 'undefined' && window.matchMedia) {
        const mediaQuery = window.matchMedia(`(max-width: ${breakpoint}px)`);
        isMobile.value = mediaQuery.matches;
        
        // ウィンドウの幅が変更された時にモバイルかどうかを再判定
        mediaQuery.addEventListener('change', (event) => {
            isMobile.value = event.matches;
        });
    }
    
    return {
        isMobile
    };
}