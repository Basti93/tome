import { defineStore } from 'pinia'

export const useSnackbarStore = defineStore('snackbar', {
  state: () => ({
    visible: false,
    text: '',
    color: 'info',
    timeout: 6000
  }),
  actions: {
    show(text: string, color = 'info', timeout = 6000) {
      this.text = text
      this.color = color
      this.timeout = timeout
      this.visible = true
    },
    hide() {
      this.visible = false
    }
  }
})
