import httpClient from '@/http/api'
import { useSnackbarStore } from '@/store/snackbar'

/**
 * Sends an invitation e-mail to a user (`POST /v1/user/{id}/invite`).
 * 200 {status:'ok'} on success, 422 if the user has no e-mail address.
 * Shows a snackbar for success and failure; resolves to whether the invitation was sent.
 */
export async function inviteUser(userId: number, displayName = ''): Promise<boolean> {
  const snackbar = useSnackbarStore()
  const who = displayName ? ` an ${displayName}` : ''
  try {
    const { data } = await httpClient.post(`/user/${userId}/invite`)
    if (data && data.status === 'ok') {
      snackbar.show(`Einladung${who} gesendet`, 'success')
      return true
    }
    snackbar.show('Einladung konnte nicht gesendet werden', 'error')
    return false
  } catch (error: any) {
    if (error?.response?.status === 422) {
      snackbar.show('Der Benutzer hat keine E-Mail-Adresse, Einladung nicht möglich', 'error')
    } else {
      snackbar.show('Einladung konnte nicht gesendet werden', 'error')
    }
    return false
  }
}
