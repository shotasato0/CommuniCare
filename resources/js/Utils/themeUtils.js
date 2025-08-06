export const initTheme = () => {
    // SSR環境チェック
    if (typeof window === 'undefined') return
    
    const savedMode = (typeof localStorage !== 'undefined' ? localStorage.getItem('theme-mode') : null) || 'system'
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
    
    const applyTheme = (mode = savedMode) => {
        let isDark = false
        
        if (mode === 'system') {
            isDark = mediaQuery.matches
        } else {
            isDark = mode === 'dark'
        }
        
        if (typeof document !== 'undefined') {
            if (isDark) {
                document.documentElement.classList.add('dark')
            } else {
                document.documentElement.classList.remove('dark')
            }
        }
    }
    
    // 初期テーマ適用
    applyTheme()
    
    // システム設定変更を監視
    mediaQuery.addEventListener('change', () => {
        const currentMode = (typeof localStorage !== 'undefined' ? localStorage.getItem('theme-mode') : null) || 'system'
        if (currentMode === 'system') {
            applyTheme('system')
        }
    })
}