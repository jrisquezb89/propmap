import { computed, ref, watchEffect } from 'vue'

/**
 * Light / dark / follow-the-OS theme.
 *
 * The preference is a per-visitor convenience, so localStorage is fine (wrapped
 * in try/catch: it throws in some private modes). index.html applies the same
 * logic in an inline script before first paint, so there's no light flash.
 */
export type ThemePreference = 'system' | 'light' | 'dark'

const STORAGE_KEY = 'propmap-theme'
const media = window.matchMedia('(prefers-color-scheme: dark)')

function readPreference(): ThemePreference {
  try {
    const v = localStorage.getItem(STORAGE_KEY)
    return v === 'light' || v === 'dark' ? v : 'system'
  } catch {
    return 'system'
  }
}

const preference = ref<ThemePreference>(readPreference())
const systemDark = ref(media.matches)
media.addEventListener('change', (e) => (systemDark.value = e.matches))

export const isDark = computed(() => preference.value === 'dark' || (preference.value === 'system' && systemDark.value))

watchEffect(() => {
  const root = document.documentElement
  root.classList.toggle('dark', isDark.value)
  root.style.colorScheme = isDark.value ? 'dark' : 'light' // native controls & scrollbars
  document.querySelector('meta[name="theme-color"]')?.setAttribute('content', isDark.value ? '#0b1220' : '#ffffff')
})

export function useTheme() {
  function setPreference(next: ThemePreference) {
    preference.value = next
    try {
      if (next === 'system') localStorage.removeItem(STORAGE_KEY)
      else localStorage.setItem(STORAGE_KEY, next)
    } catch {
      /* storage unavailable: the choice still applies for this visit */
    }
  }

  return { preference: computed(() => preference.value), isDark, setPreference }
}
