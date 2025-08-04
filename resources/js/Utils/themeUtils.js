export const initTheme = () => {
    const savedMode = localStorage.getItem('theme-mode') || 'system'
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
    
    // システム設定変更を監視
    mediaQuery.addEventListener('change', () => {
        const currentMode = localStorage.getItem('theme-mode') || 'system'
        if (currentMode === 'system') {
            applyTheme('system')
        }
    })
}