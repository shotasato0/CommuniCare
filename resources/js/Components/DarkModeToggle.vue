<template>
    <div class="flex items-center space-x-2">
        <select
            v-model="selectedMode"
            @change="changeTheme"
            class="text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md px-2 py-1 text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
            <option value="system">{{ $t('System') }}</option>
            <option value="light">{{ $t('Light') }}</option>
            <option value="dark">{{ $t('Dark') }}</option>
        </select>
        
        <!-- 現在のモード表示アイコン -->
        <div class="text-gray-500 dark:text-gray-400">
            <svg v-if="currentMode === 'dark'" class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
            </svg>
            <svg v-else class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd" />
            </svg>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'

const selectedMode = ref('system')
const currentMode = ref('light')

// システム設定を監視
const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')

const updateCurrentMode = () => {
    if (selectedMode.value === 'system') {
        const systemIsDark = mediaQuery.matches
        currentMode.value = systemIsDark ? 'dark' : 'light'
        console.log('System theme detected:', systemIsDark ? 'dark' : 'light')
    } else {
        currentMode.value = selectedMode.value
        console.log('Manual theme set:', selectedMode.value)
    }
}

const applyTheme = () => {
    updateCurrentMode()
    
    console.log('Applying theme:', currentMode.value)
    
    if (currentMode.value === 'dark') {
        document.documentElement.classList.add('dark')
        console.log('Dark mode applied')
    } else {
        document.documentElement.classList.remove('dark')
        console.log('Light mode applied')
    }
}

const changeTheme = () => {
    localStorage.setItem('theme-mode', selectedMode.value)
    applyTheme()
}

// システム設定変更を監視
mediaQuery.addEventListener('change', () => {
    if (selectedMode.value === 'system') {
        applyTheme()
    }
})

// 初期化
onMounted(() => {
    const savedMode = localStorage.getItem('theme-mode') || 'system'
    console.log('Saved theme mode:', savedMode)
    console.log('Media query matches (system is dark):', mediaQuery.matches)
    selectedMode.value = savedMode
    applyTheme()
})

// selectedModeの変更を監視
watch(selectedMode, applyTheme)
</script>