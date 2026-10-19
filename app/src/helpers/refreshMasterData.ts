import httpClient from '@/http/api'
import { useMasterDataStore } from '@/store/masterData'

/**
 * Reload the master data (simple users, trainers, groups, ...). The public endpoints answer with
 * abbreviated family names ("Anna M.") for anonymous visitors and with full names for logged-in users,
 * so the cached data from app start is stale after every login and logout.
 */
export async function refreshMasterData(): Promise<boolean> {
  try {
    await useMasterDataStore().loadAll(httpClient)
    return true
  } catch (e) {
    console.error('Reloading the master data failed', e)
    return false
  }
}
