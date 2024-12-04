import { onMounted, onUnmounted } from "vue";

/**
 * 指定された要素外をクリックした場合にコールバックを呼び出します。
 *
 * @param {Ref<HTMLElement>} elementRef - 監視対象の要素
 * @param {Function} callback - 要素外をクリックした際に呼ばれる関数
 */
export default function useClickOutside(elementRef, callback) {
    const handleClick = (event) => {
        if (elementRef.value && !elementRef.value.contains(event.target)) {
            callback();
        }
    };

    onMounted(() => {
        document.addEventListener("click", handleClick);
    });

    onUnmounted(() => {
        document.removeEventListener("click", handleClick);
    });
}
