<script setup>
import { computed, toRefs, useAttrs } from 'vue'
defineOptions({ inheritAttrs: false })

// このコンポーネントはテキストエリア右下に添付ボタンを重ねる共通UI
// - 親から v-model で値を受け取る
// - 添付ボタンクリック時に attach-click をemit（親で fileUploadRef.openFileDialog() を呼ぶ）
// - 任意のリスナー（dragenter/over/leave/drop など）は $attrs 経由でtextareaに転送

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  rows: { type: Number, default: 3 },
  textareaClass: { type: String, default: 'w-full p-2 pr-12 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100' },
  rightOffsetClass: { type: String, default: 'right-3' },
  bottomOffsetClass: { type: String, default: 'bottom-5' },
  buttonTitle: { type: String, default: 'ファイルを選択' },
  buttonAriaLabel: { type: String, default: 'ファイルを選択' },
  dragActive: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'attach-click'])
const attrs = useAttrs()

const valueProxy = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v)
})

</script>

<template>
  <div class="relative">
    <textarea
      v-model="valueProxy"
      :rows="rows"
      :placeholder="placeholder"
      :class="textareaClass"
      v-bind="attrs"
    ></textarea>
    <button
      type="button"
      class="absolute bg-gray-300 dark:bg-gray-600 text-black dark:text-gray-300 transition hover:bg-gray-400 dark:hover:bg-gray-500 rounded-md flex items-center justify-center cursor-pointer p-2"
      :class="[rightOffsetClass, bottomOffsetClass, { 'pointer-events-none': dragActive }]"
      style="width: 40px; height: 40px"
      :title="buttonTitle"
      :aria-label="buttonAriaLabel"
      @click="$emit('attach-click')"
    >
      <i class="bi bi-paperclip text-2xl"></i>
    </button>
  </div>
</template>
