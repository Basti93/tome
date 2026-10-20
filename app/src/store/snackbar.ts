import { defineStore } from 'pinia'

export const useSnackbarStore = defineStore('snackbar', {
  state: () => ({
    visible: false,
    text: '',
    color: 'info',
    timeout: 6000,
    // changes with every message: a new message restarts the timer even if the same text is shown again
    count: 0
  }),
  actions: {
    show(text: string, color = 'info', timeout = 6000) {
      this.text = text
      this.color = color
      this.timeout = timeout
      this.visible = true
      this.count++
    },
    hide() {
      this.visible = false
    }
  }
})
