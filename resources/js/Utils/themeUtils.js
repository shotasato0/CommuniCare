export const initTheme = () => {
    // SSR環境チェック - window, localStorage, document の存在確認
    if (typeof window === 'undefined' || typeof localStorage === 'undefined' || typeof document === 'undefined') {
        return
    }
    
    const savedMode = localStorage.getItem('theme-mode') || 'system'
    
    // matchMedia のサポート確認
    if (!window.matchMedia) {
        // matchMedia がサポートされていない場合はライトモードで固定
        console.warn('matchMedia is not supported, defaulting to light mode')
        if (savedMode === 'dark') {
            document.documentElement.classList.add('dark')
        }
        return
    }
    
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
    
    const applyTheme = (mode = savedMode) => {
        let isDark = false
        
        if (mode === 'system') {
            isDark = mediaQuery.matches
        } else {
            isDark = mode === 'dark'
        }
        
        if (isDark) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    }
    
    // 初期テーマ適用
    applyTheme()
    
    // システム設定変更を監視（addEventListener のサポート確認）
    if (mediaQuery.addEventListener) {
        mediaQuery.addEventListener('change', () => {
            const currentMode = localStorage.getItem('theme-mode') || 'system'
            if (currentMode === 'system') {
                applyTheme('system')
            }
        })
    } else if (mediaQuery.addListener) {
        // 古いブラウザ対応（IE/Edge Legacy）
        // @ts-ignore - addListener is deprecated but needed for legacy browser support
        mediaQuery.addListener(() => {
            const currentMode = localStorage.getItem('theme-mode') || 'system'
            if (currentMode === 'system') {
                applyTheme('system')
            }
        })
    }
}